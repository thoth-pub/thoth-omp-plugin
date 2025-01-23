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
 * @ingroup plugins_generic_thoth
 *
 * @brief A handler to load Thoth register confirmation
 */

use APP\components\forms\publication\PublishForm;
use ThothApi\Exception\QueryException;

import('classes.handler.Handler');

class RegisterHandler extends Handler
{
    public $submission;

    public $publication;

    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT],
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
            'submissions/' . $this->submission->getId() . '/publications/' . $this->publication->getId() . '/register'
        );

        $imprints = [];
        $errors = [];

        try {
            $thothClient = ThothContainer::getInstance()->get('client');
            $thothAccountDetails = $thothClient->accountDetails();
            $publishers = $thothAccountDetails['resourceAccess']['linkedPublishers'];
            $imprints = $thothClient->imprints(['publishers' => array_column($publishers, 'publisherId')]);
            $errors = array_merge(ThothValidator::validate($this->submission), $errors);
        } catch (QueryException $e) {
            $errors[] = __('plugins.generic.thoth.connectionError');
            error_log('Failed to send the request to Thoth: ' . $e->getMessage());
        }

        $plugin->import('classes.components.forms.RegisterForm');
        $registerForm = new RegisterForm($publicationApiUrl, $imprints, $errors);

        $settingsData = [
            'components' => [
                'register' => $registerForm->getConfig(),
            ],
        ];

        $templateMgr->assign('registerData', $settingsData);

        return $templateMgr->fetchJson($plugin->getTemplateResource('register.tpl'));
    }
}
