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

    public function testUploadsFrontcoverAndUpdatesWorkCoverUrl()
    {
        $frontcoverPath = $this->createTemporaryPng();
        $frontcoverSha256 = hash_file('sha256', $frontcoverPath);
        $cdnUrl = 'https://cdn.thoth.pub/frontcover.png';

        $publication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData'])
            ->getMock();
        $publication->method('getData')
            ->willReturnCallback(function ($key) {
                return [
                    'thothUploadFrontcover' => true,
                    'thothFrontcoverSha256' => null,
                ][$key] ?? null;
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
                'declaredExtension' => 'png',
                'declaredMimeType' => 'image/png',
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
            ->setMethods(['canUploadFrontcover', 'resolveFrontcoverFile', 'saveUploadData'])
            ->getMock();
        $service->method('canUploadFrontcover')->willReturn(true);
        $service->method('resolveFrontcoverFile')
            ->with($publication)
            ->willReturn([
                'path' => $frontcoverPath,
                'extension' => 'png',
                'mimeType' => 'image/png',
                'sha256' => $frontcoverSha256,
            ]);
        $service->expects($this->once())
            ->method('saveUploadData')
            ->with($publication, $frontcoverSha256, $cdnUrl);

        $service->sync($publication, 'work-id');
    }

    private function createTemporaryPng()
    {
        $filePath = tempnam(sys_get_temp_dir(), 'thoth-frontcover-') . '.png';
        file_put_contents(
            $filePath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
        );
        $this->temporaryFiles[] = $filePath;

        return $filePath;
    }
}
