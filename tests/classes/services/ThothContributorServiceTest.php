<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothContributorServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributorServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothContributorService
 *
 * @brief Test class for the ThothContributorService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

use APP\author\Author;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Contributor as ThothContributor;
use APP\plugins\generic\thoth\classes\factories\ThothContributorFactory;
use APP\plugins\generic\thoth\classes\services\ThothContributorService;
use APP\plugins\generic\thoth\classes\repositories\ThothContributorRepository;

class ThothContributorServiceTest extends PKPTestCase
{
    public function testRegisterContributor()
    {
        $mockFactory = $this->getMockBuilder(ThothContributorFactory::class)
            ->onlyMethods(['createFromAuthor'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromAuthor')
            ->willReturn(new ThothContributor());

        $mockRepository = $this->getMockBuilder(ThothContributorRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->willReturn('1a1f6581-9c66-4292-9afc-176060dc3e8a');

        $mockAuthor = $this->getMockBuilder(Author::class)->getMock();

        $service = new ThothContributorService($mockFactory, $mockRepository);
        $thothContributorId = $service->register($mockAuthor);

        $this->assertSame('1a1f6581-9c66-4292-9afc-176060dc3e8a', $thothContributorId);
    }
}
