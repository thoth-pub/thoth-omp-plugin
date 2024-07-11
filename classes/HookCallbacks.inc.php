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
        $currentUser = $request->getUser();
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

            $notificationMgr = new NotificationManager();
            $notificationMgr->createTrivialNotification(
                $currentUser->getId(),
                NOTIFICATION_TYPE_SUCCESS,
                ['contents' => __('plugins.generic.thoth.settings.registerMetadata')]
            );
        } catch (ThothException $e) {
            error_log($e->getMessage());
            $notificationMgr = new NotificationManager();
            $notificationMgr->createTrivialNotification(
                $currentUser->getId(),
                NOTIFICATION_TYPE_ERROR,
                ['contents' => __('plugins.generic.thoth.settings.registerMetadata.error')]
            );
        }

        return true;
    }
}
