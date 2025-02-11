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
import('plugins.generic.thoth.classes.notification.ThothNotification');

class ThothEndpoint
{
    public function addEndpoints($hookName, $args)
    {
        $endpoints = & $args[0];
        $handler = $args[1];

        if (!is_a($handler, 'PKPSubmissionHandler')) {
            return false;
        }

        array_unshift(
            $endpoints['PUT'],
            [
                'pattern' => $handler->getEndpointPattern() . '/{submissionId}/publications/{publicationId}/register',
                'handler' => [$this, 'addThothBook'],
                'roles' => [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR],
            ]
        );

        $handler->requiresSubmissionAccess[] = 'addThothBook';

        return false;
    }

    public function addThothBook($slimRequest, $response, $args)
    {
        $request = Application::get()->getRequest();
        $handler = $request->getRouter()->getHandler();
        $submission = $handler->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
        $publication = Services::get('publication')->get((int) $args['publicationId']);
        $params = $slimRequest->getParsedBody();

        if (empty($params['imprint'])) {
            return $response->withStatus(400)->withJson(['imprint' => [__('plugins.generic.thoth.imprint.required')]]);
        }

        if (!$publication) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        if ($submission->getId() !== $publication->getData('submissionId')) {
            return $response->withStatus(403)->withJsonError('api.publications.403.submissionsDidNotMatch');
        }

        if ($submission->getData('thothWorkId')) {
            return $response->withStatus(403)->withJsonError('plugins.generic.thoth.api.403.alreadyRegistered');
        }

        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_SUBMISSION);

        $submissionContext = $request->getContext();
        if (!$submissionContext || $submissionContext->getId() !== $submission->getData('contextId')) {
            $submissionContext = Services::get('context')->get($submission->getData('contextId'));
        }

        $thothNotification = new ThothNotification();
        try {
            $thothBookId = ThothService::book()->register($publication, $params['imprint']);
            $submission = Services::get('submission')->edit($submission, ['thothWorkId' => $thothBookId], $request);
            $thothNotification->notifySuccess($request, $submission);
        } catch (QueryException $e) {
            $thothNotification->notifyError($request, $submission, $e->getMessage());
            return $response->withStatus(403)->withJsonError('plugins.generic.thoth.register.error');
        }

        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');

        $publicationProps = Services::get('publication')->getFullProperties(
            $publication,
            [
                'request' => $request,
                'userGroups' => $userGroupDao->getByContextId($submission->getData('contextId'))->toArray(),
            ]
        );

        return $response->withJson($publicationProps, 200);
    }
}
