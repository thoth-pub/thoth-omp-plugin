<?php

/**
 * @file plugins/generic/thoth/HookCallbacks.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class HookCallbacks
 * @ingroup plugins_generic_thoth
 *
 * @brief Manage callback functions for the plugin hooks
 */

import('plugins.generic.thoth.classes.facades.ThothService');

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

    public function createWork($hookName, $args)
    {
        $request = Application::get()->getRequest();
        $contextId = $request->getContext()->getId();
        $submission = $args[2];

        if ($submission->getData('thothWorkId')) {
            return false;
        }

        $thothImprintId = $this->plugin->getSetting($contextId, 'imprintId');
        try {
            $thothClient = $this->plugin->getThothClient($contextId);
            $thothBook = ThothService::work()->registerBook($thothClient, $submission, $thothImprintId);
            $submission = Services::get('submission')->edit(
                $submission,
                ['thothWorkId' => $thothBook->getId()],
                $request
            );

            $this->notify(
                $request,
                NOTIFICATION_TYPE_SUCCESS,
                __('plugins.generic.thoth.registerMetadata')
            );
        } catch (ThothException $e) {
            error_log($e->getMessage());
            $this->notify(
                $request,
                NOTIFICATION_TYPE_ERROR,
                __('plugins.generic.thoth.registerMetadata.error')
            );
        }

        return false;
    }

    public function updateWork($hookName, $args)
    {
        $publication = $args[0];
        $params = $args[2];
        $request = $args[3];

        $submission = Services::get('submission')->get($publication->getData('submissionId'));
        $submissionLocale = $submission->getLocale();
        $thothWorkId = $submission->getData('thothWorkId');

        if (!$thothWorkId) {
            return false;
        }

        try {
            $thothClient = $this->plugin->getThothClient($submission->getData('contextId'));
            $thothWork = ThothService::work()->get($thothClient, $thothWorkId);
            ThothService::work()->update($thothClient, $thothWork, $params, $submissionLocale);

            $this->notify(
                $request,
                NOTIFICATION_TYPE_SUCCESS,
                __('plugins.generic.thoth.updateMetadata')
            );
        } catch (ThothException $e) {
            error_log($e->getMessage());
            $this->notify(
                $request,
                NOTIFICATION_TYPE_ERROR,
                __('plugins.generic.thoth.updateMetadata.error')
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
}
