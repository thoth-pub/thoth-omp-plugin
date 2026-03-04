<?php

/**
 * @file plugins/generic/thoth/classes/handlers/pages/ThothHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothHandler
*
* @ingroup plugins_generic_thoth
*
* @brief Handle requests for Thoth functions
*/

namespace APP\plugins\generic\thoth\classes\handlers\pages;

use APP\core\Application;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\i18n\AppLocale;
use APP\plugins\generic\thoth\classes\components\listPanels\ThothListPanel;
use APP\plugins\generic\thoth\classes\facades\ThothRepository;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\PKPSiteAccessPolicy;
use PKP\security\Role;
use PKP\userGroup\UserGroup;

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
        $this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function initialize($request, $args = null)
    {
        $this->setupTemplate($request);
        parent::initialize($request, $args);
    }

    public function index($args, $request)
    {
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
        $context = $request->getContext();
        $connectionError = false;

        $plugin = PluginRegistry::getPlugin('generic', 'thothplugin');
        $templateMgr = TemplateManager::getManager($request);

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

        $userGroups = UserGroup::withContextIds($context->getId())->get();

        $genreDao = DAORegistry::getDAO('GenreDAO');
        $genres = $genreDao->getByContextId($context->getId())->toArray();

        $items = Repo::submission()->getSchemaMap()
            ->mapMany(
                $submissions,
                $userGroups,
                $genres,
                $this->getAuthorizedContextObject(Application::ASSOC_TYPE_USER_ROLES)
            )
            ->values();

        $thothList->set([
            'items' => $items,
            'itemsMax' => $total,
        ]);

        $templateMgr->setState([
            'components' => [
                'thoth' => $thothList->getConfig()
            ],
            'connectionError' => $connectionError
        ]);

        return $templateMgr->display($plugin->getTemplateResource('thoth/index.tpl'));
    }

}
