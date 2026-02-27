<?php

/**
 * @file plugins/generic/thoth/classes/hooks/HookRegistrant.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
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
use APP\plugins\generic\thoth\classes\listeners\PublicationEditListener;
use APP\plugins\generic\thoth\classes\listeners\PublicationPublishListener;
use APP\plugins\generic\thoth\classes\notification\ThothNotification;
use APP\plugins\generic\thoth\classes\schema\ThothSchema;
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
        Hook::add('APIHandler::endpoints::plugin', (new ThothEndpoint())->addEndpoints(...));
    }

    private function registerTemplateHooks(): void
    {
        $thothMenuHandler = new ThothMenuHandler();
        $thothPageHandler = new ThothPageHandler($this->plugin);

        Hook::add('TemplateManager::display', $this->addTemplateFilters(...));
        Hook::add('TemplateManager::display', $this->addScripts(...));
        Hook::add('TemplateManager::display', $thothMenuHandler->addMenu(...));
        Hook::add('LoadHandler', $thothPageHandler->addHandlers(...));
    }

    private function addTemplateFilters($hookName, $args): void
    {
        $templateMgr = $args[0];
        $template = $args[1];

        $thothSectionFilter = new ThothSectionTemplateFilter();
        $thothSectionFilter->registerFilter($templateMgr, $template, $this->plugin);
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
    }
}
