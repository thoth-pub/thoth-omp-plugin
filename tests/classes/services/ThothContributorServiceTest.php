<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
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
use ThothApi\GraphQL\Inputs\PatchContributor as ThothContributor;

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

    public function testUpdateContributor()
    {
        $thothContributor = new ThothContributor(['fullName' => 'Updated Name']);
        $mockFactory = $this->getMockBuilder(ThothContributorFactory::class)
            ->setMethods(['createFromAuthor'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromAuthor')
            ->willReturn($thothContributor);

        $mockRepository = $this->getMockBuilder(ThothContributorRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['edit'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function ($contributor) {
                return $contributor->getContributorId() === 'contributor-id'
                    && $contributor->getFullName() === 'Updated Name';
            }))
            ->willReturn('contributor-id');

        $service = new ThothContributorService($mockFactory, $mockRepository);
        $result = $service->update($this->getMockBuilder(Author::class)->getMock(), 'contributor-id');

        $this->assertSame('contributor-id', $result);
    }
}
