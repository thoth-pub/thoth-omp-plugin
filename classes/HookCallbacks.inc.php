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

class HookCallbacks
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function createWork($hookName, $args)
    {
        $publication = $args[0];
        $submission = $args[2];
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $dispatcher = $request->getDispatcher();

        import('plugins.generic.thoth.thoth.models.Work');
        $work = new Work();

        $submissionWorkType = $work->getSubmissionWorkType($submission->getData('workType'));
        $urlPublished = $dispatcher->url(
            $request,
            ROUTE_PAGE,
            $context->getPath(),
            'catalog',
            'book',
            $submission->getBestId()
        );

        $work->setWorkType($submissionWorkType);
        $work->setWorkStatus(Work::WORK_STATUS_ACTIVE);
        $work->setFullTitle($submission->getLocalizedFullTitle());
        $work->setTitle($submission->getLocalizedTitle());
        $work->setSubtitle($submission->getLocalizedData('subtitle'));
        $work->setEdition($publication->getData('version'));
        $work->setImprintId($this->plugin->getSetting($context->getId(), 'imprintId'));
        $work->setDoi($publication->getStoredPubId('doi'));
        $work->setPublicationDate($publication->getData('datePublished'));
        $work->setLicense($context->getData('licenseUrl'));
        $work->setCopyrightHolder($publication->getLocalizedData('copyrightHolder'));
        $work->setLandingPage($urlPublished);
        $work->setLongAbstract(strip_tags($publication->getLocalizedData('abstract')));
        $work->setCoverUrl($publication->getLocalizedCoverImageUrl($context->getId()));

        $thothEndpoint = $this->plugin->getSetting($context->getId(), 'apiUrl');
        $email = $this->plugin->getSetting($context->getId(), 'email');
        $password = $this->plugin->getSetting($context->getId(), 'password');

        import('plugins.generic.thoth.lib.APIKeyEncryption.APIKeyEncryption');
        $password = APIKeyEncryption::decryptString($password);

        import('plugins.generic.thoth.thoth.ThothClient');
        $thothClient = new ThothClient($thothEndpoint);

        try {
            $thothClient->login($email, $password);
            $workId = $thothClient->createWork($work);

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
