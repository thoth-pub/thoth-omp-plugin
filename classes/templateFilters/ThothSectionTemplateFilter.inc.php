<?php

/**
 * @file plugins/generic/thoth/classes/filters/ThothSectionFilter.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSectionFilter
 * @ingroup plugins_generic_thoth
 *
 * @brief Template filter to include Thoth section in workflow page
 */

class ThothSectionTemplateFilter
{
    private $plugin;

    public function registerFilter($templateMgr, $template, $plugin)
    {
        if ($template != 'workflow/workflow.tpl') {
            return false;
        }

        $this->plugin = $plugin;

        $templateMgr->registerFilter("output", [$this, 'thothSectionFilter']);

        return false;
    }

    public function thothSectionFilter($output, $templateMgr)
    {
        $regex = '/<span\s+class="pkpPublication__status">([\s\S]*?)<\/span>[^<]+<\/span>/';
        if (preg_match($regex, $output, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0][0];
            $offset = $matches[0][1];
            $newOutput = substr($output, 0, $offset + strlen($match));
            $newOutput .= $templateMgr->fetch($this->plugin->getTemplateResource('thothSection.tpl'));
            $newOutput .= substr($output, $offset + strlen($match));
            $output = $newOutput;
            $templateMgr->unregisterFilter('output', array($this, 'thothSectionFilter'));
        }
        return $output;
    }

    public function addJavaScriptData($request, $templateMgr, $template)
    {
        if ($template != 'workflow/workflow.tpl') {
            return false;
        }

        $submission = $templateMgr->getTemplateVars('submission');

        $registerTitle = __('plugins.generic.thoth.register');
        $registerUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_PAGE,
            null,
            'thoth',
            'register',
            null,
            ['submissionId' => $submission->getId(), 'publicationId' => '__publicationId__']
        );
        $synchronizeUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_API,
            $request->getContext()->getData('urlPath'),
            'submissions/' . $submission->getId() . '/publications/__publicationId__/synchronize'
        );
        $workStatusUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_API,
            $request->getContext()->getData('urlPath'),
            'submissions/' . $submission->getId() . '/thothWorkStatus'
        );
        $unlinkUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_API,
            $request->getContext()->getData('urlPath'),
            'submissions/' . $submission->getId() . '/thothWork'
        );

        $data = [
            'hasLinkedWork' => (bool) $submission->getData('thothWorkId'),
            'registerTitle' => $registerTitle,
            'unlinkTitle' => __('plugins.generic.thoth.unlink'),
            'unlinkConfirm' => __('plugins.generic.thoth.unlink.confirm'),
            'unlinkCancel' => __('common.cancel'),
            'unlinkError' => __('plugins.generic.thoth.connectionError'),
            'registerUrl' => $registerUrl,
            'synchronizeUrl' => $synchronizeUrl,
            'workStatusUrl' => $workStatusUrl,
            'workStatusError' => __('common.error'),
            'workStatusNotFound' => __('plugins.generic.thoth.status.notFound'),
            'workStatusLabels' => [
                'ACTIVE' => __('plugins.generic.thoth.workStatus.active'),
                'FORTHCOMING' => __('plugins.generic.thoth.workStatus.forthcoming'),
                'WITHDRAWN' => __('plugins.generic.thoth.workStatus.withdrawn'),
                'SUPERSEDED' => __('plugins.generic.thoth.workStatus.superseded'),
                'POSTPONED_INDEFINITELY' => __('plugins.generic.thoth.workStatus.postponedIndefinitely'),
                'CANCELLED' => __('plugins.generic.thoth.workStatus.cancelled'),
            ],
            'unlinkUrl' => $unlinkUrl,
        ];

        $output = '$.pkp.plugins.generic = $.pkp.plugins.generic || {};';
        $output .= '$.pkp.plugins.generic.thothplugin = $.pkp.plugins.generic.thothplugin || {};';
        $output .= '$.pkp.plugins.generic.thothplugin.workflow = ' . json_encode($data) . ';';

        $templateMgr->addJavaScript(
            'workflowData',
            $output,
            [
                'inline' => true,
                'contexts' => 'backend',
            ]
        );
    }

    public function addJavaScript($request, $templateMgr, $plugin)
    {
        $templateMgr->addJavaScript(
            'thoth-section-js',
            $request->getBaseUrl() . '/' . $plugin->getPluginPath() . '/js/ThothSection.js',
            [
                'contexts' => 'backend',
                'priority' => STYLE_SEQUENCE_LATE,
            ]
        );
    }

    public function addStyleSheet($request, $templateMgr, $plugin)
    {
        $templateMgr->addStyleSheet(
            'thoth-section-css',
            $request->getBaseUrl() . '/' . $plugin->getPluginPath() . '/styles/thothSection.css',
            ['contexts' => 'backend']
        );
    }
}
