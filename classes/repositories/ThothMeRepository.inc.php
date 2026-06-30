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
    private const PROFILE_SELECTION = [
        'userId',
        'email',
        'firstName',
        'lastName',
        'isSuperuser',
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

    public function getProfile()
    {
        $profile = $this->normalizeMe($this->get(self::PROFILE_SELECTION));
        $profile['linkedPublishers'] = $this->getLinkedPublishersFromProfile($profile);

        return $profile;
    }

    public function getLinkedPublishers()
    {
        return $this->getProfile()['linkedPublishers'] ?? [];
    }

    public function hasCdnWritePermission($me)
    {
        $publisherContexts = $this->normalizePublisherContexts($me['publisherContexts'] ?? []);

        foreach ($publisherContexts as $publisherContext) {
            if (!empty($publisherContext['permissions']['cdnWrite'])) {
                return true;
            }
        }

        return false;
    }

    private function normalizeMe($me)
    {
        if (is_object($me) && method_exists($me, 'toArray')) {
            return $me->toArray();
        }

        return $me ?: [];
    }

    private function getLinkedPublishersFromProfile($profile)
    {
        $publisherContexts = $this->normalizePublisherContexts($profile['publisherContexts'] ?? []);

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
