<?php

/**
 * @file plugins/generic/thoth/classes/filters/ThothSectionTemplateFilter.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSectionTemplateFilter
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Provide data for the Thoth section in the workflow page
 */

namespace APP\plugins\generic\thoth\classes\templateFilters;

class ThothSectionTemplateFilter
{
    private $plugin;

    public function registerFilter($templateMgr, $template, $plugin)
    {
        return false;
    }

    public function addJavaScriptData($request, $templateMgr, $template)
    {
        if ($template != 'dashboard/editors.tpl') {
            return false;
        }

        $registerTitle = __('plugins.generic.thoth.register');
        $registerUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_PAGE,
            null,
            'thoth',
            'register',
            null,
            ['submissionId' => '__submissionId__', 'publicationId' => '__publicationId__']
        );
        $publicationUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_API,
            $request->getContext()->getData('urlPath'),
            'submissions/__submissionId__/publications/__publicationId__'
        );
        $workStatusUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_API,
            $request->getContext()->getData('urlPath'),
            '_submissions/__submissionId__/thothWorkStatus'
        );

        $data = [
            'registerTitle' => $registerTitle,
            'registerUrl' => $registerUrl,
            'publicationUrl' => $publicationUrl,
            'workStatusUrl' => $workStatusUrl
        ];

        $output = 'pkp.plugins = pkp.plugins || {};';
        $output .= 'pkp.plugins.generic = pkp.plugins.generic || {};';
        $output .= 'pkp.plugins.generic.thoth = pkp.plugins.generic.thoth || {};';
        $output .= 'pkp.plugins.generic.thoth.workflow = ' . json_encode($data) . ';';

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
            'thothPlugin',
            $request->getBaseUrl() . '/' . $plugin->getPluginPath() . '/public/build/build.iife.js',
            [
                'inline' => false,
                'contexts' => ['backend'],
                'priority' => STYLE_SEQUENCE_LAST,
            ]
        );
    }

    public function addStyleSheet($request, $templateMgr, $plugin)
    {
        $cssFile = $plugin->getPluginPath() . '/public/build/build.css';
        if (file_exists(BASE_SYS_DIR . '/' . $cssFile)) {
            $templateMgr->addStyleSheet(
                'thothPluginStyle',
                $request->getBaseUrl() . '/' . $cssFile,
                ['contexts' => ['backend']]
            );
        }
    }
}
