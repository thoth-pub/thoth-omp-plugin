<?php

/**
 * @file plugins/generic/thoth/classes/gridModifier/PublicationFormatGridModifier.php
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

namespace APP\plugins\generic\thoth\classes\gridModifier;

use APP\controllers\grid\catalogEntry\PublicationFormatGridHandler;
use APP\core\Application;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\security\Role;

class PublicationFormatGridModifier
{
    private GenericPlugin $plugin;

    private array $thothFilesActions = [];

    public function __construct(GenericPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function register(): void
    {
        Hook::add('TemplateManager::fetch', $this->addThothActions(...));
        Hook::add('TemplateManager::fetch', $this->addThothFilesColumn(...));
    }

    public function addThothActions($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];

        if ($template !== 'controllers/grid/gridCell.tpl') {
            return false;
        }

        $actions = $templateMgr->getTemplateVars('actions');
        if (!is_array($actions) || count($actions) != 2) {
            return false;
        }

        $request = Application::get()->getRequest();
        $handler = $request->getRouter()->getHandler();
        if (!($handler instanceof PublicationFormatGridHandler) || !$this->canManagePublicationFormats($handler)) {
            return false;
        }

        $submission = $handler->getSubmission();
        $thothWorkId = $submission->getData('thothWorkId');
        if (!$thothWorkId) {
            return false;
        }

        $categoryRow = $templateMgr->getTemplateVars('categoryRow');
        $publicationFormat = $categoryRow->getData();
        $actionArgs = [
            'representationId' => $publicationFormat->getId(),
            'publicationId' => $publicationFormat->getData('publicationId'),
            'thothWorkId' => $thothWorkId,
        ];
        $title = __('plugins.generic.thoth.grid.action.thothFileUpload');

        $actions[] = new LinkAction(
            'thothUpload',
            new AjaxModal(
                $request->getDispatcher()->url(
                    $request,
                    Application::ROUTE_PAGE,
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

        $templateMgr->assign('actions', $actions);

        return false;
    }

    public function addThothFilesColumn($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];

        if ($template !== 'controllers/grid/grid.tpl') {
            return false;
        }

        $request = Application::get()->getRequest();
        $handler = $request->getRouter()->getHandler();
        if (!($handler instanceof PublicationFormatGridHandler) || !$this->canManagePublicationFormats($handler)) {
            return false;
        }

        $submission = $handler->getSubmission();
        if (!$submission || !$submission->getData('thothWorkId')) {
            return false;
        }

        $this->thothFilesActions = [];
        $templateMgr->registerFilter('output', $this->injectThothFilesColumn(...));

        return false;
    }

    public function injectThothFilesColumn($output, $templateMgr)
    {
        $request = Application::get()->getRequest();

        $templateMgr->unregisterFilter('output', $this->injectThothFilesColumn(...));

        $output = $this->injectThothFilesColumnGroups($output);
        $output = $this->injectThothFilesHeader($output);
        $output = $this->injectThothFilesBodyCells($output);
        $output = $this->injectThothFilesCategoryActions($output, $templateMgr, $request);
        $output = $this->increaseColspans($output);

        return $output;
    }

    private function canManagePublicationFormats($handler): bool
    {
        $userRoles = $handler->getAuthorizedContextObject(Application::ASSOC_TYPE_USER_ROLES);

        return !empty(array_intersect(
            $userRoles,
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT]
        ));
    }

    private function injectThothFilesColumnGroups($output): string
    {
        return preg_replace(
            '/(<col class="grid-column column-name"[^>]*\/>)/',
            '$1<col class="grid-column column-thothFiles" style="width: 15%;" />',
            $output
        );
    }

    private function injectThothFilesHeader($output): string
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

    private function injectThothFilesBodyCells($output): string
    {
        return preg_replace(
            '/(<tr\b[^>]*class="[^"]*\bgridRow\b[^"]*"[^>]*>[\s\S]*?<\/td>)/',
            '$1<td></td>',
            $output
        );
    }

    private function injectThothFilesCategoryActions($output, $templateMgr, $request): string
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

    private function increaseColspans($output): string
    {
        return preg_replace_callback('/colspan="(\d+)"/', function ($matches) {
            return 'colspan="' . ((int) $matches[1] + 1) . '"';
        }, $output);
    }

    private function getThothFilesActionHtml($representationId, $templateMgr, $request): string
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

    private function renderThothFilesAction($representationId, $templateMgr, $request): string
    {
        $handler = $request->getRouter()->getHandler();
        $submission = $handler->getSubmission();
        $publication = $handler->getPublication();
        $cellId = 'cell-thothPublicationFormatFiles-' . $representationId;
        $title = __('plugins.generic.thoth.grid.action.viewThothFiles');

        $linkAction = new LinkAction(
            'viewThothFiles',
            new AjaxModal(
                $request->getDispatcher()->url(
                    $request,
                    Application::ROUTE_PAGE,
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
