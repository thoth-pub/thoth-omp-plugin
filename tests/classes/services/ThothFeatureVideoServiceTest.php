<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothFeatureVideoServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

require_once __DIR__ . '/../../../vendor/autoload.php';

use APP\plugins\generic\thoth\classes\services\ThothFeatureVideoService;
use APP\plugins\generic\thoth\classes\services\ThothFileUploadService;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Schemas\File;
use ThothApi\GraphQL\Schemas\FileUploadResponse;
use ThothApi\GraphQL\Schemas\WorkFeaturedVideo;

class ThothFeatureVideoServiceTest extends PKPTestCase
{
    public function testUploadsVideoAndSetsItsCdnUrl(): void
    {
        $videoRepository = new ThothFeatureVideoRepositoryStub();
        $fileRepository = new ThothFeatureVideoFileRepositoryStub();
        $fileUploadService = new ThothFeatureVideoFileUploadServiceStub();
        $service = new ThothFeatureVideoService(
            $videoRepository,
            $fileRepository,
            $fileUploadService
        );

        $result = $service->upload('work-id', 'Book trailer', [
            'path' => '/tmp/trailer.mp4',
            'extension' => 'mp4',
            'mimeType' => 'video/mp4',
            'sha256' => 'video-sha256',
        ]);

        $this->assertSame('work-id', $videoRepository->created['workId']);
        $this->assertSame('Book trailer', $videoRepository->created['title']);
        $this->assertSame('video-id', $fileRepository->initialized['workFeaturedVideoId']);
        $this->assertSame('video/mp4', $fileRepository->initialized['declaredMimeType']);
        $this->assertSame('https://cdn.thoth.pub/trailer.mp4', $videoRepository->updated['url']);
        $this->assertSame('video-id', $result['id']);
        $this->assertSame('https://cdn.thoth.pub/trailer.mp4', $result['url']);
    }
}

class ThothFeatureVideoRepositoryStub
{
    public array $created = [];
    public array $updated = [];

    public function new(array $data): array
    {
        return $data;
    }

    public function create(array $data): WorkFeaturedVideo
    {
        $this->created = $data;
        return new WorkFeaturedVideo(['workFeaturedVideoId' => 'video-id']);
    }

    public function update(array $data): WorkFeaturedVideo
    {
        $this->updated = $data;
        return new WorkFeaturedVideo($data);
    }
}

class ThothFeatureVideoFileRepositoryStub
{
    public array $initialized = [];

    public function new(array $data): array
    {
        return $data;
    }

    public function init(array $data): FileUploadResponse
    {
        $this->initialized = $data;
        return new FileUploadResponse(['fileUploadId' => 'upload-id']);
    }
}

class ThothFeatureVideoFileUploadServiceStub extends ThothFileUploadService
{
    public function upload($fileUploadResponse, string $filePath, $fileUploadRepository)
    {
        return new File(['cdnUrl' => 'https://cdn.thoth.pub/trailer.mp4']);
    }
}
