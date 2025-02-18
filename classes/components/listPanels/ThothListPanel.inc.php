<?php

/**
 * @file plugins/generic/thoth/classes/components/listPanel/ThothListPanel.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothListPanel
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A ListPanel component for register submissions in Thoth
 */

use APP\core\Application;
use APP\facades\Repo;
use APP\template\TemplateManager;
use PKP\components\listPanels\ListPanel;
use PKP\core\PKPApplication;

class ThothListPanel extends ListPanel
{
    public $apiUrl = '';

    public $count = 30;

    public $getParams = [];

    public $itemsMax = 0;

    public $imprintOptions = [];

    public function getConfig()
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        $config = parent::getConfig();

        $config['apiUrl'] = $this->apiUrl;
        $config['count'] = $this->count;
        $config['getParams'] = $this->getParams;
        $config['itemsMax'] = $this->itemsMax;
        $config['imprintOptions'] = $this->imprintOptions;
        $config['csrfToken'] = $request->getSession()->getCSRFToken();
        $config['errors'] = [];
        $config['filters'] = [];

        $config['filters'] = [];

        $config['filters'][] = [
            'heading' => __('common.status'),
            'filters' => [
                [
                    'param' => 'status',
                    'value' => STATUS_PUBLISHED,
                    'title' => __('publication.status.published'),
                ],
                [
                    'param' => 'status',
                    'value' => STATUS_QUEUED,
                    'title' => __('publication.status.unpublished'),
                ]
            ]
        ];

        if ($context) {
            $config['contextId'] = $context->getId();

            $categories = [];
            $categoriesCollection = Repo::category()->getCollector()
                ->filterByContextIds([$context->getId()])
                ->getMany();

            foreach ($categoriesCollection as $category) {
                [$categorySortBy, $categorySortDir] = explode('-', $category->getSortOption());
                $categorySortDir = empty($categorySortDir) ? $catalogSortDir : ($categorySortDir == SORT_DIRECTION_ASC ? 'ASC' : 'DESC');
                $categories[] = [
                    'param' => 'categoryIds',
                    'value' => (int) $category->getId(),
                    'title' => $category->getLocalizedTitle(),
                    'sortBy' => $categorySortBy,
                    'sortDir' => $categorySortDir,
                ];
            }
            if (count($categories)) {
                $config['filters'][] = [
                    'heading' => __('catalog.categories'),
                    'filters' => $categories,
                ];
            }

            $series = [];
            $seriesResult = Repo::section()
                ->getCollector()
                ->filterByContextIds([$context->getId()])
                ->getMany();
            foreach ($seriesResult as $seriesObj) {
                [$seriesSortBy, $seriesSortDir] = explode('-', $seriesObj->getSortOption());
                $seriesSortDir = empty($seriesSortDir) ? $catalogSortDir : ($seriesSortDir == SORT_DIRECTION_ASC ? 'ASC' : 'DESC');
                $series[] = [
                    'param' => 'seriesIds',
                    'value' => (int) $seriesObj->getId(),
                    'title' => $seriesObj->getLocalizedTitle(),
                    'sortBy' => $seriesSortBy,
                    'sortDir' => $seriesSortDir,
                ];
            }
            if (count($series)) {
                $config['filters'][] = [
                    'heading' => __('catalog.manage.series'),
                    'filters' => $series,
                ];
            }
        }

        $searchSubmissionsApiUrl = $request->getDispatcher()->url(
            $request,
            PKPApplication::ROUTE_API,
            $context->getPath(),
            'submissions'
        );

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->setLocaleKeys([
            'common.selectAll',
            'common.selectNone',
            'plugins.generic.thoth.imprint',
            'plugins.generic.thoth.imprint.required',
            'plugins.generic.thoth.register',
            'plugins.generic.thoth.status.registered',
            'plugins.generic.thoth.status.unregistered',
            'plugins.generic.thoth.actions.register.label',
            'plugins.generic.thoth.actions.register.prompt',
        ]);

        return $config;
    }
}
