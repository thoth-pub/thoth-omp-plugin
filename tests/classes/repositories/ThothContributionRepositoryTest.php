<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothContributionRepositoryTest.php
 *
 * Copyright (c) 2025 Lepidus Tecnologia
 * Copyright (c) 2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionRepositoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothContributionRepository
 *
 * @brief Test class for the ThothContributionRepository class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Contribution as ThothContribution;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothContributionRepository');

class ThothContributionRepositoryTest extends PKPTestCase
{
    public function testNewThothContribution()
    {
        $data = [
            'contributionType' => ThothContribution::CONTRIBUTION_TYPE_AUTHOR,
            'mainContribution' => true,
            'contributionOrdinal' => 1,
            'lastName' => 'John',
            'fullName' => 'John Doe'
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothContributionRepository($mockThothClient);

        $thothContribution = $repository->new($data);
        $this->assertSame($data, $thothContribution->getAllData());
    }

    public function testGetContribution()
    {
        $expectedThothContribution = new ThothContribution([
            'contributionId' => '8d19d277-c42d-4bc4-b992-73174c7415e0',
            'contributionType' => ThothContribution::CONTRIBUTION_TYPE_AUTHOR,
            'mainContribution' => true,
            'contributionOrdinal' => 1,
            'lastName' => 'John',
            'fullName' => 'John Doe'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['contribution'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('contribution')
            ->will($this->returnValue($expectedThothContribution));

        $repository = new ThothContributionRepository($mockThothClient);
        $thothContribution = $repository->get('8d19d277-c42d-4bc4-b992-73174c7415e0');

        $this->assertEquals($expectedThothContribution, $thothContribution);
    }

    public function testAddContribution()
    {
        $thothContribution = new ThothContribution([
            'workId' => '66603c16-7f9f-440a-9584-09214491ec82',
            'contributorId' => '91350d60-b9e9-4083-a256-2d7acd6551e8',
            'contributionType' => ThothContribution::CONTRIBUTION_TYPE_AUTHOR,
            'mainContribution' => true,
            'contributionOrdinal' => 1,
            'lastName' => 'John',
            'fullName' => 'John Doe'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createContribution'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createContribution')
            ->will($this->returnValue('d2cea6e7-32d4-421c-9983-d4553a991efd'));

        $repository = new ThothContributionRepository($mockThothClient);
        $thothContributionId = $repository->add($thothContribution);

        $this->assertEquals('d2cea6e7-32d4-421c-9983-d4553a991efd', $thothContributionId);
    }

    public function testEditContribution()
    {
        $thothPatchContribution = new ThothContribution([
            'contributionId' => 'ffc5404d-6365-434b-920b-da446cc3556e',
            'workId' => '66603c16-7f9f-440a-9584-09214491ec82',
            'contributorId' => '91350d60-b9e9-4083-a256-2d7acd6551e8',
            'contributionType' => ThothContribution::CONTRIBUTION_TYPE_AUTHOR,
            'mainContribution' => true,
            'contributionOrdinal' => 1,
            'lastName' => 'Johnathan',
            'fullName' => 'Johnathan Doe'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['updateContribution'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updateContribution')
            ->will($this->returnValue('ffc5404d-6365-434b-920b-da446cc3556e'));

        $repository = new ThothContributionRepository($mockThothClient);
        $thothContributionId = $repository->edit($thothPatchContribution);

        $this->assertEquals('ffc5404d-6365-434b-920b-da446cc3556e', $thothContributionId);
    }

    public function testDeleteContribution()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['deleteContribution'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deleteContribution')
            ->will($this->returnValue('ffc5404d-6365-434b-920b-da446cc3556e'));

        $repository = new ThothContributionRepository($mockThothClient);
        $thothContributionId = $repository->delete('ffc5404d-6365-434b-920b-da446cc3556e');

        $this->assertEquals('ffc5404d-6365-434b-920b-da446cc3556e', $thothContributionId);
    }
}
