<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.services.ThothFeatureVideoService');
import('plugins.generic.thoth.classes.services.ThothFileUploadService');

use ThothApi\GraphQL\Schemas\File;
use ThothApi\GraphQL\Schemas\FileUploadResponse;
use ThothApi\GraphQL\Schemas\WorkFeaturedVideo;

class ThothFeatureVideoServiceTest extends PKPTestCase
{
    public function testUploadsVideoAndSetsItsCdnUrl(): void
    {
        $videoRepository = new ThothFeatureVideoRepositoryStub();
        $fileRepository = new ThothFeatureVideoFileRepositoryStub();
        $service = new ThothFeatureVideoService(
            $videoRepository,
            $fileRepository,
            new ThothFeatureVideoFileUploadServiceStub()
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
    public $created = [];
    public $updated = [];

    public function create(array $data)
    {
        $this->created = $data;
        return new WorkFeaturedVideo(['workFeaturedVideoId' => 'video-id']);
    }

    public function update(array $data)
    {
        $this->updated = $data;
        return new WorkFeaturedVideo($data);
    }
}

class ThothFeatureVideoFileRepositoryStub
{
    public $initialized = [];

    public function init(array $data)
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
