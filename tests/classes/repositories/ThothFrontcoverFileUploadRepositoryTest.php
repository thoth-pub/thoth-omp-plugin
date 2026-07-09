<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothFrontcoverFileUploadRepositoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothFrontcoverFileUploadRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothFrontcoverFileUploadRepository
 *
 * @brief Test class for the ThothFrontcoverFileUploadRepository class
 */

require_once(__DIR__ . '/../../../vendor/autoload.php');

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Inputs\CompleteFileUpload;
use ThothApi\GraphQL\Inputs\NewFrontcoverFileUpload;
use ThothApi\GraphQL\Schemas\File;
use ThothApi\GraphQL\Schemas\FileUploadResponse;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothFrontcoverFileUploadRepository');

class ThothFrontcoverFileUploadRepositoryTest extends PKPTestCase
{
    public function testNewThothFrontcoverFileUpload(): void
    {
        $data = [
            'workId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'declaredExtension' => 'jpg',
            'declaredMimeType' => 'image/jpeg',
            'declaredSha256' => 'd7a8fbb90080857cf2a444f86405340c65c1b1e9b3d9c1b1e9b3d9c1b1e9b3d9',
        ];

        $repository = new ThothFrontcoverFileUploadRepository($this->getMockBuilder(ThothClient::class)->getMock());

        $frontcoverFileUpload = $repository->new($data);

        $this->assertInstanceOf(NewFrontcoverFileUpload::class, $frontcoverFileUpload);
        $this->assertSame($data, $frontcoverFileUpload->getAllData());
    }

    public function testInitThothFrontcoverFileUpload(): void
    {
        $newFrontcoverFileUpload = new NewFrontcoverFileUpload([
            'workId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'declaredExtension' => 'jpg',
            'declaredMimeType' => 'image/jpeg',
            'declaredSha256' => 'd7a8fbb90080857cf2a444f86405340c65c1b1e9b3d9c1b1e9b3d9c1b1e9b3d9',
        ]);
        $fileUploadResponse = new FileUploadResponse([
            'fileUploadId' => '123e4567-e89b-12d3-a456-426614174000',
            'uploadUrl' => 'https://thoth.example.com/upload/123e4567-e89b-12d3-a456-426614174000',
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['initFrontcoverFileUpload'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('initFrontcoverFileUpload')
            ->with($newFrontcoverFileUpload, $this->isType('array'))
            ->willReturn($fileUploadResponse);
        $repository = new ThothFrontcoverFileUploadRepository($mockThothClient);

        $response = $repository->init($newFrontcoverFileUpload);

        $this->assertSame($fileUploadResponse, $response);
    }

    public function testCompleteThothFrontcoverFileUpload(): void
    {
        $fileUploadId = '123e4567-e89b-12d3-a456-426614174000';
        $expectedFile = new File([
            'fileId' => '0c333e20-09f9-4f32-9f8f-20e801437dba',
            'fileType' => 'FRONTCOVER',
            'workId' => 'f214b70e-9c0d-4a1f-a254-c8f426783dfd',
            'objectKey' => '10.12345/frontcover.png',
            'cdnUrl' => 'https://example.thoth.pub/10.12345/frontcover.png',
            'mimeType' => 'image/png',
            'bytes' => 2358735,
            'sha256' => 'b5bb9d8014a0f9b1d61e21e796d78dccdf1352f23cd32812f4850b878ae4944c',
        ]);

        $fakeThothClient = new class ($expectedFile) {
            public $completeFileUploadArgs = [];
            private $expectedFile;

            public function __construct(File $expectedFile)
            {
                $this->expectedFile = $expectedFile;
            }

            public function completeFileUpload($completeFileUpload, array $selection)
            {
                $this->completeFileUploadArgs = [$completeFileUpload, $selection];
                return $this->expectedFile;
            }
        };
        $repository = new ThothFrontcoverFileUploadRepository($fakeThothClient);

        $response = $repository->complete($fileUploadId);

        $this->assertSame($expectedFile, $response);
        $this->assertInstanceOf(CompleteFileUpload::class, $fakeThothClient->completeFileUploadArgs[0]);
        $this->assertSame($fileUploadId, $fakeThothClient->completeFileUploadArgs[0]->getFileUploadId());
        $this->assertIsArray($fakeThothClient->completeFileUploadArgs[1]);
    }
}
