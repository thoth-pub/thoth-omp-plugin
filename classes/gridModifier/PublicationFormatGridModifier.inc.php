<?php

/**
 * @file plugins/generic/thoth/classes/gridModifier/PublicationFormatGridModifier.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationFormatGridModifier
 * @ingroup plugins_generic_thoth
 *
 * @brief A class to modify the publication format grid by adding Thoth features.
 */

class PublicationFormatGridModifier
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        HookRegistry::register('TemplateManager::fetch', [$this, 'addThothActions']);
    }

    public function addThothActions($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];

        if ($template !== 'controllers/grid/gridCell.tpl') {
            return;
        }

        $actions = $templateMgr->getTemplateVars('actions');
        if (!is_array($actions) || count($actions) != 2) {
            return;
        }

        $request = Application::get()->getRequest();
        $router = $request->getRouter();
        $handler = $router->getHandler();
        if (!($handler instanceof PublicationFormatGridHandler)) {
            return;
        }

        $submission = $handler->getSubmission();
        $thothWorkId = $submission->getData('thothWorkId');

        if (!$thothWorkId) {
            return;
        }

        $categoryRow = $templateMgr->getTemplateVars('categoryRow');
        $publicationFormat = $categoryRow->getData();

        $actionArgs = [
            'representationId' => $publicationFormat->getId(),
            'publicationId' => $publicationFormat->getData('publicationId'),
            'thothWorkId' => $thothWorkId
        ];

        import('lib.pkp.classes.linkAction.request.AjaxModal');
        $linkAction = new LinkAction(
            'thothUpload',
            new AjaxModal(
                $request->getDispatcher()->url(
                    $request,
                    ROUTE_PAGE,
                    null,
                    'thoth',
                    'uploadThothPublicationFile',
                    null,
                    $actionArgs
                ),
                __('plugins.generic.thoth.grid.action.thothFileUpload')
            ),
            __('plugins.generic.thoth.grid.action.thothFileUpload'),
            null
        );

        array_push($actions, $linkAction);
        $templateMgr->assign('actions', $actions);
    }
}
