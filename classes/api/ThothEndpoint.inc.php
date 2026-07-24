<?php

/**
 * @file plugins/generic/thoth/classes/api/ThothEndpoint.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothEndpoint
 * @ingroup plugins_generic_thoth
 *
 * @brief Thoth endpoints for OMP API
 */

use ThothApi\Exception\QueryException;

import('plugins.generic.thoth.classes.facades.ThothService');
import('plugins.generic.thoth.classes.facades.ThothRepo');
import('plugins.generic.thoth.classes.exceptions.MetadataSynchronizationException');
import('plugins.generic.thoth.classes.notification.ThothNotification');
import('plugins.generic.thoth.classes.services.ThothMeCacheService');
import('plugins.generic.thoth.classes.services.ThothWorkLinkService');

class ThothEndpoint
{
    public function addEndpoints($hookName, $args)
    {
        $endpoints = & $args[0];
        $handler = $args[1];

        if (!is_a($handler, 'PKPSubmissionHandler')) {
            return false;
        }

        $rootPattern = $handler->getEndpointPattern();

        array_unshift(
            $endpoints['PUT'],
            [
                'pattern' => $handler->getEndpointPattern() . '/{submissionId}/register',
                'handler' => [$this, 'register'],
                'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR],
            ]
        );

        $handler->requiresSubmissionAccess[] = 'register';

        array_unshift(
            $endpoints['GET'],
            [
                'pattern' => $rootPattern . '/{submissionId}/thothWorkStatus',
                'handler' => [$this, 'getWorkStatus'],
                'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT],
            ]
        );

        $handler->requiresSubmissionAccess[] = 'getWorkStatus';

        array_unshift(
            $endpoints['DELETE'],
            [
                'pattern' => $rootPattern . '/{submissionId}/thothWork',
                'handler' => [$this, 'unlinkWork'],
                'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT],
            ]
        );

        $handler->requiresSubmissionAccess[] = 'unlinkWork';

        array_unshift(
            $endpoints['PUT'],
            [
                'pattern' => $rootPattern
                    . '/{submissionId}/publications/{publicationId}/synchronize',
                'handler' => [$this, 'synchronize'],
                'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT],
            ]
        );

        $handler->requiresSubmissionAccess[] = 'synchronize';

        $endpoints['POST'][] = [
            'pattern' => "{$rootPattern}/{submissionId:\d+}/featureVideo",
            'handler' => [$this, 'uploadFeatureVideo'],
            'roles' => [
                ROLE_ID_MANAGER,
                ROLE_ID_SUB_EDITOR,
                ROLE_ID_ASSISTANT,
            ],
        ];

        return false;
    }

    public function register($slimRequest, $response, $args)
    {
        $request = Application::get()->getRequest();
        $handler = $request->getRouter()->getHandler();
        $submission = $handler->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
        $params = $slimRequest->getParsedBody();

        $thothImprintId = $params['thothImprintId'];
        if (!$thothImprintId) {
            return $response->withStatus(400)->withJson(
                ['thothImprintId' => [__('plugins.generic.thoth.imprint.required')]]
            );
        }

        if (!$submission) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        if (!$request->getContext()) {
            return $response->withStatus(403)->withJsonError('api.submissions.403.contextRequired');
        }

        if ($submission->getData('thothWorkId')) {
            return $response->withStatus(403)->withJsonError('plugins.generic.thoth.api.403.alreadyRegistered');
        }

        $publication = $submission->getCurrentPublication();

        $failure = [
            'id' => $submission->getId(),
            'errors' => []
        ];

        try {
            $failure['errors'] = ThothService::book()->validate($publication);
        } catch (Exception $e) {
            $failure['errors'][] = __('plugins.generic.thoth.connectionError');
        }

        if ($failure['errors']) {
            return $response->withStatus(400)->withJson($failure);
        }

        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_SUBMISSION);

        $disableNotification = $params['disableNotification'] ?? false;
        $registrationResult = null;
        try {
            $thothBookRegistrationService = ThothService::bookRegistration();
            $registrationResult = $thothBookRegistrationService->register($publication, $thothImprintId);
            $thothBookRegistrationService->setActive($registrationResult);
            $thothBookId = $registrationResult->getWorkId();
            $submission = Services::get('submission')->edit($submission, ['thothWorkId' => $thothBookId], $request);
            $this->handleNotification(
                $request,
                $submission,
                true,
                $disableNotification,
                null,
                $registrationResult->getWarning()
            );
        } catch (QueryException $e) {
            if ($registrationResult !== null) {
                $thothBookRegistrationService->deleteRegisteredEntry($registrationResult);
            }
            $this->handleNotification(
                $request,
                $submission,
                false,
                $disableNotification,
                $e,
                $registrationResult ? $registrationResult->getWarning() : null
            );
            $failure['errors'][] = __('plugins.generic.thoth.register.error.log', ['reason' => $e->getMessage()]);
            return $response->withStatus(403)->withJson($failure);
        }

        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');

        $submissionProps = Services::get('submission')->getFullProperties($submission, [
            'request' => $request,
            'slimRequest' => $slimRequest,
            'userGroups' => $userGroupDao->getByContextId($submission->getData('contextId'))->toArray(),
        ]);

