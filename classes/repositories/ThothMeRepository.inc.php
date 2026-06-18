<?php

/**
 * @file plugins/generic/thoth/classes/repositories/ThothMeRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothMeRepository
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage the authenticated Thoth user
 */

class ThothMeRepository
{
    private const PUBLISHER_CONTEXTS_SELECTION = [
        'publisherContexts' => [
            'publisher' => ['publisherId', 'publisherName'],
            'permissions' => ['publisherAdmin', 'workLifecycle', 'cdnWrite'],
        ],
    ];

    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function get(array $selection = [])
    {
        return $this->thothClient->me($selection);
    }

    public function getLinkedPublishers()
    {
        $publisherContexts = $this->normalizePublisherContexts(
            $this->get(self::PUBLISHER_CONTEXTS_SELECTION)->getPublisherContexts() ?? []
        );

        return array_values(array_map(
            fn ($publisherContext) => $publisherContext['publisher'],
            array_filter($publisherContexts, fn ($publisherContext) => !empty($publisherContext['publisher']))
        ));
    }

    private function normalizePublisherContexts(array $publisherContexts): array
    {
        return array_map(
            fn ($publisherContext) => is_object($publisherContext) && method_exists($publisherContext, 'toArray')
                ? $publisherContext->toArray()
                : $publisherContext,
            $publisherContexts
        );
    }
}
