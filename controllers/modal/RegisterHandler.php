<?php

/**
 * @file controllers/modal/RegisterHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RegisterHandler
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A handler to load Thoth register confirmation
 */

namespace APP\plugins\generic\thoth\controllers\modal;

use APP\core\Application;
use APP\handler\Handler;
use APP\plugins\generic\thoth\classes\components\forms\RegisterForm;
use APP\plugins\generic\thoth\classes\facades\ThothRepository;
use APP\plugins\generic\thoth\classes\facades\ThothService;
use APP\template\TemplateManager;
use Exception;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\core\PKPRequest;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\PublicationAccessPolicy;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\security\Role;

class RegisterHandler extends Handler
{
    public $submission;

    public $publication;

    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_MANAGER, Role::ROLE_ID_ASSISTANT],
            ['register']
        );
    }

    /**
     * @copydoc PKPHandler::initialize()
     */
    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);
        $this->submission = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);
        $this->publication = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_PUBLICATION);
        $this->setupTemplate($request);
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
        $this->addPolicy(new PublicationAccessPolicy($request, $args, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Display a Thoth registration confirmation form
     *
     * @param array $args
     * @param PKPRequest $request
     *
     * @return JSONMessage JSON object
     */
    public function register($args, $request)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'thothplugin');

        $templateMgr = TemplateManager::getManager($request);

        $submissionContext = $request->getContext();
        if (
            !$submissionContext
            || $submissionContext->getId() !== $this->submission->getData('contextId')
        ) {
            $submissionContext = app()->get('context')->get($this->submission->getData('contextId'));
        }

        $publicationApiUrl = $request->getDispatcher()->url(
            $request,
            PKPApplication::ROUTE_API,
            $submissionContext->getPath(),
            '_submissions/' . $this->submission->getId() . '/register'
        );

        $imprints = [];
        $workType = $this->submission->getData('workType');
        try {
            $errors = ThothService::book()->validate($this->publication);

            if (empty($errors)) {
                $publishers = ThothRepository::account()->getLinkedPublishers();
                $imprints = ThothRepository::imprint()->getMany(array_column($publishers, 'publisherId'));
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            $errors = [__('plugins.generic.thoth.connectionError')];
        }

        $registerForm = new RegisterForm($publicationApiUrl, $imprints, $workType, $errors);

        $settingsData = [
            'components' => [
                'register' => $registerForm->getConfig(),
            ],
        ];

        $templateMgr->assign('registerData', $settingsData);

        return $templateMgr->fetchJson($plugin->getTemplateResource('thoth/register.tpl'));
    }
}
