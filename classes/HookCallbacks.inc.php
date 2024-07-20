<?php

/**
 * @file plugins/generic/thoth/HookCallbacks.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class HookCallbacks
 * @ingroup plugins_generic_thoth
 *
 * @brief Manage callback functions for the plugin hooks
 */

import('plugins.generic.thoth.classes.services.ThothService');

class HookCallbacks
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function addWorkIdToSchema($hookName, $args)
    {
        $schema = & $args[0];
        $schema->properties->{'thothWorkId'} = (object) [
            'type' => 'string',
            'apiSummary' => true,
            'validation' => ['nullable'],
        ];
        return false;
    }

    public function createWork($hookName, $args)
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $submission = $args[2];

        if ($submission->getData('thothWorkId')) {
            return false;
        }

        try {
            $thothService = new ThothService($this->plugin, $context->getId());
            $book = $thothService->registerBook($submission);
            $submission = Services::get('submission')->edit($submission, ['thothWorkId' => $book->getId()], $request);

            $this->notify(
                $request,
                NOTIFICATION_TYPE_SUCCESS,
                __('plugins.generic.thoth.settings.registerMetadata')
            );
        } catch (ThothException $e) {
            error_log($e->getMessage());
            $this->notify(
                $request,
                NOTIFICATION_TYPE_ERROR,
                __('plugins.generic.thoth.settings.registerMetadata.error')
            );
        }

        return false;
    }

    private function notify($request, $notificationType, $message)
    {
        $currentUser = $request->getUser();
        $notificationMgr = new NotificationManager();
        $notificationMgr->createTrivialNotification(
            $currentUser->getId(),
            $notificationType,
            ['contents' => $message]
        );
        return new JSONMessage(false);
    }

    public function addJavaScripts($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];
        $request = Application::get()->getRequest();

        if ($template == 'workflow/workflow.tpl') {
            $data = [];
            $data['notificationUrl'] = $request->url(null, 'notification', 'fetchNotification');

            $templateMgr->addJavaScript(
                'workflowData',
                '$.pkp.plugins.generic = $.pkp.plugins.generic || {};' .
                    '$.pkp.plugins.generic.' . strtolower(get_class($this->plugin)) . ' = ' . json_encode($data) . ';',
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
}
