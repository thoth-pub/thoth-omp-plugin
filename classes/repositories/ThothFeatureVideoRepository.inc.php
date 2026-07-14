<?php

use ThothApi\GraphQL\Inputs\NewWorkFeaturedVideo;
use ThothApi\GraphQL\Inputs\PatchWorkFeaturedVideo;

class ThothFeatureVideoRepository
{
    private const SELECTION = [
        'workFeaturedVideoId', 'workId', 'title', 'url', 'width', 'height',
    ];
    private $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function create(array $data)
    {
        return $this->thothClient->createWorkFeaturedVideo(
            new NewWorkFeaturedVideo($data),
            self::SELECTION
        );
    }

    public function update(array $data)
    {
        return $this->thothClient->updateWorkFeaturedVideo(
            new PatchWorkFeaturedVideo($data),
            self::SELECTION
        );
    }
}
