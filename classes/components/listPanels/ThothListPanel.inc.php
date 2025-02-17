<?php

class ThothListPanel extends \PKP\components\listPanels\ListPanel
{
    public $apiUrl = '';

    public $count = 30;

    public $getParams = [];

    public $itemsMax = 0;

    public $imprintOptions = [];

    public function getConfig()
    {
        $request = \Application::get()->getRequest();
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
            $categoryDao = \DAORegistry::getDAO('CategoryDAO');
            $categoriesResult = $categoryDao->getByContextId($context->getId());
            while (!$categoriesResult->eof()) {
                $category = $categoriesResult->next();
                list($categorySortBy, $categorySortDir) = explode('-', $category->getSortOption());
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
            $seriesDao = \DAORegistry::getDAO('SeriesDAO');
            $seriesResult = $seriesDao->getByPressId($context->getId());
            while (!$seriesResult->eof()) {
                $seriesObj = $seriesResult->next();
                list($seriesSortBy, $seriesSortDir) = explode('-', $seriesObj->getSortOption());
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
            ROUTE_API,
            $context->getPath(),
            'submissions'
        );

        $supportedFormLocales = $context->getSupportedFormLocales();
        $localeNames = \AppLocale::getAllLocales();
        $locales = array_map(function ($localeKey) use ($localeNames) {
            return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
        }, $supportedFormLocales);

        $templateMgr = \TemplateManager::getManager($request);
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
