<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothFeatureVideoService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothFeatureVideoService
 * @ingroup plugins_generic_thoth
 *
 * @brief Creates and uploads a hosted featured video in Thoth.
 */

namespace APP\plugins\generic\thoth\classes\services;

class ThothFeatureVideoService
{
    private const WIDTH = 640;
    private const HEIGHT = 360;

    private $featureVideoRepository;
    private $featureVideoFileUploadRepository;
    private ThothFileUploadService $fileUploadService;

    public function __construct(
        $featureVideoRepository,
        $featureVideoFileUploadRepository,
        ThothFileUploadService $fileUploadService
    ) {
        $this->featureVideoRepository = $featureVideoRepository;
        $this->featureVideoFileUploadRepository = $featureVideoFileUploadRepository;
        $this->fileUploadService = $fileUploadService;
    }

    public function upload(string $workId, string $title, array $file): array
    {
        $video = $this->featureVideoRepository->create([
            'workId' => $workId,
            'title' => $title,
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
        ]);
        $videoId = $video->getWorkFeaturedVideoId();

        $uploadResponse = $this->featureVideoFileUploadRepository->init([
            'workFeaturedVideoId' => $videoId,
            'declaredMimeType' => $file['mimeType'],
            'declaredExtension' => $file['extension'],
            'declaredSha256' => $file['sha256'],
        ]);
        $uploadedFile = $this->fileUploadService->upload(
            $uploadResponse,
            $file['path'],
            $this->featureVideoFileUploadRepository
        );
        $cdnUrl = $uploadedFile->getCdnUrl();

        $this->featureVideoRepository->update([
            'workFeaturedVideoId' => $videoId,
            'url' => $cdnUrl,
        ]);

        return [
            'id' => $videoId,
            'title' => $title,
            'url' => $cdnUrl,
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
            'sha256' => $file['sha256'],
        ];
    }
}
