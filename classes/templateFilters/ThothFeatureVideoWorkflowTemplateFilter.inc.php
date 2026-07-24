<?php

/**
 * @file plugins/generic/thoth/classes/templateFilters/ThothFeatureVideoWorkflowTemplateFilter.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothFeatureVideoWorkflowTemplateFilter
 * @ingroup plugins_generic_thoth
 *
 * @brief Adds the featured video form to the legacy OMP workflow.
 */

import('plugins.generic.thoth.classes.components.forms.FeatureVideoForm');
import('plugins.generic.thoth.classes.facades.ThothRepo');
import('plugins.generic.thoth.classes.services.ThothMeCacheService');

class ThothFeatureVideoWorkflowTemplateFilter
{
    public function registerFilter($templateMgr, $template)
    {
        if ($template !== 'workflow/workflow.tpl') {
            return false;
        }

        $templateMgr->registerFilter('output', [$this, 'addTab']);
        return false;
    }

    public function addTab($output, $templateMgr = null)
    {
        $tab = '<tab id="featureVideo" label="'
            . htmlspecialchars(__('plugins.generic.thoth.featureVideo'), ENT_QUOTES, 'UTF-8')
            . '"><pkp-form v-bind="components.featureVideo" @set="set" /></tab>';

        return preg_replace(
            '/(<tab id="publicationDates"[\s\S]*?<\/tab>)/',
            '$1' . $tab,
            $output,
            1
        );
    }

    public function addFormConfig($request, $templateMgr, $template)
    {
        if ($template !== 'workflow/workflow.tpl') {
            return false;
        }

        $submission = $templateMgr->getTemplateVars('submission');
        $dispatcher = $request->getDispatcher();
        $contextPath = $request->getContext()->getData('urlPath');
        $action = $dispatcher->url(
            $request,
            ROUTE_API,
            $contextPath,
            'submissions/' . $submission->getId() . '/featureVideo'
        );
        $temporaryFilesUrl = $dispatcher->url(
            $request,
            ROUTE_API,
            $contextPath,
            'temporaryFiles'
        );

        $components = $templateMgr->getState('components');
        $components[FeatureVideoForm::FORM_FEATURE_VIDEO] = (new FeatureVideoForm(
            $action,
            $temporaryFilesUrl,
            $this->canUploadSafely($request),
            $this->hasExistingVideoSafely($submission)
        ))->getConfig();
        $templateMgr->setState(['components' => $components]);

        return false;
    }

    private function hasExistingVideoSafely($submission)
    {
        try {
            return $this->hasExistingVideo($submission);
        } catch (Throwable $exception) {
            return false;
        }
    }

    protected function hasExistingVideo($submission)
    {
        return (bool) ($submission->getData('thothWorkId')
            ? ThothRepo::work()->getFeatureVideo($submission->getData('thothWorkId'))
            : null);
    }

    private function canUploadSafely($request)
    {
        try {
            return $this->canUpload($request);
        } catch (Throwable $exception) {
            return false;
        }
    }

    protected function canUpload($request)
    {
        return (new ThothMeCacheService(ThothRepo::me()))
            ->hasCdnWritePermission($request->getContext()->getId());
    }
}
