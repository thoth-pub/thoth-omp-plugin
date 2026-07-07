<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/services/ThothContributionServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothContributionService
 *
 * @brief Test class for the ThothContributionService class
 */

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Inputs\PatchContribution as ThothContribution;
use ThothApi\GraphQL\Inputs\PatchContributor as ThothContributor;

import('plugins.generic.thoth.classes.factories.ThothContributionFactory');
import('plugins.generic.thoth.classes.repositories.ThothContributionRepository');
import('plugins.generic.thoth.classes.repositories.ThothContributorRepository');
import('plugins.generic.thoth.classes.services.ThothAffiliationService');
import('plugins.generic.thoth.classes.services.ThothBiographyService');
import('plugins.generic.thoth.classes.services.ThothContributionService');
import('plugins.generic.thoth.classes.services.ThothContributorService');

class ThothContributionServiceTest extends PKPTestCase
{
    public function testRegisterContribution()
    {
        $mockBiographyService = $this->createMock(ThothBiographyService::class);
        $mockBiographyService->expects($this->once())->method('registerByAuthor');

        $mockContributorRepository = $this->getMockBuilder(ThothContributorRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['find'])
            ->getMock();
        $mockContributorRepository->expects($this->once())
            ->method('find')
            ->will($this->returnValue(new ThothContributor()));

        $mockContributorService = $this->createMock(ThothContributorService::class);
        $mockContributorService->expects($this->never())->method('register');

        $mockAffiliationService = $this->createMock(ThothAffiliationService::class);
        $mockAffiliationService->expects($this->never())->method('register');

        $mockFactory = $this->getMockBuilder(ThothContributionFactory::class)
            ->setMethods(['createFromAuthor'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromAuthor')
            ->will($this->returnValue(new ThothContribution()));

        $mockRepository = $this->getMockBuilder(ThothContributionRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('e2d8dc3b-a5d9-4941-8ebd-52f0a70515bd'));

        $mockAuthor = new class () {
            public function getData($key)
            {
                $values = [
                    'locale' => 'en_US',
                    'biography' => [
                        'en_US' => 'English biography',
                        'pt_BR' => 'Biografia em portugues',
                    ],
                ];

                return $values[$key] ?? null;
            }

            public function getOrcid()
            {
                return null;
            }

            public function getFullName($usePrefix = false)
            {
                return 'John Doe';
            }
        };
        $thothWorkId = '97fcc25c-361b-46f9-8c4b-016bfa36fb6d';

        $service = new ThothContributionService(
            $mockFactory,
            $mockRepository,
            $mockContributorRepository,
            $mockContributorService,
            $mockBiographyService,
            $mockAffiliationService
        );
        $thothContributionId = $service->register($mockAuthor, 0, $thothWorkId);

        $this->assertSame('e2d8dc3b-a5d9-4941-8ebd-52f0a70515bd', $thothContributionId);
    }
}
