<?php

/**
 * @file plugins/generic/thoth/ThothPlugin.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPlugin
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Plugin for integration with Thoth for communication and synchronization of book data between the two platforms
 */

require_once(__DIR__ . '/vendor/autoload.php');

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.thoth.classes.api.ThothEndpoint');
import('plugins.generic.thoth.classes.components.forms.config.CatalogEntryFormConfig');
import('plugins.generic.thoth.classes.components.forms.config.PublishFormConfig');
import('plugins.generic.thoth.classes.formModifiers.AuthorFormModifier');
import('plugins.generic.thoth.classes.formModifiers.PublicationFormatFormModifier');
import('plugins.generic.thoth.classes.listeners.PublicationEditListener');
import('plugins.generic.thoth.classes.listeners.PublicationPublishListener');
import('plugins.generic.thoth.classes.notification.ThothNotification');
import('plugins.generic.thoth.classes.schema.ThothSchema');
import('plugins.generic.thoth.classes.services.ThothCatalogFilesCacheService');
import('plugins.generic.thoth.classes.templateFilters.ThothCatalogFilesTemplateFilter');
import('plugins.generic.thoth.classes.templateFilters.ThothFrontcoverTemplateFilter');
import('plugins.generic.thoth.classes.templateFilters.PublicationFormatTemplateFilter');
import('plugins.generic.thoth.classes.templateFilters.ThothSectionTemplateFilter');
import('plugins.generic.thoth.classes.templateFilters.ThothFeatureVideoWorkflowTemplateFilter');

class ThothPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
            HookRegistry::register('TemplateManager::display', [$this, 'addScripts']);
            HookRegistry::register('TemplateManager::display', [$this, 'addTemplateFilters']);
            HookRegistry::register('TemplateManager::display', [$this, 'addMenu']);
            HookRegistry::register('LoadHandler', [$this, 'addHandlers']);

            $this->addToSchema();
            $this->addFormConfig();
            $this->addFormModifiers();
            $this->addEndpoints();
            $this->addListeners();

            import('plugins.generic.thoth.classes.gridModifier.PublicationFormatGridModifier');
            $publicationFormatGridModifier = new PublicationFormatGridModifier($this);
        }

        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.thoth.name');
    }

    public function getDescription()
    {
        return __('plugins.generic.thoth.description');
    }

    public function getActions($request, $verb)
    {
        $parentActions = parent::getActions($request, $verb);

        if (!$this->getEnabled()) {
            return $parentActions;
        }

        $router = $request->getRouter();

        import('lib.pkp.classes.linkAction.request.AjaxModal');
        $linkAction = new LinkAction(
            'settings',
            new AjaxModal(
                $router->url(
                    $request,
                    null,
                    null,
                    'manage',
                    null,
                    [
                        'verb' => 'settings',
                        'plugin' => $this->getName(),
                        'category' => 'generic'
                    ]
                ),
                $this->getDisplayName()
            ),
            __('manager.plugins.settings'),
            null
        );

        array_unshift($parentActions, $linkAction);

        return $parentActions;
    }

    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();

                $this->import('ThothSettingsForm');
                $form = new ThothSettingsForm($this, $context->getId());

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }
                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    public function addTemplateFilters($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];

        $thothSectionFilter = new ThothSectionTemplateFilter();
        $thothSectionFilter->registerFilter($templateMgr, $template, $this);

        $thothCatalogFilesFilter = new ThothCatalogFilesTemplateFilter();
        $thothCatalogFilesFilter->registerFilter($templateMgr, $template);

        $thothFrontcoverFilter = new ThothFrontcoverTemplateFilter();
        $thothFrontcoverFilter->registerFilter($templateMgr, $template);

        $featureVideoWorkflowFilter = new ThothFeatureVideoWorkflowTemplateFilter();
        $featureVideoWorkflowFilter->registerFilter($templateMgr, $template);
    }

    public function addToSchema()
    {
        $thothSchema = new ThothSchema();
        HookRegistry::register('Schema::get::author', [$thothSchema, 'addToAuthorSchema']);
        HookRegistry::register('Schema::get::submission', [$thothSchema, 'addWorkIdToSchema']);
        HookRegistry::register('Schema::get::publication', [$thothSchema, 'addToPublicationSchema']);
        HookRegistry::register('authordao::getAdditionalFieldNames', [$thothSchema, 'addToAdditionalFieldNames']);
        HookRegistry::register('Submission::getBackendListProperties::properties', [$thothSchema, 'addToBackendProps']);
    }

    public function addFormConfig()
    {
        $publishFormConfig = new PublishFormConfig();
        HookRegistry::register('Form::config::before', [$publishFormConfig, 'addConfig']);

        $catalogEntryFormConfig = new CatalogEntryFormConfig();
        HookRegistry::register('Form::config::before', [$catalogEntryFormConfig, 'addConfig']);
    }

    public function addFormModifiers()
    {
        $authorFormModifier = new AuthorFormModifier($this);
        HookRegistry::register('authorform::Constructor', [$authorFormModifier, 'handleFormConstructor']);
        HookRegistry::register('authorform::display', [$authorFormModifier, 'handleFormDisplay']);
        HookRegistry::register('authorform::execute', [$authorFormModifier, 'handleFormExecute']);

        $publicationFormatFormModifier = new PublicationFormatFormModifier($this);
        HookRegistry::register(
            'publicationformatdao::getAdditionalFieldNames',
            [$publicationFormatFormModifier, 'addAccessibilityFieldNames']
        );
        HookRegistry::register('publicationformatform::display', [$publicationFormatFormModifier, 'handleFormDisplay']);
        HookRegistry::register('publicationformatform::readuservars', [$publicationFormatFormModifier, 'handleFormReadUserVars']);
        HookRegistry::register('publicationformatform::validate', [$publicationFormatFormModifier, 'handleFormValidate']);
        HookRegistry::register('publicationformatform::execute', [$publicationFormatFormModifier, 'handleFormExecute']);
    }

    public function addEndpoints()
    {
        $thothEndpoint = new ThothEndpoint();
        HookRegistry::register('APIHandler::endpoints', [$thothEndpoint, 'addEndpoints']);
    }

    public function addScripts($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];
        $request = Application::get()->getRequest();

        $thothNotification = new ThothNotification();
        $thothNotification->addJavaScriptData($request, $templateMgr);
        $thothNotification->addJavaScript($request, $templateMgr, $this);

        $thothSectionFilter = new ThothSectionTemplateFilter();
        $thothSectionFilter->addJavaScriptData($request, $templateMgr, $template);
        $thothSectionFilter->addJavaScript($request, $templateMgr, $this);
        $thothSectionFilter->addStyleSheet($request, $templateMgr, $this);

        $featureVideoWorkflowFilter = new ThothFeatureVideoWorkflowTemplateFilter();
        $featureVideoWorkflowFilter->addFormConfig($request, $templateMgr, $template);

        $this->addCatalogFilesAssets($request, $templateMgr, $template);
    }

    public function addCatalogFilesAssets($request, $templateMgr, $template)
    {
        if ($template !== 'frontend/pages/book.tpl') {
            return false;
        }

        $monograph = $templateMgr->getTemplateVars('publishedSubmission');
        $publication = $templateMgr->getTemplateVars('publication');
        $chapters = $templateMgr->getTemplateVars('chapters') ?: [];
        $availableFiles = $templateMgr->getTemplateVars('availableFiles') ?: [];

        if (!$monograph || !$publication) {
            return false;
        }

        $catalogFilesUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_PAGE,
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
                'publicationFormatFiles' => $this->getCatalogFilesPublicationFormatFileData($availableFiles),
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
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/ThothCatalogFiles.js',
            [
                'contexts' => 'frontend',
                'priority' => STYLE_SEQUENCE_LATE,
            ]
        );
        $templateMgr->addStyleSheet(
            'thoth-catalog-files-css',
            '.thoth_files:empty { display: none; } .thoth_files_loading { color: #666; font-size: 0.93em; }',
            [
                'inline' => true,
                'contexts' => 'frontend',
            ]
        );

        return false;
    }

    private function getCatalogFilesChapterData($chapters)
    {
        return array_map(function ($chapter) {
            return [
                'id' => (int) $chapter->getId(),
                'title' => $chapter->getLocalizedTitle(),
            ];
        }, array_values($chapters));
    }

    private function getCatalogFilesPublicationFormatFileData($availableFiles)
    {
        $publicationFormatFiles = [];

        foreach ($availableFiles as $file) {
            if (!$this->isCatalogFilesMonographFile($file)) {
                continue;
            }

            $publicationFormatId = $file->getData('assocId');
            if (!$publicationFormatId) {
                continue;
            }

            if (!isset($publicationFormatFiles[$publicationFormatId])) {
                $publicationFormatFiles[$publicationFormatId] = [];
            }

            $publicationFormatFiles[$publicationFormatId][] = $file->getLocalizedData('name');
        }

        return $publicationFormatFiles;
    }

    private function isCatalogFilesMonographFile($file)
    {
        return method_exists($file, 'getChapterId') && !$file->getChapterId();
    }

    public function addListeners()
    {
        $publicationPublishListener = new PublicationPublishListener();
        HookRegistry::register('Publication::validatePublish', [$publicationPublishListener, 'validate']);
        HookRegistry::register('Publication::publish', [$publicationPublishListener, 'registerThothBook']);

        $publicationEditListener = new PublicationEditListener();
        HookRegistry::register('Publication::edit', [$publicationEditListener, 'updateThothBook']);
    }

    public function addHandlers($hookName, $args)
    {
        $page = $args[0];
        $op = $args[1];

        if (!$this->getEnabled() || $page !== 'thoth') {
            return false;
        }

        if ($op === 'register') {
            $this->import('controllers/modal/RegisterHandler');
            define('HANDLER_CLASS', 'RegisterHandler');
            return true;
        }

        if ($op === 'index') {
            $this->import('pages/thoth/ThothHandler');
            define('HANDLER_CLASS', 'ThothHandler');
            return true;
        }

        if ($op === 'catalogFiles') {
            $this->import('pages/thoth/ThothCatalogFilesHandler');
            define('HANDLER_CLASS', 'ThothCatalogFilesHandler');
            return true;
        }

        if (in_array(
            $op,
            [
                'uploadThothPublicationFile',
                'handleThothPublicationFile',
                'saveUploadThothPublicationFile',
                'viewThothPublicationFormatFiles',
            ]
        )) {
            $this->import('controllers/fileUpload/UploadThothFileHandler');
            define('HANDLER_CLASS', 'UploadThothFileHandler');
            return true;
        }

        return false;
    }

    public function addMenu($hookName, $args)
    {
        $templateMgr = $args[0];

        $request = Application::get()->getRequest();
        $router = $request->getRouter();
        $userRoles = (array) $router->getHandler()->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

        $menu = $templateMgr->getState('menu');

        if (empty($menu)) {
            return false;
        }

        if (in_array(ROLE_ID_MANAGER, $userRoles)) {
            $menu = array_slice($menu, 0, 2, true) +
            [
                'thoth' => [
                    'name' => __('plugins.generic.thoth.navigation.thoth'),
                    'url' => $router->url($request, null, 'thoth'),
                    'isCurrent' => $router->getRequestedPage($request) === 'thoth',
                ]
            ] +
            array_slice($menu, 2, null, true);
        }

        $templateMgr->setState(['menu' => $menu]);
    }

}
