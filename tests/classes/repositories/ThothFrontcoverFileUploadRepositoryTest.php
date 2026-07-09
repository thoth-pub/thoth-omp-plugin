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
use ThothApi\GraphQL\Inputs\NewFrontcoverFileUpload;
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
}
