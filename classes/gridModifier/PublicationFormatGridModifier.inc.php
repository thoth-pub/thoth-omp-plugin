<?php

/**
 * @file plugins/generic/thoth/classes/gridModifier/PublicationFormatGridModifier.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationFormatGridModifier
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A class to modify the publication format grid by adding Thoth features.
 */

use APP\controllers\grid\catalogEntry\PublicationFormatGridHandler;
use PKP\security\Role;

class PublicationFormatGridModifier
{
    private $plugin;

    private $thothFilesActions = [];

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        HookRegistry::register('TemplateManager::fetch', [$this, 'addThothActions']);
        HookRegistry::register('TemplateManager::fetch', [$this, 'addThothFilesColumn']);
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

        if (!$this->canManagePublicationFormats($handler)) {
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

        $title = __('plugins.generic.thoth.grid.action.thothFileUpload');

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
                $title
            ),
            $title,
            null
        );

        array_push($actions, $linkAction);
        $templateMgr->assign('actions', $actions);
    }

    public function addThothFilesColumn($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];

        if ($template !== 'controllers/grid/grid.tpl') {
            return;
        }

        $request = Application::get()->getRequest();
        $router = $request->getRouter();
        $handler = $router->getHandler();
        if (!($handler instanceof PublicationFormatGridHandler)) {
            return;
        }

        if (!$this->canManagePublicationFormats($handler)) {
            return;
        }

        $submission = $handler->getSubmission();
        if (!$submission || !$submission->getData('thothWorkId')) {
            return;
        }

        $this->thothFilesActions = [];
        $templateMgr->registerFilter('output', [$this, 'injectThothFilesColumn']);
    }

    public function injectThothFilesColumn($output, $templateMgr)
    {
        $request = Application::get()->getRequest();

        $templateMgr->unregisterFilter('output', [$this, 'injectThothFilesColumn']);

        $output = $this->injectThothFilesColumnGroups($output);
        $output = $this->injectThothFilesHeader($output);
        $output = $this->injectThothFilesBodyCells($output);
        $output = $this->injectThothFilesCategoryActions($output, $templateMgr, $request);
        $output = $this->increaseColspans($output);

        return $output;
    }

    private function canManagePublicationFormats($handler)
    {
        $userRoles = $handler->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

        return !empty(array_intersect(
            $userRoles,
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT]
        ));
    }

    private function injectThothFilesColumnGroups($output)
    {
        return preg_replace(
            '/(<col class="grid-column column-name"[^>]*\/>)/',
            '$1<col class="grid-column column-thothFiles" style="width: 15%;" />',
            $output
        );
    }

    private function injectThothFilesHeader($output)
    {
        return preg_replace(
            '/(<thead>\s*<tr>\s*<th\b[\s\S]*?<\/th>)/',
            '$1<th scope="col" style="text-align: left;">' .
                htmlspecialchars(__('plugins.generic.thoth.grid.column.thothFiles'), ENT_QUOTES, 'UTF-8') .
                '</th>',
            $output,
            1
        );
    }

    private function injectThothFilesBodyCells($output)
    {
        return preg_replace(
            '/(<tr\b[^>]*class="[^"]*\bgridRow\b[^"]*"[^>]*>[\s\S]*?<\/td>)/',
            '$1<td></td>',
            $output
        );
    }

    private function injectThothFilesCategoryActions($output, $templateMgr, $request)
    {
        return preg_replace_callback(
            '/(<tbody id="[^"]*-category-(\d+)"[^>]*>\s*<tr\b[^>]*class="[^"]*\bcategory\b[^"]*"[^>]*>[\s\S]*?' .
            '<td>)(<\/td>)/',
            function ($matches) use ($templateMgr, $request) {
                $actionHtml = $this->getThothFilesActionHtml((int) $matches[2], $templateMgr, $request);
                return $matches[1] . $actionHtml . $matches[3];
            },
            $output
        );
    }

    private function increaseColspans($output)
    {
        return preg_replace_callback('/colspan="(\d+)"/', function ($matches) {
            return 'colspan="' . ((int) $matches[1] + 1) . '"';
        }, $output);
    }

    private function getThothFilesActionHtml($representationId, $templateMgr, $request)
    {
        if (!isset($this->thothFilesActions[$representationId])) {
            $this->thothFilesActions[$representationId] = $this->renderThothFilesAction(
                $representationId,
                $templateMgr,
                $request
            );
        }

        return $this->thothFilesActions[$representationId];
    }

    private function renderThothFilesAction($representationId, $templateMgr, $request)
    {
        $router = $request->getRouter();
        $handler = $router->getHandler();
        $submission = $handler->getSubmission();
        $publication = $handler->getPublication();
        $cellId = 'cell-thothPublicationFormatFiles-' . $representationId;
        $title = __('plugins.generic.thoth.grid.action.viewThothFiles');

        import('lib.pkp.classes.linkAction.request.AjaxModal');
        $linkAction = new LinkAction(
            'viewThothFiles',
            new AjaxModal(
                $request->getDispatcher()->url(
                    $request,
                    ROUTE_PAGE,
                    null,
                    'thoth',
                    'viewThothPublicationFormatFiles',
                    null,
                    [
                        'submissionId' => $submission->getId(),
                        'publicationId' => $publication->getId(),
                        'representationId' => $representationId,
                    ]
                ),
                $title
            ),
            __('plugins.generic.thoth.grid.action.viewThothFilesLabel'),
            'view',
            $title
        );

        $templateMgr->assign('action', $linkAction);
        $templateMgr->assign('contextId', $cellId);

        return '<span id="' . htmlspecialchars($cellId, ENT_QUOTES, 'UTF-8') . '" class="pkp_linkActions">' .
            $templateMgr->fetch('linkAction/linkAction.tpl') .
            '</span>';
    }
}
