<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothPublicationFileUploadRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationFileUploadRepositoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothPublicationFileUploadRepository
 *
 * @brief Test class for the ThothPublicationFileUploadRepository class
 */

use ThothApi\GraphQL\Inputs\CompleteFileUpload;
use ThothApi\GraphQL\Inputs\NewPublicationFileUpload;
use ThothApi\GraphQL\Schemas\File;
use ThothApi\GraphQL\Schemas\FileUploadResponse;
use ThothApi\GraphQL\Schemas\UploadRequestHeader;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothPublicationFileUploadRepository');

class ThothPublicationFileUploadRepositoryTest extends PKPTestCase
{
    public function testNewThothPublicationFileUpload()
    {
        $data = [
            'publicationId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'declaredExtension' => 'pdf',
            'declaredMimeType' => 'application/pdf',
            'declaredSha256' => 'd7a8fbb90080857cf2a444f86405340c65c1b1e9b3d9c1b1e9b3d9c1b1e9b3d9',
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothPublicationFileUploadRepository($mockThothClient);

        $thothPublicationFileUpload = $repository->new($data);

        $this->assertInstanceOf(NewPublicationFileUpload::class, $thothPublicationFileUpload);
        $this->assertSame($data['publicationId'], $thothPublicationFileUpload->getPublicationId());
        $this->assertSame($data['declaredExtension'], $thothPublicationFileUpload->getDeclaredExtension());
        $this->assertSame($data['declaredMimeType'], $thothPublicationFileUpload->getDeclaredMimeType());
        $this->assertSame($data['declaredSha256'], $thothPublicationFileUpload->getDeclaredSha256());
    }

    public function testInitThothPublicationFileUpload()
    {
        $data = [
            'publicationId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'declaredExtension' => 'pdf',
            'declaredMimeType' => 'application/pdf',
            'declaredSha256' => 'd7a8fbb90080857cf2a444f86405340c65c1b1e9b3d9c1b1e9b3d9c1b1e9b3d9',
        ];

        $newPublicationFileUpload = new NewPublicationFileUpload($data);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['initPublicationFileUpload'])
            ->getMock();
        $repository = new ThothPublicationFileUploadRepository($mockThothClient);
        $mockThothClient->expects($this->once())
            ->method('initPublicationFileUpload')
            ->with($newPublicationFileUpload)
            ->willReturn(new FileUploadResponse([
                'fileUploadId' => '123e4567-e89b-12d3-a456-426614174000',
                'uploadUrl' => 'https://thoth.example.com/upload/123e4567-e89b-12d3-a456-426614174000',
                'uploadHeaders' => [
                    new UploadRequestHeader([
                        'name' => 'Content-Type',
                        'value' => 'application/pdf',
                    ]),
                    new UploadRequestHeader([
                        'name' => 'x-amz-checksum-sha256',
                        'value' => 'c3ab8ff13720e8ad9047dd39466b3c8974e592c2fa383d4a3960714caef0c4f2',
                    ]),
                    new UploadRequestHeader([
                        'name' => 'x-amz-sdk-checksum-algorithm',
                        'value' => 'SHA256',
                    ]),
                ],
                'expiresAt' => '2020-01-01T12:00:10.00000000Z'
            ]));

        $response = $repository->init($newPublicationFileUpload);

        $this->assertInstanceOf(FileUploadResponse::class, $response);
    }

    public function testCompleteThothPublicationFileUpload()
    {
        $fileUploadId = '123e4567-e89b-12d3-a456-426614174000';

        $expectedFile = new File([
                'fileId' => '0c333e20-09f9-4f32-9f8f-20e801437dba',
                'fileType' => 'PUBLICATION',
                'workId' => null,
                'publicationId' => 'f214b70e-9c0d-4a1f-a254-c8f426783dfd',
                'additionalResourceId' => null,
                'workFeaturedVideoId' => null,
                'objectKey' => '10.12345/123.pdf',
                'cdnUrl' => 'https://example.thoth.pub/10.12345/123.pdf',
                'mimeType' => 'application/pdf',
                'bytes' => 2358735,
                'sha256' => 'b5bb9d8014a0f9b1d61e21e796d78dccdf1352f23cd32812f4850b878ae4944c',
            ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['completeFileUpload'])
            ->getMock();
        $repository = new ThothPublicationFileUploadRepository($mockThothClient);
        $mockThothClient->expects($this->once())
            ->method('completeFileUpload')
            ->with($fileUploadId)
            ->willReturn($expectedFile);

        $response = $repository->complete($fileUploadId);

        $this->assertSame($expectedFile, $response);
    }
}
