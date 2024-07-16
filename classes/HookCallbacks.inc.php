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

import('plugins.generic.thoth.lib.APIKeyEncryption.APIKeyEncryption');
import('plugins.generic.thoth.thoth.models.Contributor');
import('plugins.generic.thoth.thoth.models.Contribution');
import('plugins.generic.thoth.thoth.models.Work');
import('plugins.generic.thoth.thoth.ThothClient');

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
        $publication = $args[0];
        $submission = $args[2];
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $dispatcher = $request->getDispatcher();

        if ($submission->getData('thothWorkId')) {
            return false;
        }

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

        if (!$email || !$password) {
            return false;
        }

        $password = APIKeyEncryption::decryptString($password);

        try {
            $thothClient = new ThothClient($thothEndpoint);
            $thothClient->login($email, $password);
            $workId = $thothClient->createWork($work);

            foreach ($publication->getData('authors') as $order => $author) {
                $contributor = new Contributor();
                $contributor->setFirstName($author->getLocalizedGivenName());
                $contributor->setLastName($author->getLocalizedFamilyName());
                $contributor->setFullName($author->getFullName(false));
                $contributor->setOrcid($author->getOrcid());
                $contributor->setWebsite($author->getUrl());

                $contributorId = $thothClient->createContributor($contributor);

                $contribution = new Contribution();
                $contributorType = $contribution->getContributionTypeByUserGroup($author->getUserGroup());
                $mainContribution = $publication->getData('primaryContactId') == $author->getId();
                $contribution->setWorkId($workId);
                $contribution->setContributorId($contributorId);
                $contribution->setContributionType($contributorType);
                $contribution->setMainContribution($mainContribution);
                $contribution->setContributionOrdinal($order + 1);
                $contribution->setFirstName($author->getLocalizedGivenName());
                $contribution->setLastName($author->getLocalizedFamilyName());
                $contribution->setFullName($author->getFullName(false));
                $contribution->setBiography($author->getLocalizedBiography());

                $contributorId = $thothClient->createContribution($contribution);
            }

            $submission = Services::get('submission')->edit($submission, ['thothWorkId' => $workId], $request);

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
