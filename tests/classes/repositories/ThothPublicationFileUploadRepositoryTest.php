<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothPublicationFileUploadRepositoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationFileUploadRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothPublicationFileUploadRepository
 *
 * @brief Test class for the ThothPublicationFileUploadRepository class
 */

namespace APP\plugins\generic\thoth\tests\classes\repositories;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\repositories\ThothPublicationFileUploadRepository;
use Mockery;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Inputs\CompleteFileUpload;
use ThothApi\GraphQL\Inputs\NewPublicationFileUpload;
use ThothApi\GraphQL\Schemas\File;
use ThothApi\GraphQL\Schemas\FileUploadResponse;

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

        $mockThothClient = Mockery::mock(ThothClient::class);
        $repository = new ThothPublicationFileUploadRepository($mockThothClient);

        $thothPublicationFileUpload = $repository->new($data);

        $this->assertInstanceOf(NewPublicationFileUpload::class, $thothPublicationFileUpload);
        $this->assertSame($data, $thothPublicationFileUpload->getAllData());
    }

    public function testInitThothPublicationFileUpload()
    {
        $newPublicationFileUpload = new NewPublicationFileUpload([
            'publicationId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'declaredExtension' => 'pdf',
            'declaredMimeType' => 'application/pdf',
            'declaredSha256' => 'd7a8fbb90080857cf2a444f86405340c65c1b1e9b3d9c1b1e9b3d9c1b1e9b3d9',
        ]);
        $fileUploadResponse = new FileUploadResponse([
            'fileUploadId' => '123e4567-e89b-12d3-a456-426614174000',
            'uploadUrl' => 'https://thoth.example.com/upload/123e4567-e89b-12d3-a456-426614174000',
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('initPublicationFileUpload')
            ->once()
            ->with($newPublicationFileUpload, Mockery::type('array'))
            ->andReturn($fileUploadResponse);
        $repository = new ThothPublicationFileUploadRepository($mockThothClient);

        $response = $repository->init($newPublicationFileUpload);

        $this->assertSame($fileUploadResponse, $response);
    }

    public function testCompleteThothPublicationFileUpload()
    {
        $fileUploadId = '123e4567-e89b-12d3-a456-426614174000';
        $expectedFile = new File([
            'fileId' => '0c333e20-09f9-4f32-9f8f-20e801437dba',
            'publicationId' => 'f214b70e-9c0d-4a1f-a254-c8f426783dfd',
            'objectKey' => '10.12345/123.pdf',
            'cdnUrl' => 'https://example.thoth.pub/10.12345/123.pdf',
            'mimeType' => 'application/pdf',
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('completeFileUpload')
            ->once()
            ->with(
                Mockery::on(function ($completeFileUpload) use ($fileUploadId) {
                    return $completeFileUpload instanceof CompleteFileUpload
                        && $completeFileUpload->getFileUploadId() === $fileUploadId;
                }),
                Mockery::type('array')
            )
            ->andReturn($expectedFile);
        $repository = new ThothPublicationFileUploadRepository($mockThothClient);

        $response = $repository->complete($fileUploadId);

        $this->assertSame($expectedFile, $response);
    }
}
