<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothLocationServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothLocationService
 *
 * @brief Test class for the ThothLocationService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

use APP\plugins\generic\thoth\classes\factories\ThothLocationFactory;
use APP\plugins\generic\thoth\classes\repositories\ThothLocationRepository;
use APP\plugins\generic\thoth\classes\services\ThothLocationService;
use APP\publicationFormat\PublicationFormat;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Location as ThothLocation;

class ThothLocationServiceTest extends PKPTestCase
{
    public function testRegisterLocation()
    {
        $mockFactory = $this->getMockBuilder(ThothLocationFactory::class)
            ->onlyMethods(['createFromPublicationFormat'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublicationFormat')
            ->willReturn(new ThothLocation());

        $mockRepository = $this->getMockBuilder(ThothLocationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['hasCanonical', 'add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('hasCanonical')
            ->willReturn(true);
        $mockRepository->expects($this->once())
            ->method('add')
            ->willReturn('6f40cf3f-c7eb-437a-9c09-08a7f6923ec0');

        $mockPubFormat = $this->getMockBuilder(PublicationFormat::class)->getMock();

        $thothPublicationId = '75ce9d60-1397-439c-90ad-80ee49630a70';
        $fileId = 1;

        $service = new ThothLocationService($mockFactory, $mockRepository);
        $thothLocationId = $service->register($mockPubFormat, $thothPublicationId, $fileId);

        $this->assertSame('6f40cf3f-c7eb-437a-9c09-08a7f6923ec0', $thothLocationId);
    }
}
