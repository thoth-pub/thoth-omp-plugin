<?php

/**
 * @file plugins/generic/thoth/classes/hooks/HookRegistrant.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class HookRegistrant
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Registers all hooks used by the Thoth plugin
 */

namespace APP\plugins\generic\thoth\classes\hooks;

use APP\core\Application;
use APP\plugins\generic\thoth\classes\api\ThothEndpoint;
use APP\plugins\generic\thoth\classes\components\forms\config\CatalogEntryFormConfig;
use APP\plugins\generic\thoth\classes\components\forms\config\ContributorFormConfig;
use APP\plugins\generic\thoth\classes\components\forms\config\PublishFormConfig;
use APP\plugins\generic\thoth\classes\gridModifier\PublicationFormatGridModifier;
use APP\plugins\generic\thoth\classes\listeners\PublicationEditListener;
use APP\plugins\generic\thoth\classes\listeners\PublicationPublishListener;
use APP\plugins\generic\thoth\classes\notification\ThothNotification;
use APP\plugins\generic\thoth\classes\schema\ThothSchema;
use APP\plugins\generic\thoth\classes\services\ThothCatalogFilesCacheService;
use APP\plugins\generic\thoth\classes\templateFilters\ThothCatalogFilesTemplateFilter;
use APP\plugins\generic\thoth\classes\templateFilters\ThothFeatureVideoTemplateFilter;
use APP\plugins\generic\thoth\classes\templateFilters\ThothFrontcoverTemplateFilter;
use APP\plugins\generic\thoth\classes\templateFilters\ThothSectionTemplateFilter;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class HookRegistrant
{
    private GenericPlugin $plugin;

    public function __construct(GenericPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function register(): void
    {
        $this->registerSchema();
        $this->registerFormConfigs();
        $this->registerLegacyForms();
        $this->registerListeners();
        $this->registerEndpoints();
        $this->registerTemplateHooks();
    }

    private function registerSchema(): void
    {
        $thothSchema = new ThothSchema();
        Hook::add('Schema::get::eventLog', $thothSchema->addReasonToSchema(...));
        Hook::add('Schema::get::submission', $thothSchema->addWorkIdToSchema(...));
        Hook::add('Schema::get::publication', $thothSchema->addToPublicationSchema(...));
        Hook::add('Schema::get::author', $thothSchema->addToAuthorSchema(...));
        Hook::add('Submission::getSubmissionsListProps', $thothSchema->addToSubmissionsListProps(...));
    }

    private function registerFormConfigs(): void
    {
        Hook::add('Form::config::before', (new PublishFormConfig())->addConfig(...));
        Hook::add('Form::config::before', (new CatalogEntryFormConfig())->addConfig(...));
        Hook::add('Form::config::before', (new ContributorFormConfig())->addConfig(...));
    }

    private function registerLegacyForms(): void
    {
        $publicationFormatFormHandler = new PublicationFormatFormHandler($this->plugin);
        Hook::add(
            'publicationformatdao::getAdditionalFieldNames',
            $publicationFormatFormHandler->addAccessibilityFieldNames(...)
        );
        Hook::add('publicationformatform::display', $publicationFormatFormHandler->addAccessibilityFields(...));
        Hook::add('publicationformatform::readuservars', $publicationFormatFormHandler->addAccessibilityUserVars(...));
        Hook::add('publicationformatform::validate', $publicationFormatFormHandler->validateAccessibilityFields(...));
        Hook::add('publicationformatform::execute', $publicationFormatFormHandler->saveAccessibilityFields(...));
    }

    private function registerListeners(): void
    {
        $publicationPublishListener = new PublicationPublishListener();
        Hook::add('Publication::validatePublish', $publicationPublishListener->validate(...));
        Hook::add('Publication::publish', $publicationPublishListener->registerThothBook(...));

        $publicationEditListener = new PublicationEditListener();
        Hook::add('Publication::edit', $publicationEditListener->updateThothBook(...));
    }

    private function registerEndpoints(): void
    {
        Hook::add('APIHandler::endpoints::_submissions', (new ThothEndpoint())->addEndpoints(...));
    }

    private function registerTemplateHooks(): void
    {
        $thothMenuHandler = new ThothMenuHandler();
        $thothPageHandler = new ThothPageHandler($this->plugin);
        $publicationFormatGridModifier = new PublicationFormatGridModifier($this->plugin);
        $publicationFormatGridModifier->register();

        Hook::add('TemplateManager::display', $this->addTemplateFilters(...));
        Hook::add('TemplateManager::display', $this->addScripts(...));
        Hook::add('TemplateManager::display', $thothMenuHandler->addMenu(...));
        Hook::add('LoadHandler', $thothPageHandler->addHandlers(...));
    }

    private function addTemplateFilters($hookName, $args): bool
    {
        $templateMgr = $args[0];
        $template = $args[1];

        $thothCatalogFilesFilter = new ThothCatalogFilesTemplateFilter();
        $thothCatalogFilesFilter->registerFilter($templateMgr, $template);

        $thothFrontcoverFilter = new ThothFrontcoverTemplateFilter();
        $thothFrontcoverFilter->registerFilter($templateMgr, $template);

        $thothFeatureVideoFilter = new ThothFeatureVideoTemplateFilter();
        $thothFeatureVideoFilter->registerFilter($templateMgr, $template);

        return false;
    }

    private function addScripts($hookName, $args): void
    {
        $templateMgr = $args[0];
        $template = $args[1];
        $request = Application::get()->getRequest();

        $thothSectionFilter = new ThothSectionTemplateFilter();
        $thothSectionFilter->addJavaScriptData($request, $templateMgr, $template);
        $thothSectionFilter->addJavaScript($request, $templateMgr, $this->plugin);
        $thothSectionFilter->addStyleSheet($request, $templateMgr, $this->plugin);

        $thothNotification = new ThothNotification();
        $thothNotification->addJavaScriptData($request, $templateMgr);
        $thothNotification->addJavaScript($request, $templateMgr, $this->plugin);

        $this->addCatalogFilesAssets($request, $templateMgr, $template);
    }

    private function addCatalogFilesAssets($request, $templateMgr, $template): bool
    {
        if ($template !== 'frontend/pages/book.tpl') {
            return false;
        }

        $monograph = $templateMgr->getTemplateVars('publishedSubmission')
            ?: $templateMgr->getTemplateVars('monograph');
        $publication = $templateMgr->getTemplateVars('publication');
        $chapters = (array) $templateMgr->getTemplateVars('chapters');

        if (!$monograph || !$publication || !$monograph->getData('thothWorkId')) {
            return false;
        }

        $catalogFilesUrl = $request->getDispatcher()->url(
            $request,
            Application::ROUTE_PAGE,
            null,
            'thoth',
            'catalogFiles',
            null,
            [
                'submissionId' => $monograph->getId(),
                'publicationId' => $publication->getId(),
            ]
        );
        $catalogFilesCacheService = new ThothCatalogFilesCacheService();

        $templateMgr->addJavaScript(
            'thoth-catalog-files-data',
            'window.thothCatalogFiles = ' . json_encode([
                'url' => $catalogFilesUrl,
                'downloadsLabel' => __('submission.downloads'),
                'loadingLabel' => __('common.loading'),
                'chapters' => $this->getCatalogFilesChapterData($chapters),
                'cacheTtl' => ThothCatalogFilesCacheService::TTL,
                'cacheKeySuffix' => $catalogFilesCacheService->getClientCacheKeySuffix($publication->getId()),
            ]) . ';',
            [
                'inline' => true,
                'contexts' => 'frontend',
            ]
        );
        $templateMgr->addJavaScript(
            'thoth-catalog-files-js',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/js/ThothCatalogFiles.js',
            [
                'contexts' => 'frontend',
                'priority' => STYLE_SEQUENCE_LATE,
            ]
        );

        return false;
    }

    private function getCatalogFilesChapterData($chapters): array
    {
        return array_map(function ($chapter) {
            return [
                'id' => (int) $chapter->getId(),
            ];
        }, array_values($chapters));
    }

}
