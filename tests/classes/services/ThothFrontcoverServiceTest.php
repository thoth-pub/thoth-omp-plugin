<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothFrontcoverServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothFrontcoverServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothFrontcoverService
 *
 * @brief Test class for the ThothFrontcoverService class
 */

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\publication\DAO as PublicationDAO;
use APP\submission\Repository as SubmissionRepository;
use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Enums\WorkType;
use ThothApi\GraphQL\Inputs\NewFrontcoverFileUpload;
use ThothApi\GraphQL\Inputs\PatchWork;
use ThothApi\GraphQL\Schemas\File;
use ThothApi\GraphQL\Schemas\FileUploadResponse;

import('classes.publication.Publication');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothFrontcoverFileUploadRepository');
import('plugins.generic.thoth.classes.repositories.ThothWorkRepository');
import('plugins.generic.thoth.classes.services.ThothFileUploadService');
import('plugins.generic.thoth.classes.services.ThothFrontcoverService');

class ThothFrontcoverServiceTest extends PKPTestCase
{
    private $temporaryFiles = [];

    protected function getMockedContainerKeys(): array
    {
        return [...parent::getMockedContainerKeys(), PublicationDAO::class, SubmissionRepository::class];
    }

    public function testPersistsFrontcoverMetadataThroughPublicationDao(): void
    {
        $publication = $this->createMock(Publication::class);
        $persisted = false;
        $publicationDao = Mockery::mock(app(PublicationDAO::class))
            ->makePartial()
            ->shouldReceive('update')
            ->once()
            ->with($publication)
            ->andReturnUsing(function () use (&$persisted) {
                $persisted = true;
            })
            ->getMock();
        app()->instance(PublicationDAO::class, $publicationDao);

        $service = new class (
            $this->createMock(ThothFrontcoverFileUploadRepository::class),
            $this->createMock(ThothWorkRepository::class),
            $this->createMock(ThothFileUploadService::class)
        ) extends ThothFrontcoverService {
            public function persistFrontcoverMetadata($publication): void
            {
                $this->persistPublication($publication);
            }
        };

        $service->persistFrontcoverMetadata($publication);

        $this->assertTrue($persisted);
    }

