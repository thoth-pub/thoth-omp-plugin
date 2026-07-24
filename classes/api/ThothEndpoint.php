<?php

/**
 * @file plugins/generic/thoth/classes/api/ThothEndpoint.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothEndpoint
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Thoth endpoints for OMP API
 */

namespace APP\plugins\generic\thoth\classes\api;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\thoth\classes\components\forms\FeatureVideoForm;
use APP\plugins\generic\thoth\classes\exceptions\MetadataSynchronizationException;
use APP\plugins\generic\thoth\classes\facades\ThothRepository;
use APP\plugins\generic\thoth\classes\facades\ThothService;
use APP\plugins\generic\thoth\classes\notification\ThothNotification;
use APP\plugins\generic\thoth\classes\services\ThothWorkLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response;
use InvalidArgumentException;
use PKP\core\PKPBaseController;
use PKP\core\PKPRequest;
use PKP\db\DAORegistry;
use PKP\handler\APIHandler;
use PKP\plugins\interfaces\HasAuthorizationPolicy;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\security\Role;
use PKP\userGroup\UserGroup;
use ThothApi\Exception\QueryException;

class ThothEndpoint implements HasAuthorizationPolicy
{
    public function addEndpoints(string $hookName, PKPBaseController $apiController, APIHandler $apiHandler): bool
    {
        $apiHandler->addRoute(
            'PUT',
            '{submissionId}/register',
            $this->register(...),
            'thoth.register',
            [
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_MANAGER,
            ],
            $this
        );

        $apiHandler->addRoute(
            'GET',
            '{submissionId}/thothWorkStatus',
            $this->getWorkStatus(...),
            'thoth.workStatus',
            [
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
                Role::ROLE_ID_ASSISTANT,
            ],
            $this
        );

        $apiHandler->addRoute(
            'DELETE',
            '{submissionId}/thothWork',
            $this->unlinkWork(...),
            'thoth.unlinkWork',
            [
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
                Role::ROLE_ID_ASSISTANT,
            ],
            $this
        );

        $apiHandler->addRoute(
            'PUT',
            '{submissionId}/publications/{publicationId}/synchronize',
            $this->synchronize(...),
            'thoth.synchronize',
            [
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
                Role::ROLE_ID_ASSISTANT,
            ],
            $this
        );

        $apiHandler->addRoute(
            'GET',
            '{submissionId}/featureVideo',
            $this->getFeatureVideoForm(...),
            'thoth.featureVideo.form',
            [
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
                Role::ROLE_ID_ASSISTANT,
            ]
        );

        $apiHandler->addRoute(
            'POST',
            '{submissionId}/featureVideo',
            $this->uploadFeatureVideo(...),
            'thoth.featureVideo.upload',
            [
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
                Role::ROLE_ID_ASSISTANT,
            ]
        );

        return false;
    }

    public function getPolicies(PKPRequest $request, array &$args, array $roleAssignments): array
    {
        return [new SubmissionAccessPolicy($request, $args, $roleAssignments)];
    }

