<?php

/**
 * @file plugins/generic/thoth/classes/repositories/ThothMeRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothMeRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage the authenticated Thoth user
 */

namespace APP\plugins\generic\thoth\classes\repositories;

use Illuminate\Support\Facades\Cache;

class ThothMeRepository
{
    public const CACHE_LIFETIME = 24 * 60 * 60;

    private const ME_SELECTION = [
        'userId',
        'email',
        'firstName',
        'lastName',
        'isSuperuser',
        'publisherContexts' => self::PUBLISHER_CONTEXT_SELECTION,
    ];

    private const PUBLISHER_CONTEXT_SELECTION = [
        'publisher' => self::PUBLISHER_SELECTION,
        'permissions' => self::PERMISSIONS_SELECTION,
    ];

    private const PUBLISHER_SELECTION = [
        'publisherId',
        'publisherName',
        'imprints' => self::IMPRINT_SELECTION,
    ];

    private const IMPRINT_SELECTION = [
        'imprintId',
        'imprintName',
    ];

    private const PERMISSIONS_SELECTION = [
        'publisherAdmin',
        'workLifecycle',
        'cdnWrite',
    ];

    protected $thothClient;

    private $contextId;

    public function __construct($thothClient, int $contextId)
    {
        $this->thothClient = $thothClient;
        $this->contextId = $contextId;
    }

    protected function _getCacheId(int $contextId): string
    {
        $contextId = (int) $contextId;
        return "thothMe-{$contextId}";
    }

    public function get()
    {
        return Cache::remember(
            $this->_getCacheId($this->contextId),
            static::CACHE_LIFETIME,
            fn () => $this->thothClient->me(self::ME_SELECTION)
        );
    }
}
