<?php

/**
 * @file plugins/generic/thoth/classes/ThothBadgeRender.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBadgeRender
 * @ingroup plugins_generic_thoth
 *
 * @brief Manage callback functions render Thoth badge in Workflow page
 */

class ThothBadgeRender
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function addThothBadge($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];
        $request = Application::get()->getRequest();

        if ($template != 'workflow/workflow.tpl') {
            return false;
        }

        $templateMgr->addStyleSheet(
            'plugin-thoth-workflow_css',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/styles/badge.css',
            [
                'contexts' => 'backend'
            ]
        );

        $templateMgr->registerFilter("output", array($this, 'thothBadgeFilter'));

        return false;
    }

    public function thothBadgeFilter($output, $templateMgr)
    {
        $regex = '/<span\s+class="pkpPublication__status">([\s\S]*?)<\/span>[^<]+<\/span>/';
        if (preg_match($regex, $output, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0][0];
            $offset = $matches[0][1];
            $newOutput = substr($output, 0, $offset + strlen($match));
            $newOutput .= $templateMgr->fetch($this->plugin->getTemplateResource('thothBadge.tpl'));
            $newOutput .= substr($output, $offset + strlen($match));
            $output = $newOutput;
            $templateMgr->unregisterFilter('output', array($this, 'thothBadgeFilter'));
        }
        return $output;
    }
}
