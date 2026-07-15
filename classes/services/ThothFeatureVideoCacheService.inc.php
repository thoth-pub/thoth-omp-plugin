<?php

import('lib.pkp.classes.cache.CacheManager');

class ThothFeatureVideoCacheService
{
    public const TTL = 3600;

    public function get($workId, $loader)
    {
        $cache = CacheManager::getManager()->getFileCache('thothFeatureVideo', 'work-' . $workId, [$this, 'cacheMiss']);
        $contents = (array) $cache->getContents();
        if ($cache->getCacheTime() && time() - $cache->getCacheTime() < self::TTL && array_key_exists('video', $contents)) {
            return $contents['video'];
        }
        $video = $loader();
        $cache->setEntireCache(['video' => $video]);
        return $video;
    }

    public function flush($workId)
    {
        CacheManager::getManager()->getFileCache('thothFeatureVideo', 'work-' . $workId, [$this, 'cacheMiss'])->flush();
    }

    public function cacheMiss($cache, $id)
    {
        return null;
    }
}
