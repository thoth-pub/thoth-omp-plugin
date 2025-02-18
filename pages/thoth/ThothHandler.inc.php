<?php

/**
 * @file plugins/generic/thoth/pages/ThothHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothHandler
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Handle requests for Thoth functions
 */

use APP\core\Application;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\i18n\AppLocale;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\plugins\PluginRegistry;
use PKP\security\Role;

import('plugins.generic.thoth.classes.components.listPanels.ThothListPanel');
import('plugins.generic.thoth.classes.facades.ThothService');
import('plugins.generic.thoth.classes.facades.ThothRepository');

class ThothHandler extends Handler
{
    public $_isBackendPage = true;

    public function __construct()
    {
        parent::__construct();

        $this->addRoleAssignment(
            [Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_MANAGER],
            ['index']
        );
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
        $this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function initialize($request)
    {
        $this->setupTemplate($request);
        parent::initialize($request);
    }

    public function index($args, $request)
    {
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
        $context = $request->getContext();
        $connectionError = false;

        $plugin = PluginRegistry::getPlugin('generic', 'thothplugin');
        $templateMgr = TemplateManager::getManager($request);

        $this->addScripts($request, $templateMgr, $plugin);
        $this->addStyles($request, $templateMgr, $plugin);

        try {
            $publishers = ThothRepository::account()->getLinkedPublishers();
            $imprints = ThothRepository::imprint()->getMany(array_column($publishers, 'publisherId'));
        } catch (Exception $e) {
            error_log($e->getMessage());
            $connectionError = true;
        }

        $imprintOptions = [];
        foreach ($imprints as $imprint) {
            $imprintOptions[] = [
                'value' => $imprint->getImprintId(),
                'label' => $imprint->getImprintName()
            ];
        }

        $selectedImprint = null;
        if (count($imprintOptions) === 1) {
            $selectedImprint = $imprintOptions[0]['value'];
        }

        $thothList = new ThothListPanel(
            'thoth',
            __('submission.list.monographs'),
            [
                'apiUrl' => $request->getDispatcher()->url(
                    $request,
                    Application::ROUTE_API,
                    $context->getPath(),
                    '_submissions'
                ),
                'imprintOptions' => $imprintOptions,
                'selectedImprint' => $selectedImprint
            ]
        );

        $collector = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$context->getId()]);
        $total = $collector->getCount();
        $submissions = $collector->limit($thothList->count)->getMany();

        $userGroups = Repo::userGroup()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany();

        $genreDao = DAORegistry::getDAO('GenreDAO');
        $genres = $genreDao->getByContextId($context->getId())->toArray();

        $items = Repo::submission()->getSchemaMap()
            ->mapMany($submissions, $userGroups, $genres)
            ->values();

        $thothList->set([
            'items' => $items,
            'itemsMax' => $total,
        ]);

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->setState([
            'components' => [
                'thoth' => $thothList->getConfig()
            ],
            'connectionError' => $connectionError
        ]);

        return $templateMgr->display($plugin->getTemplateResource('thoth/index.tpl'));
    }

    private function addScripts($request, $templateMgr, $plugin)
    {
        $templateMgr->addJavaScript(
            'thoth-list-item-component',
            $request->getBaseUrl() . '/' .
            $plugin->getPluginPath() .
            '/js/ui/components/ListPanel/ThothListItem.js',
            [
                'contexts' => 'backend',
                'priority' => STYLE_SEQUENCE_LAST,
            ]
        );

        $templateMgr->addJavaScript(
            'thoth-list-panel-component',
            $request->getBaseUrl() . '/' .
            $plugin->getPluginPath() .
            '/js/ui/components/ListPanel/ThothListPanel.js',
            [
                'contexts' => 'backend',
                'priority' => STYLE_SEQUENCE_LAST,
            ]
        );
    }

    private function addStyles($request, $templateMgr, $plugin)
    {
        $templateMgr->addStyleSheet(
            'thoth-list-panel-component',
            '
                .listPanel--thoth .listPanel__itemIdentity {
                    position: relative;
                    padding: 0;
                }

                .listPanel__selectWrapper {
                    display: flex;
                    align-items: center;
                    margin-left: -1rem;
                    overflow: hidden;
                }

                .listPanel__selector {
                    width: 3rem;
                    padding-left: 1rem;
                }

                .listPanel__item--thoth_notice {
                    margin-top: .5em;
                    font-size: .75rem;
                    line-height: 1.5em;
                    color: #222;
                }

                .listPanel__itemMetadata {
                    font-size: .75rem;
                    line-height: 1.5em;
                    color: #222;
                    margin-left: 0.75rem;
                }

                .listPanel__itemMetadata--badge {
                    margin-right: 0.25rem;
                }

                .listPanel__itemExpanded--thoth, .listPanel__item--thoth_notice {
                    padding-left: 2rem;
                }

                .listPanel__block--option {
                    display: block;
                }
            ',
            [
                'contexts' => 'backend',
                'inline' => true,
                'priority' => STYLE_SEQUENCE_LAST,
            ]
        );
    }
}
