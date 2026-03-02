<?php

/**
 * @file plugins/generic/thoth/classes/api/ThothEndpoint.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
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
use APP\plugins\generic\thoth\classes\facades\ThothRepository;
use APP\plugins\generic\thoth\classes\facades\ThothService;
use APP\plugins\generic\thoth\classes\notification\ThothNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response;
use PKP\core\PKPBaseController;
use PKP\db\DAORegistry;
use PKP\handler\APIHandler;
use PKP\security\Role;
use PKP\userGroup\UserGroup;
use ThothApi\Exception\QueryException;

class ThothEndpoint
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
            ]
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
            ]
        );

        return false;
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
        try {
            $thothBookService = ThothService::book();
            $thothBookId = $thothBookService->register($publication, $thothImprintId);
            $thothBookService->setActive();
            Repo::submission()->edit($submission, ['thothWorkId' => $thothBookId]);
            $this->handleNotification($request, $submission, true, $disableNotification);
        } catch (QueryException $e) {
            $thothBookService->deleteRegisteredEntry();
            $this->handleNotification($request, $submission, false, $disableNotification, $e->getMessage());
            $failure['errors'][] = __('plugins.generic.thoth.register.error.log', ['reason' => $e->getMessage()]);
            return response()->json($failure, Response::HTTP_FORBIDDEN);
        }

        $submission = Repo::submission()->get($submission->getId());

        $userGroups = UserGroup::withContextIds($submission->getData('contextId'))->get();

        $genreDao = DAORegistry::getDAO('GenreDAO');
        $genres = $genreDao->getByContextId($submission->getData('contextId'))->toArray();

        $routeController = PKPBaseController::getRouteController();
        $userRoles = (array) $routeController->getAuthorizedContextObject(Application::ASSOC_TYPE_USER_ROLES);

        return response()->json(
            Repo::submission()->getSchemaMap()->map(
                $submission,
                $userGroups,
                $genres,
                $userRoles
            ),
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
            $thothWork = ThothRepository::work()->get($thothWorkId);
            return response()->json(
                ['workStatus' => $thothWork->getWorkStatus()],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                ['error' => __('plugins.generic.thoth.connectionError')],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function handleNotification($request, $submission, $success, $disableNotification, $errorMessage = null)
    {
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
    }
}