    public function testAllowsFrontcoverUploadWhenSubmissionContextHasCdnWritePermission(): void
    {
        $submission = Mockery::mock(\APP\submission\Submission::class)
            ->shouldReceive('getData')
            ->once()
            ->with('contextId')
            ->andReturn(1)
            ->getMock();
        $submissionRepository = Mockery::mock(app(SubmissionRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->once()
            ->with(1)
            ->andReturn($submission)
            ->getMock();
        app()->instance(SubmissionRepository::class, $submissionRepository);

        $publication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData'])
            ->getMock();
        $publication->method('getData')
            ->willReturnCallback(function ($key) {
                return [
                    'contextId' => null,
                    'submissionId' => 1,
                ][$key] ?? null;
            });

        $service = new class (
            $this->createMock(ThothFrontcoverFileUploadRepository::class),
            $this->createMock(ThothWorkRepository::class),
            $this->createMock(ThothFileUploadService::class)
        ) extends ThothFrontcoverService {
            public function canUploadFrontcoverForPublication($publication): bool
            {
                return $this->canUploadFrontcover($publication);
            }

            protected function hasCdnWritePermission($contextId): bool
            {
                return true;
            }
        };

        $this->assertTrue($service->canUploadFrontcoverForPublication($publication));
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        parent::tearDown();
    }

    public function testDoesNotUploadWithoutCdnWritePermission()
    {
        $publication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData'])
            ->getMock();
        $publication->method('getData')
            ->with('thothUploadFrontcover')
            ->willReturn(true);

        $frontcoverRepository = $this->createMock(ThothFrontcoverFileUploadRepository::class);
        $frontcoverRepository->expects($this->never())->method('init');

        $workRepository = $this->createMock(ThothWorkRepository::class);
        $workRepository->expects($this->never())->method('edit');

        $fileUploadService = $this->createMock(ThothFileUploadService::class);
        $fileUploadService->expects($this->never())->method('upload');

        $service = $this->getMockBuilder(ThothFrontcoverService::class)
            ->setConstructorArgs([$frontcoverRepository, $workRepository, $fileUploadService])
            ->setMethods(['canUploadFrontcover'])
            ->getMock();
        $service->expects($this->once())
            ->method('canUploadFrontcover')
            ->with($publication)
            ->willReturn(false);

        $service->sync($publication, 'work-id');
    }

    public function testClearsUploadDataWhenFrontcoverHostingIsDisabled()
    {
        $metadata = [];
        $publication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData', 'setData'])
            ->getMock();
        $publication->method('getData')
            ->willReturnCallback(function ($key) {
                return [
                    'thothUploadFrontcover' => false,
                    'thothFrontcoverSha256' => 'previous-sha256',
                    'thothFrontcoverUrl' => 'https://cdn.thoth.pub/frontcover.png',
                ][$key] ?? null;
            });
        $publication->expects($this->exactly(2))
            ->method('setData')
            ->willReturnCallback(function ($key, $value) use (&$metadata) {
                $metadata[$key] = $value;
            });

        $frontcoverRepository = $this->createMock(ThothFrontcoverFileUploadRepository::class);
        $frontcoverRepository->expects($this->never())->method('init');

        $workRepository = $this->createMock(ThothWorkRepository::class);
        $workRepository->expects($this->never())->method('edit');

        $fileUploadService = $this->createMock(ThothFileUploadService::class);
        $fileUploadService->expects($this->never())->method('upload');

        $service = $this->getMockBuilder(ThothFrontcoverService::class)
            ->setConstructorArgs([$frontcoverRepository, $workRepository, $fileUploadService])
            ->setMethods(['persistPublication'])
            ->getMock();
        $service->expects($this->once())
            ->method('persistPublication')
            ->with($publication);

        $service->sync($publication, 'work-id');

        $this->assertNull($metadata['thothFrontcoverSha256']);
        $this->assertNull($metadata['thothFrontcoverUrl']);
    }

    public function testUploadsFrontcoverAndUpdatesWorkCoverUrl()
    {
        $frontcoverPath = $this->createTemporaryJpeg();
        $frontcoverSha256 = hash_file('sha256', $frontcoverPath);
        $cdnUrl = 'https://cdn.thoth.pub/frontcover.jpg';

        $metadata = [];
        $publication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData', 'setData'])
            ->getMock();
        $publication->method('getData')
            ->willReturnCallback(function ($key) {
                return [
                    'thothUploadFrontcover' => true,
                    'thothFrontcoverSha256' => null,
                ][$key] ?? null;
            });
        $publication->expects($this->exactly(2))
            ->method('setData')
            ->willReturnCallback(function ($key, $value) use (&$metadata) {
                $metadata[$key] = $value;
            });

        $newFrontcoverFileUpload = new NewFrontcoverFileUpload();
        $fileUploadResponse = new FileUploadResponse([
            'fileUploadId' => 'file-upload-id',
            'uploadUrl' => 'https://thoth.example/upload',
        ]);
        $file = new File([
            'cdnUrl' => $cdnUrl,
            'sha256' => $frontcoverSha256,
        ]);

        $frontcoverRepository = $this->createMock(ThothFrontcoverFileUploadRepository::class);
        $frontcoverRepository->expects($this->once())
            ->method('new')
            ->with([
                'workId' => 'work-id',
                'declaredExtension' => 'jpg',
                'declaredMimeType' => 'image/jpeg',
                'declaredSha256' => $frontcoverSha256,
            ])
            ->willReturn($newFrontcoverFileUpload);
        $frontcoverRepository->expects($this->once())
            ->method('init')
            ->with($newFrontcoverFileUpload)
            ->willReturn($fileUploadResponse);

        $workRepository = $this->createMock(ThothWorkRepository::class);
        $workRepository->expects($this->once())
            ->method('get')
            ->with('work-id')
            ->willReturn(new class () {
                public function toArray()
                {
                    return [
                        'workId' => 'work-id',
                        'workType' => WorkType::MONOGRAPH,
                        'workStatus' => WorkStatus::ACTIVE,
                        'fullTitle' => 'Ignored title',
                        'coverUrl' => 'https://old.example/cover.png',
                    ];
                }
            });
        $workRepository->expects($this->once())
            ->method('new')
            ->with([
                'workId' => 'work-id',
                'workType' => WorkType::MONOGRAPH,
                'workStatus' => WorkStatus::ACTIVE,
                'coverUrl' => $cdnUrl,
            ])
            ->willReturn(new PatchWork([
                'workId' => 'work-id',
                'coverUrl' => $cdnUrl,
            ]));
        $workRepository->expects($this->once())
            ->method('edit');

        $fileUploadService = $this->createMock(ThothFileUploadService::class);
        $fileUploadService->expects($this->once())
            ->method('upload')
            ->with($fileUploadResponse, $frontcoverPath, $frontcoverRepository)
            ->willReturn($file);

        $service = $this->getMockBuilder(ThothFrontcoverService::class)
            ->setConstructorArgs([$frontcoverRepository, $workRepository, $fileUploadService])
            ->setMethods(['canUploadFrontcover', 'resolveFrontcoverFile', 'persistPublication'])
            ->getMock();
        $service->method('canUploadFrontcover')->willReturn(true);
        $service->method('resolveFrontcoverFile')
            ->with($publication)
            ->willReturn([
                'path' => $frontcoverPath,
                'extension' => 'jpg',
                'mimeType' => 'image/jpeg',
                'sha256' => $frontcoverSha256,
            ]);
        $service->expects($this->once())
            ->method('persistPublication')
            ->with($publication);

        $service->sync($publication, 'work-id');

        $this->assertSame($frontcoverSha256, $metadata['thothFrontcoverSha256']);
        $this->assertSame($cdnUrl, $metadata['thothFrontcoverUrl']);
    }

    public function testDoesNotUploadUnsupportedFrontcoverFormat()
    {
        $metadata = [];
        $publication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData', 'setData'])
            ->getMock();
        $publication->method('getData')->willReturnCallback(function ($key) {
            return ['thothUploadFrontcover' => true, 'thothFrontcoverSha256' => null][$key] ?? null;
        });
        $publication->expects($this->exactly(3))
            ->method('setData')
            ->willReturnCallback(function ($key, $value) use (&$metadata) {
                $metadata[$key] = $value;
            });

        $frontcoverRepository = $this->createMock(ThothFrontcoverFileUploadRepository::class);
        $frontcoverRepository->expects($this->never())->method('new');
        $workRepository = $this->createMock(ThothWorkRepository::class);
        $workRepository->expects($this->never())->method('edit');
        $fileUploadService = $this->createMock(ThothFileUploadService::class);
        $fileUploadService->expects($this->never())->method('upload');

        $service = $this->getMockBuilder(ThothFrontcoverService::class)
            ->setConstructorArgs([$frontcoverRepository, $workRepository, $fileUploadService])
            ->setMethods(['canUploadFrontcover', 'resolveFrontcoverFile', 'persistPublication'])
            ->getMock();
        $service->method('canUploadFrontcover')->willReturn(true);
        $service->method('resolveFrontcoverFile')->willReturn([
            'path' => '/tmp/frontcover.png',
            'extension' => 'png',
            'mimeType' => 'image/png',
            'sha256' => 'png-sha256',
        ]);
        $service->expects($this->once())->method('persistPublication')->with($publication);

        $warning = $service->sync($publication, 'work-id');

        $this->assertFalse($metadata['thothUploadFrontcover']);
        $this->assertNull($metadata['thothFrontcoverSha256']);
        $this->assertNull($metadata['thothFrontcoverUrl']);
        $this->assertSame('plugins.generic.thoth.frontcover.unsupportedFormat', $warning);
    }

    private function createTemporaryJpeg()
    {
        $filePath = tempnam(sys_get_temp_dir(), 'thoth-frontcover-') . '.jpg';
        file_put_contents($filePath, 'jpeg fixture');
        $this->temporaryFiles[] = $filePath;

        return $filePath;
    }
}
