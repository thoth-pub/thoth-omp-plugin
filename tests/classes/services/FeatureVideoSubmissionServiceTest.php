<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.services.FeatureVideoSubmissionService');

class FeatureVideoSubmissionServiceTest extends PKPTestCase
{
    public function testUploadsWithoutPersistingFeatureVideoMetadata(): void
    {
        $thothService = new FeatureVideoUploadStub();
        $service = new FeatureVideoSubmissionServiceStub($thothService, [
            'path' => '/tmp/trailer.mp4',
            'extension' => 'mp4',
            'mimeType' => 'video/mp4',
            'sha256' => 'video-sha256',
        ]);
        $publication = new FeatureVideoPublicationStub();

        $service->upload(new FeatureVideoSubmissionStub(), $publication, 'Book trailer', 15, 7);

        $this->assertSame('work-id', $thothService->workId);
        $this->assertSame([], $publication->data);
        $this->assertFalse($service->persisted);
        $this->assertTrue($service->deleted);
    }

    public function testRejectsFilesThatAreNotVideos(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'thoth-video-');
        file_put_contents($path, 'not a video');
        $service = new FeatureVideoTemporaryFileServiceStub(
            new FeatureVideoUploadStub(),
            new FeatureVideoTemporaryFileStub($path)
        );

        $this->expectException(InvalidArgumentException::class);
        $service->upload(new FeatureVideoSubmissionStub(), new FeatureVideoPublicationStub(), 'Trailer', 15, 7);
    }
}

class FeatureVideoSubmissionServiceStub extends FeatureVideoSubmissionService
{
    public $persisted = false;
    public $deleted = false;
    private $file;

    public function __construct($service, array $file)
    {
        parent::__construct($service);
        $this->file = $file;
    }

    protected function resolveTemporaryFile(int $id, int $userId): array
    {
        return $this->file;
    }

    protected function persistPublication($publication): void
    {
        $this->persisted = true;
    }

    protected function deleteTemporaryFile(int $id, int $userId): void
    {
        $this->deleted = true;
    }
}

class FeatureVideoTemporaryFileServiceStub extends FeatureVideoSubmissionService
{
    private $file;

    public function __construct($service, $file)
    {
        parent::__construct($service);
        $this->file = $file;
    }

    protected function getTemporaryFile(int $id, int $userId)
    {
        return $this->file;
    }
}

class FeatureVideoUploadStub
{
    public $workId;

    public function upload(string $workId, string $title, array $file): array
    {
        $this->workId = $workId;
        return [
            'id' => 'video-id', 'title' => $title,
            'url' => 'https://cdn.thoth.pub/trailer.mp4',
            'width' => 640, 'height' => 360, 'sha256' => $file['sha256'],
        ];
    }
}

class FeatureVideoSubmissionStub
{
    public function getData($name)
    {
        return $name === 'thothWorkId' ? 'work-id' : null;
    }
}

class FeatureVideoPublicationStub
{
    public $data = [];

    public function setData($name, $value): void
    {
        $this->data[$name] = $value;
    }
}

class FeatureVideoTemporaryFileStub
{
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getFilePath(): string
    {
        return $this->path;
    }

    public function getOriginalFileName(): string
    {
        return 'trailer.mp4';
    }
}
