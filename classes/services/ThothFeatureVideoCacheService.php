<?php

namespace APP\plugins\generic\thoth\classes\services;

use Illuminate\Support\Facades\Cache;

class ThothFeatureVideoCacheService
{
    public const TTL = 3600;

    public function get(string $workId, callable $loader): ?array
    {
        $value = Cache::remember('thothFeatureVideo-work-' . $workId, self::TTL, function () use ($loader) {
            return ['video' => $loader()];
        });
        return $value['video'] ?? null;
    }

    public function flush(string $workId): void
    {
        Cache::forget('thothFeatureVideo-work-' . $workId);
    }
}
