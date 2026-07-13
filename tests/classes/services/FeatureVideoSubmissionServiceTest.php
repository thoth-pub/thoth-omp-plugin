<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/FeatureVideoSubmissionServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

require_once __DIR__ . '/../../../vendor/autoload.php';

use APP\plugins\generic\thoth\classes\services\FeatureVideoSubmissionService;
use InvalidArgumentException;
use PKP\tests\PKPTestCase;

class FeatureVideoSubmissionServiceTest extends PKPTestCase
{
    public function testUploadsWithoutPersistingFeatureVideoMetadata(): void
    {
        $thothService = new ThothFeatureVideoUploadStub();
        $service = new FeatureVideoSubmissionServiceStub($thothService, [
            'path' => '/tmp/trailer.mp4',
            'extension' => 'mp4',
            'mimeType' => 'video/mp4',
            'sha256' => 'video-sha256',
        ]);
        $submission = new FeatureVideoSubmissionStub('work-id');
        $publication = new FeatureVideoPublicationStub();

        $service->upload($submission, $publication, 'Book trailer', 15, 7);

        $this->assertSame(15, $service->temporaryFileId);
        $this->assertSame(7, $service->userId);
        $this->assertSame('work-id', $thothService->workId);
        $this->assertSame([], $publication->data);
        $this->assertFalse($service->persisted);
        $this->assertTrue($service->deleted);
    }

    public function testRejectsFilesThatAreNotVideos(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'thoth-video-');
        file_put_contents($path, 'not a video');
        $service = new FeatureVideoSubmissionServiceTemporaryFileStub(
            new ThothFeatureVideoUploadStub(),
            new FeatureVideoTemporaryFileStub($path, 'trailer.mp4')
        );

        $this->expectException(InvalidArgumentException::class);
        $service->upload(
            new FeatureVideoSubmissionStub('work-id'),
            new FeatureVideoPublicationStub(),
            'Book trailer',
            15,
            7
        );
    }
}

class FeatureVideoSubmissionServiceStub extends FeatureVideoSubmissionService
{
    public int $temporaryFileId = 0;
    public int $userId = 0;
    public bool $persisted = false;
    public bool $deleted = false;
    private array $file;

    public function __construct($thothService, array $file)
    {
        parent::__construct($thothService);
        $this->file = $file;
    }

    protected function resolveTemporaryFile(int $temporaryFileId, int $userId): array
    {
        $this->temporaryFileId = $temporaryFileId;
        $this->userId = $userId;
        return $this->file;
    }

    protected function persistPublication($publication): void
    {
        $this->persisted = true;
    }

    protected function deleteTemporaryFile(int $temporaryFileId, int $userId): void
    {
        $this->deleted = true;
    }
}

class FeatureVideoSubmissionServiceTemporaryFileStub extends FeatureVideoSubmissionService
{
    private $temporaryFile;

    public function __construct($thothService, $temporaryFile)
    {
        parent::__construct($thothService);
        $this->temporaryFile = $temporaryFile;
    }

    protected function getTemporaryFile(int $temporaryFileId, int $userId)
    {
        return $this->temporaryFile;
    }
}

class ThothFeatureVideoUploadStub
{
    public string $workId = '';

    public function upload(string $workId, string $title, array $file): array
    {
        $this->workId = $workId;
        return [
            'id' => 'video-id',
            'title' => $title,
            'url' => 'https://cdn.thoth.pub/trailer.mp4',
            'width' => 640,
            'height' => 360,
            'sha256' => $file['sha256'],
        ];
    }
}

class FeatureVideoSubmissionStub
{
    private string $workId;

    public function __construct(string $workId)
    {
        $this->workId = $workId;
    }

    public function getData(string $name)
    {
        return $name === 'thothWorkId' ? $this->workId : null;
    }
}

class FeatureVideoPublicationStub
{
    public array $data = [];

    public function setData(string $name, $value): void
    {
        $this->data[$name] = $value;
    }
}

class FeatureVideoTemporaryFileStub
{
    private string $path;
    private string $name;

    public function __construct(string $path, string $name)
    {
        $this->path = $path;
        $this->name = $name;
    }

    public function getFilePath(): string
    {
        return $this->path;
    }

    public function getOriginalFileName(): string
    {
        return $this->name;
    }
}
