<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothCatalogFilesCacheService.inc.php
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

import('lib.pkp.classes.cache.CacheManager');

class ThothCatalogFilesCacheService
{
    public const TTL = 3600;

    private const CONTEXT = 'thothCatalogFiles';

    public function get($publicationId)
    {
        $cache = $this->getCache($publicationId);
        $cacheTime = $cache->getCacheTime();
        $cachedCatalogFiles = $cache->getContents();

        if (
            $cacheTime
            && time() - $cacheTime < self::TTL
            && is_array($cachedCatalogFiles)
            && isset($cachedCatalogFiles['catalogFiles'])
        ) {
            return $cachedCatalogFiles['catalogFiles'];
        }

        return null;
    }

    public function set($publicationId, $catalogFiles)
    {
        $this->getCache($publicationId)->setEntireCache([
            'catalogFiles' => $catalogFiles,
        ]);
    }

    public function flush($publicationId)
    {
        $this->getCache($publicationId)->flush();
    }

    public function getClientCacheKeySuffix($publicationId)
    {
        return (string) ($this->getCache($publicationId)->getCacheTime() ?: time());
    }

    public function cacheMiss($cache, $id)
    {
        return null;
    }

    private function getCache($publicationId)
    {
        return CacheManager::getManager()->getFileCache(
            self::CONTEXT,
            'publication-' . $publicationId,
            [$this, 'cacheMiss']
        );
    }
}
