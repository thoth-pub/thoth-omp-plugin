<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothContributorRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributorRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothContributorRepository
 *
 * @brief Test class for the ThothContributorRepository class
 */

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Contributor as ThothContributor;

import('plugins.generic.thoth.classes.repositories.ThothContributorRepository');

class ThothContributorRepositoryTest extends PKPTestCase
{
    public function testNewThothContributor()
    {
        $data = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
            'website' => 'https://john.doe.org/',
            'orcid' => '0000-0001-2345-678X'
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothContributorRepository($mockThothClient);

        $thothContributor = $repository->new($data);
        $this->assertSame($data, $thothContributor->getAllData());
    }

    public function testGetContributor()
    {
        $expectedThothContributor = new ThothContributor([
            'contributorId' => '1cb32ce1-8844-4b7a-b7bc-c3d8d14b2f75',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
            'website' => 'https://john.doe.org/',
            'orcid' => '0000-0001-2345-678X'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['contributor'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('contributor')
            ->will($this->returnValue($expectedThothContributor));

        $repository = new ThothContributorRepository($mockThothClient);
        $thothContributor = $repository->get('1cb32ce1-8844-4b7a-b7bc-c3d8d14b2f75');

        $this->assertEquals($expectedThothContributor, $thothContributor);
    }

    public function testFindContributor()
    {
        $expectedThothContributor = new ThothContributor([
            'contributorId' => '57bf024a-a4fd-448a-98d4-029054149103',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
            'website' => 'https://john.doe.org/',
            'orcid' => '0000-0001-2345-678X'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['contributors'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('contributors')
            ->will($this->returnValue([$expectedThothContributor]));

        $repository = new ThothContributorRepository($mockThothClient);
        $thothContributor = $repository->find('0000-0001-2345-678X');

        $this->assertEquals($expectedThothContributor, $thothContributor);
    }

    public function testAddContributor()
    {
        $thothContributor = new ThothContributor([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
            'website' => 'https://john.doe.org/',
            'orcid' => '0000-0001-2345-678X'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createContributor'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createContributor')
            ->will($this->returnValue('6b029723-167d-4cc5-8710-7634b9547565'));

        $repository = new ThothContributorRepository($mockThothClient);
        $thothContributorId = $repository->add($thothContributor);

        $this->assertEquals('6b029723-167d-4cc5-8710-7634b9547565', $thothContributorId);
    }

    public function testEditContributor()
    {
        $thothPatchContributor = new ThothContributor([
            'contributorId' => '0856c196-3092-4410-ad6b-5eaab989e47f',
            'firstName' => 'Johnathan',
            'lastName' => 'Doe',
            'fullName' => 'Johnathan Doe',
            'website' => 'https://johnathan.doe.org/',
            'orcid' => '0000-0001-2345-678X'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['updateContributor'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updateContributor')
            ->will($this->returnValue('0856c196-3092-4410-ad6b-5eaab989e47f'));

        $repository = new ThothContributorRepository($mockThothClient);
        $thothContributorId = $repository->edit($thothPatchContributor);

        $this->assertEquals('0856c196-3092-4410-ad6b-5eaab989e47f', $thothContributorId);
    }

    public function testDeleteContributor()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['deleteContributor'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deleteContributor')
            ->will($this->returnValue('f6cc1494-151c-4850-8b47-9e15bf0ed307'));

        $repository = new ThothContributorRepository($mockThothClient);
        $thothContributorId = $repository->delete('f6cc1494-151c-4850-8b47-9e15bf0ed307');

        $this->assertEquals('f6cc1494-151c-4850-8b47-9e15bf0ed307', $thothContributorId);
    }
}
