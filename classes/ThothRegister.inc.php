<?php

/**
 * @file plugins/generic/thoth/classes/ThothRegister.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothRegister
 * @ingroup plugins_generic_thoth
 *
 * @brief Manage callback functions to register works in Thoth
 */

use ThothApi\Exception\QueryException;

import('plugins.generic.thoth.classes.facades.ThothService');

class ThothRegister
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function validateRegister($hookName, $args)
    {
        $errors = & $args[0];
        $request = Application::get()->getRequest();

        $confirmation = $request->getUserVar('registerConfirmation');
        if (!$confirmation || $confirmation == 'false') {
            return;
        }

        $imprint = $request->getUserVar('imprint');
        if (empty($imprint)) {
            $errors['imprint'] = [__('plugins.generic.thoth.imprint.required')];
        }
    }

    public function addResources($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];
        $request = Application::get()->getRequest();

        if ($template == 'workflow/workflow.tpl') {
            $submission = $templateMgr->getTemplateVars('submission');

            $data = [
                'registerTitle' => __('plugins.generic.thoth.register'),
                'registerUrl' => $request->getDispatcher()->url(
                    $request,
                    ROUTE_PAGE,
                    null,
                    'thoth',
                    'register',
                    null,
                    [
                        'submissionId' => $submission->getId(),
                        'publicationId' => '__publicationId__',
                    ]
                ),
                'publicationUrl' => $request->getDispatcher()->url(
                    $request,
                    ROUTE_API,
                    $request->getContext()->getData('urlPath'),
                    'submissions/' . $submission->getId() . '/publications/__publicationId__'
                )
            ];

            $templateMgr->addJavaScript(
                'workflowData',
                '$.pkp.plugins.generic = $.pkp.plugins.generic || {};' .
                    '$.pkp.plugins.generic.thothplugin = ' . json_encode($data) . ';',
                [
                    'inline' => true,
                    'contexts' => 'backend',
                ]
            );

            $templateMgr->addJavaScript(
                'plugin-thoth-workflow',
                $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/js/Workflow.js',
                [
                    'contexts' => 'backend',
                    'priority' => STYLE_SEQUENCE_LATE,
                ]
            );
        }

        return false;
    }

    public function registerWork($submission, $imprint)
    {
        $request = Application::get()->getRequest();
        $submissionContext = $request->getContext();
        if (!$submissionContext || $submissionContext->getId() !== $submission->getData('contextId')) {
            $submissionContext = Services::get('context')->get($submission->getData('contextId'));
        }

        try {
            $thothBookId = ThothService::book()->register($submission, $imprint);

            $submission = Services::get('submission')->edit(
                $submission,
                ['thothWorkId' => $thothBookId],
                $request
            );

            ThothNotification::notify(
                $request,
                $submission,
                NOTIFICATION_TYPE_SUCCESS,
                'plugins.generic.thoth.register.success'
            );
        } catch (QueryException $e) {
            error_log('Failed to send the request to Thoth: ' . $e->getMessage());
            ThothNotification::notify(
                $request,
                $submission,
                NOTIFICATION_TYPE_ERROR,
                'plugins.generic.thoth.register.error',
                $e->getMessage()
            );
        }
    }

    public function registerOnPublish($hookName, $args)
    {
        $submission = $args[2];
        $request = Application::get()->getRequest();

        if ($submission->getData('thothWorkId')) {
            return false;
        }

        $confirmation = $request->getUserVar('registerConfirmation');
        if (!$confirmation || $confirmation == 'false') {
            return;
        }

        $imprint = $request->getUserVar('imprint');
        $this->registerWork($submission, $imprint);

        return false;
    }

    public function setupHandler($hookName, $params)
    {
        $page = $params[0];
        if ($this->plugin->getEnabled() && $page === 'thoth') {
            $this->plugin->import('controllers/modal/RegisterHandler');
            define('HANDLER_CLASS', 'RegisterHandler');
            return true;
        }
        return false;
    }
}
