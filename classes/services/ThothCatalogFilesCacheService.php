<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothCatalogFilesCacheService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothCatalogFilesCacheService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Cache service for public catalog files loaded from Thoth.
 */

namespace APP\plugins\generic\thoth\classes\services;

use Illuminate\Support\Facades\Cache;

class ThothCatalogFilesCacheService
{
    public const TTL = 3600;

    private const CONTEXT = 'thothCatalogFiles';

    public function get($publicationId): ?array
    {
        $cachedCatalogFiles = Cache::get($this->getCacheKey($publicationId));

        if (is_array($cachedCatalogFiles) && isset($cachedCatalogFiles['catalogFiles'])) {
            return $cachedCatalogFiles['catalogFiles'];
        }

        return null;
    }

    public function set($publicationId, array $catalogFiles): void
    {
        Cache::put($this->getCacheKey($publicationId), [
            'catalogFiles' => $catalogFiles,
            'cacheTime' => time(),
        ], self::TTL);
    }

    public function flush($publicationId): void
    {
        Cache::forget($this->getCacheKey($publicationId));
    }

    public function getClientCacheKeySuffix($publicationId): string
    {
        $cachedCatalogFiles = Cache::get($this->getCacheKey($publicationId));

        return (string) ($cachedCatalogFiles['cacheTime'] ?? time());
    }

    public function cacheMiss($cache, $id)
    {
        return null;
    }

    private function getCacheKey($publicationId): string
    {
        return self::CONTEXT . '-publication-' . (int) $publicationId;
    }
}
