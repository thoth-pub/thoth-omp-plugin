<?php

/**
 * @file controllers/modals/RegisterHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RegisterHandler
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A handler to load Thoth register confirmation
 */

use APP\core\Services;
use APP\handler\Handler;
use APP\i18n\AppLocale;
use APP\template\TemplateManager;
use PKP\plugins\PluginRegistry;
use PKP\security\Role;

import('plugins.generic.thoth.classes.facades.ThothService');
import('plugins.generic.thoth.classes.facades.ThothRepository');

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

    public function initialize($request)
    {
        parent::initialize($request);
        $this->submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
        $this->publication = $this->getAuthorizedContextObject(ASSOC_TYPE_PUBLICATION);
        $this->setupTemplate($request);
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
        $this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
        import('lib.pkp.classes.security.authorization.PublicationAccessPolicy');
        $this->addPolicy(new PublicationAccessPolicy($request, $args, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function register($args, $request)
    {
        AppLocale::requireComponents(
            LOCALE_COMPONENT_PKP_SUBMISSION,
            LOCALE_COMPONENT_APP_SUBMISSION,
            LOCALE_COMPONENT_PKP_EDITOR,
            LOCALE_COMPONENT_APP_EDITOR
        );

        $plugin = PluginRegistry::getPlugin('generic', 'thothplugin');

        $templateMgr = TemplateManager::getManager($request);

        $submissionContext = $request->getContext();
        if (
            !$submissionContext
            || $submissionContext->getId() !== $this->submission->getData('contextId')
        ) {
            $submissionContext = Services::get('context')->get($this->submission->getData('contextId'));
        }

        $publicationApiUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_API,
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

        $plugin->import('classes.components.forms.RegisterForm');
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