    public function register(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $request = Application::get()->getRequest();
        $submissionId = (int) $illuminateRequest->route('submissionId');
        $submission = Repo::submission()->get($submissionId);

        $thothImprintId = $illuminateRequest->input('thothImprintId');
        if (!$thothImprintId) {
            return response()->json(
                ['thothImprintId' => [__('plugins.generic.thoth.imprint.required')]],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!$submission) {
            return response()->json(
                ['error' => __('api.404.resourceNotFound')],
                Response::HTTP_NOT_FOUND
            );
        }

        if (!$request->getContext()) {
            return response()->json(
                ['error' => __('api.submissions.403.contextRequired')],
                Response::HTTP_FORBIDDEN
            );
        }

        if ($submission->getData('thothWorkId')) {
            return response()->json(
                ['error' => __('plugins.generic.thoth.api.403.alreadyRegistered')],
                Response::HTTP_FORBIDDEN
            );
        }

        $publication = $submission->getCurrentPublication();

        $failure = [
            'id' => $submission->getId(),
            'errors' => []
        ];

        try {
            $failure['errors'] = ThothService::book()->validate($publication);
        } catch (\Exception $e) {
            $failure['errors'][] = __('plugins.generic.thoth.connectionError');
        }

        if ($failure['errors']) {
            return response()->json($failure, Response::HTTP_BAD_REQUEST);
        }

        $disableNotification = $illuminateRequest->input('disableNotification', false);
        $registrationResult = null;
        try {
            $thothBookRegistrationService = ThothService::bookRegistration();
            $registrationResult = $thothBookRegistrationService->register($publication, $thothImprintId);
            $thothBookRegistrationService->setActive($registrationResult);
            $thothBookId = $registrationResult->getWorkId();
            Repo::submission()->edit($submission, ['thothWorkId' => $thothBookId]);
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
            return response()->json($failure, Response::HTTP_BAD_REQUEST);
        }

        $thothWork = ThothRepository::work()->get($thothBookId);
        $thothWorkStatus = $thothWork->getWorkStatus();

        $submission = Repo::submission()->get($submission->getId());

        $userGroups = UserGroup::withContextIds($submission->getData('contextId'))->get();

        $genreDao = DAORegistry::getDAO('GenreDAO');
        $genres = $genreDao->getByContextId($submission->getData('contextId'))->toArray();

        $routeController = PKPBaseController::getRouteController();
        $userRoles = (array) $routeController->getAuthorizedContextObject(Application::ASSOC_TYPE_USER_ROLES);

        $submissionProps = Repo::submission()->getSchemaMap()->map(
            $submission,
            $userGroups,
            $genres,
            $userRoles
        );
        $submissionProps['thothWorkStatus'] = $thothWorkStatus;

        return response()->json(
            $submissionProps,
            Response::HTTP_OK
        );
    }

    public function getWorkStatus(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $submissionId = (int) $illuminateRequest->route('submissionId');
        $submission = Repo::submission()->get($submissionId);

        if (!$submission) {
            return response()->json(
                ['error' => __('api.404.resourceNotFound')],
                Response::HTTP_NOT_FOUND
            );
        }

        $thothWorkId = $submission->getData('thothWorkId');
        if (!$thothWorkId) {
            return response()->json(
                ['error' => __('plugins.generic.thoth.status.unregistered')],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $workStatus = (new ThothWorkLinkService(ThothRepository::work()))->getStatus($thothWorkId);
            if ($workStatus === null) {
                return response()->json(
                    [
                        'error' => __('plugins.generic.thoth.status.notFound'),
                        'workNotFound' => true,
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json(
                ['workStatus' => $workStatus],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                ['error' => __('plugins.generic.thoth.connectionError')],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function unlinkWork(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $submissionId = (int) $illuminateRequest->route('submissionId');
        $submission = Repo::submission()->get($submissionId);

        if (!$submission) {
            return response()->json(
                ['error' => __('api.404.resourceNotFound')],
                Response::HTTP_NOT_FOUND
            );
        }

        $thothWorkId = $submission->getData('thothWorkId');
        if (!$thothWorkId) {
            return response()->json(
                ['error' => __('plugins.generic.thoth.status.unregistered')],
                Response::HTTP_NOT_FOUND
            );
        }

        try {
            $workStatus = (new ThothWorkLinkService(ThothRepository::work()))->getStatus($thothWorkId);
            if ($workStatus !== null) {
                return response()->json(
                    ['error' => __('plugins.generic.thoth.unlink.existingWork')],
                    Response::HTTP_CONFLICT
                );
            }
        } catch (\Exception $e) {
            return response()->json(
                ['error' => __('plugins.generic.thoth.connectionError')],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        Repo::submission()->edit($submission, ['thothWorkId' => null]);

        return response()->json(['status' => true], Response::HTTP_OK);
    }

    public function synchronize(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $submission = Repo::submission()->get((int) $illuminateRequest->route('submissionId'));
        $publication = Repo::publication()->get((int) $illuminateRequest->route('publicationId'));

        if (
            !$submission
            || !$publication
            || (int) $publication->getData('submissionId') !== (int) $submission->getId()
        ) {
            return response()->json(
                ['errorMessage' => __('api.404.resourceNotFound')],
                Response::HTTP_NOT_FOUND
            );
        }

        if (!$context || (int) $submission->getData('contextId') !== (int) $context->getId()) {
            return response()->json(
                ['errorMessage' => __('api.submissions.403.contextRequired')],
                Response::HTTP_FORBIDDEN
            );
        }

        $thothWorkId = $submission->getData('thothWorkId');
        if (!$thothWorkId) {
            return response()->json(
                ['errorMessage' => __('plugins.generic.thoth.status.unregistered')],
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $warning = ThothService::metadataSynchronization()->synchronize($publication, $thothWorkId);
            $this->handleNotification($request, $submission, true, false, null, $warning);
        } catch (MetadataSynchronizationException $exception) {
            return response()->json(
                ['errorMessage' => __('plugins.generic.thoth.synchronize.ambiguousMetadata')],
                Response::HTTP_CONFLICT
            );
        } catch (QueryException $exception) {
            $this->handleNotification($request, $submission, false, false, $exception);
            return response()->json(
                ['errorMessage' => __('plugins.generic.thoth.connectionError')],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return response()->json(['status' => true], Response::HTTP_OK);
    }

    public function getFeatureVideoForm(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $submissionId = (int) $illuminateRequest->route('submissionId');
        $submission = Repo::submission()->get($submissionId);
        if (!$submission) {
            return response()->json(
                ['error' => __('api.404.resourceNotFound')],
                Response::HTTP_NOT_FOUND
            );
        }

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context || (int) $submission->getData('contextId') !== (int) $context->getId()) {
            return response()->json(
                ['error' => __('api.submissions.403.contextRequired')],
                Response::HTTP_FORBIDDEN
            );
        }

        $dispatcher = $request->getDispatcher();
        $featureVideoUrl = $dispatcher->url(
            $request,
            Application::ROUTE_API,
            $context->getData('urlPath'),
            '_submissions/' . $submissionId . '/featureVideo'
        );
        $temporaryFilesUrl = $dispatcher->url(
            $request,
            Application::ROUTE_API,
            $context->getData('urlPath'),
            'temporaryFiles'
        );
        try {
            $existingVideo = $submission->getData('thothWorkId')
                ? ThothRepository::work()->getFeatureVideo($submission->getData('thothWorkId'))
                : null;
            $form = new FeatureVideoForm(
                $featureVideoUrl,
                $temporaryFilesUrl,
                ThothService::me()->hasCdnWritePermission(),
                (bool) $existingVideo
            );
        } catch (\Throwable $exception) {
            error_log($exception->getMessage());
            return response()->json(
                ['error' => __('plugins.generic.thoth.connectionError')],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return response()->json($form->getConfig(), Response::HTTP_OK);
    }

    public function uploadFeatureVideo(IlluminateRequest $illuminateRequest): JsonResponse
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $user = $request->getUser();
        $submissionId = (int) $illuminateRequest->route('submissionId');
        $submission = Repo::submission()->get($submissionId);
        if (!$submission) {
            return response()->json(
                ['error' => __('api.404.resourceNotFound')],
                Response::HTTP_NOT_FOUND
            );
        }
        if (!$context || (int) $submission->getData('contextId') !== (int) $context->getId() || !$user) {
            return response()->json(
                ['error' => __('api.submissions.403.contextRequired')],
                Response::HTTP_FORBIDDEN
            );
        }

        $title = trim((string) $illuminateRequest->input('title'));
        $temporaryFileId = (int) $illuminateRequest->input('video.temporaryFileId');
        $errors = [];
        if ($title === '') {
            $errors['title'] = [__('form.required')];
        }
        if (!$temporaryFileId) {
            $errors['video'] = [__('form.required')];
        }
        if ($errors) {
            return response()->json($errors, Response::HTTP_BAD_REQUEST);
        }

        try {
            if (!ThothService::me()->hasCdnWritePermission()) {
                return response()->json(
                    ['video' => [__('plugins.generic.thoth.fileUpload.error.missingCdnWritePermission')]],
                    Response::HTTP_FORBIDDEN
                );
            }

            $metadata = ThothService::featureVideoSubmission()->upload(
                $submission,
                $title,
                $temporaryFileId,
                (int) $user->getId()
            );
            return response()->json($metadata, Response::HTTP_OK);
        } catch (InvalidArgumentException $exception) {
            return response()->json(
                ['video' => [__('plugins.generic.thoth.featureVideo.invalidFile')]],
                Response::HTTP_BAD_REQUEST
            );
        } catch (\Throwable $exception) {
            error_log($exception->getMessage());
            return response()->json(
                ['error' => __('plugins.generic.thoth.connectionError')],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
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
