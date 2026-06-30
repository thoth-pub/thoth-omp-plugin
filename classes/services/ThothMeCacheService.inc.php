<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothMeCacheService.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothMeCacheService
 * @ingroup plugins_generic_thoth
 *
 * @brief Cache service for authenticated Thoth user data.
 */

import('lib.pkp.classes.cache.CacheManager');
import('plugins.generic.thoth.classes.facades.ThothRepo');

class ThothMeCacheService
{
    public const TTL = 86400;

    private const CONTEXT = 'thothMe';

    public function get($contextId)
    {
        $cache = $this->getCache($contextId);
        $cacheTime = $cache->getCacheTime();
        $cachedMe = $cache->getContents();

        if (
            $cacheTime
            && time() - $cacheTime < self::TTL
            && is_array($cachedMe)
            && isset($cachedMe['me'])
        ) {
            return $cachedMe['me'];
        }

        return null;
    }

    public function set($contextId, $me)
    {
        $this->getCache($contextId)->setEntireCache([
            'me' => $me,
        ]);
    }

    public function remember($contextId, $loader)
    {
        $me = $this->get($contextId);
        if ($me !== null) {
            return $me;
        }

        $me = $loader();
        $this->set($contextId, $me);

        return $me;
    }

    public function getProfile($contextId)
    {
        return $this->remember($contextId, function () {
            return ThothRepo::me()->getProfile();
        });
    }

    public function hasCdnWritePermission($contextId)
    {
        return ThothRepo::me()->hasCdnWritePermission($this->getProfile($contextId));
    }

    public function getLinkedPublishers($contextId)
    {
        return $this->getProfile($contextId)['linkedPublishers'] ?? [];
    }

    public function flush($contextId)
    {
        $this->getCache($contextId)->flush();
    }

    public function cacheMiss($cache, $id)
    {
        return null;
    }

    private function getCache($contextId)
    {
        return CacheManager::getManager()->getFileCache(
            self::CONTEXT,
            'context-' . $contextId,
            [$this, 'cacheMiss']
        );
    }
}
