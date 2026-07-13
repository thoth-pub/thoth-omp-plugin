<?php

/**
 * @file plugins/generic/thoth/classes/repositories/ThothFeatureVideoRepository.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

namespace APP\plugins\generic\thoth\classes\repositories;

use ThothApi\GraphQL\Inputs\NewWorkFeaturedVideo;
use ThothApi\GraphQL\Inputs\PatchWorkFeaturedVideo;

class ThothFeatureVideoRepository
{
    private const SELECTION = [
        'workFeaturedVideoId',
        'workId',
        'title',
        'url',
        'width',
        'height',
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
