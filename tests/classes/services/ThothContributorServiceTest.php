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

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Contributor as ThothContributor;

import('plugins.generic.thoth.classes.factories.ThothContributorFactory');
import('plugins.generic.thoth.classes.services.ThothContributorService');
import('plugins.generic.thoth.classes.repositories.ThothContributorRepository');

class ThothContributorServiceTest extends PKPTestCase
{
    public function testRegisterContributor()
    {
        $mockFactory = $this->getMockBuilder(ThothContributorFactory::class)
            ->setMethods(['createFromAuthor'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromAuthor')
            ->will($this->returnValue(new ThothContributor()));

        $mockRepository = $this->getMockBuilder(ThothContributorRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('1a1f6581-9c66-4292-9afc-176060dc3e8a'));

        $mockAuthor = $this->getMockBuilder(Author::class)->getMock();

        $service = new ThothContributorService($mockFactory, $mockRepository);
        $thothContributorId = $service->register($mockAuthor);

        $this->assertSame('1a1f6581-9c66-4292-9afc-176060dc3e8a', $thothContributorId);
    }
}