        return $response->withJson($submissionProps, 200);
    }

    public function uploadFeatureVideo($slimRequest, $response, $args)
    {
        $request = Application::get()->getRequest();
        $submission = Services::get('submission')->get((int) $args['submissionId']);
        $context = $request->getContext();
        $user = $request->getUser();
        if (!$submission) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }
        if (!$context || (int) $submission->getData('contextId') !== (int) $context->getId() || !$user) {
            return $response->withStatus(403)->withJsonError('api.submissions.403.contextRequired');
        }

        $params = (array) $slimRequest->getParsedBody();
        $title = trim((string) ($params['title'] ?? ''));
        $temporaryFileId = (int) ($params['video']['temporaryFileId'] ?? 0);
        $errors = [];
        if ($title === '') {
            $errors['title'] = [__('form.required')];
        }
        if (!$temporaryFileId) {
            $errors['video'] = [__('form.required')];
        }
        if ($errors) {
            return $response->withStatus(400)->withJson($errors);
        }

        try {
            $canUpload = (new ThothMeCacheService(ThothRepo::me()))
                ->hasCdnWritePermission($context->getId());
            if (!$canUpload) {
                return $response->withStatus(403)->withJson([
                    'video' => [__('plugins.generic.thoth.fileUpload.error.missingCdnWritePermission')],
                ]);
            }
            $metadata = ThothService::featureVideoSubmission()->upload(
                $submission,
                $title,
                $temporaryFileId,
                (int) $user->getId()
            );
            return $response->withJson($metadata, 200);
        } catch (InvalidArgumentException $exception) {
            $message = $exception->getMessage();
            if (
                strpos($message, 'temporary video file was not found') !== false
                || strpos($message, 'supported video') !== false
            ) {
                return $response->withStatus(400)->withJson([
                    'video' => [__('plugins.generic.thoth.featureVideo.invalidFile')],
                ]);
            }

            error_log($message);
            return $response->withStatus(500)->withJsonError('plugins.generic.thoth.connectionError');
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            return $response->withStatus(500)->withJsonError('plugins.generic.thoth.connectionError');
        }
    }

    public function getWorkStatus($slimRequest, $response, $args)
    {
        $request = Application::get()->getRequest();
        $handler = $request->getRouter()->getHandler();
        $submission = $handler->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

        if (!$submission) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $thothWorkId = $submission->getData('thothWorkId');
        if (!$thothWorkId) {
            return $response->withStatus(404)->withJsonError('plugins.generic.thoth.status.unregistered');
        }

        try {
            $workStatus = (new ThothWorkLinkService(ThothRepo::work()))->getStatus($thothWorkId);
            if ($workStatus === null) {
                return $response->withStatus(404)->withJson([
                    'error' => __('plugins.generic.thoth.status.notFound'),
                    'workNotFound' => true,
                ]);
            }

            return $response->withJson(['workStatus' => $workStatus], 200);
        } catch (QueryException $exception) {
            return $response->withStatus(500)->withJsonError('plugins.generic.thoth.connectionError');
        }
    }

    public function unlinkWork($slimRequest, $response, $args)
    {
        $request = Application::get()->getRequest();
        $handler = $request->getRouter()->getHandler();
        $submission = $handler->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

        if (!$submission) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        $thothWorkId = $submission->getData('thothWorkId');
        if (!$thothWorkId) {
            return $response->withStatus(404)->withJsonError('plugins.generic.thoth.status.unregistered');
        }

        try {
            $workStatus = (new ThothWorkLinkService(ThothRepo::work()))->getStatus($thothWorkId);
            if ($workStatus !== null) {
                return $response->withStatus(409)->withJsonError('plugins.generic.thoth.unlink.existingWork');
            }
        } catch (QueryException $exception) {
            return $response->withStatus(500)->withJsonError('plugins.generic.thoth.connectionError');
        }

        Services::get('submission')->edit($submission, ['thothWorkId' => null], $request);

        return $response->withJson(['status' => true], 200);
    }

    public function synchronize($slimRequest, $response, $args)
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $submission = Services::get('submission')->get((int) $args['submissionId']);
        $publication = Services::get('publication')->get((int) $args['publicationId']);

        if (
            !$submission
            || !$publication
            || (int) $publication->getData('submissionId') !== (int) $submission->getId()
        ) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        if (!$context || (int) $submission->getData('contextId') !== (int) $context->getId()) {
            return $response->withStatus(403)->withJsonError('api.submissions.403.contextRequired');
        }

        $thothWorkId = $submission->getData('thothWorkId');
        if (!$thothWorkId) {
            return $response->withStatus(403)->withJsonError('plugins.generic.thoth.status.unregistered');
        }

        try {
            $warning = ThothService::metadataSynchronization()->synchronize($publication, $thothWorkId);
            $this->handleNotification($request, $submission, true, false, null, $warning);
        } catch (MetadataSynchronizationException $exception) {
            return $response->withStatus(409)->withJsonError(
                'plugins.generic.thoth.synchronize.ambiguousMetadata'
            );
        } catch (QueryException $exception) {
            $this->handleNotification($request, $submission, false, false, $exception);
            return $response->withStatus(500)->withJsonError('plugins.generic.thoth.connectionError');
        }

        return $response->withJson(['status' => true], 200);
    }


    public function handleNotification(
        $request,
        $submission,
        $success,
        $disableNotification,
        $errorMessage = null,
        $warning = null
    ) {
        $thothNotification = new ThothNotification();

        if ($disableNotification) {
            $thothNotification->logInfo(
                $request,
                $submission,
                $success ? 'plugins.generic.thoth.register.success.log' : 'plugins.generic.thoth.register.error.log',
                $errorMessage
            );
            return;
        }

        $success
            ? $thothNotification->notifySuccess($request, $submission)
            : $thothNotification->notifyError($request, $submission, $errorMessage);
        foreach ((array) $warning as $warningMessage) {
            $thothNotification->notifyWarning($request, $submission, $warningMessage);
        }
    }
}
