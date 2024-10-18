<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothContributionServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
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

use APP\publication\Repository as PublicationRepository;
use PKP\tests\PKPTestCase;
use PKP\userGroup\Repository as UserGroupRepository;

import('plugins.generic.thoth.classes.services.ThothContributionService');
import('plugins.generic.thoth.lib.thothAPI.ThothClient');

class ThothContributionServiceTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->contributionService = new ThothContributionService();
        $this->setUpMockEnvironment();
    }

    protected function tearDown(): void
    {
        unset($this->contributionService);
        parent::tearDown();
    }

    protected function getMockedContainerKeys(): array
    {
        return [...parent::getMockedContainerKeys(), UserGroupRepository::class, PublicationRepository::class];
    }

    private function setUpMockEnvironment()
    {
        $userGroupRepoMock = Mockery::mock(app(UserGroupRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn(
                Mockery::mock(\PKP\userGroup\UserGroup::class)
                    ->shouldReceive([
                        'getId' => 1
                    ])
                    ->shouldReceive('getData')
                    ->with('nameLocaleKey')
                    ->andReturn('default.groups.name.author')
                    ->getMock()
            )
            ->getMock();

        app()->instance(UserGroupRepository::class, $userGroupRepoMock);

        $publicationRepoMock = Mockery::mock(app(PublicationRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn(
                Mockery::mock(\APP\publication\Publication::class)
                    ->shouldReceive('getData')
                    ->with('primaryContactId')
                    ->andReturn(1)
                    ->getMock()
            )
            ->getMock();

        app()->instance(PublicationRepository::class, $publicationRepoMock);
    }

    public function testGettingContributionTypeByUserGroupLocaleKey()
    {
        $this->assertEquals(
            ThothContribution::CONTRIBUTION_TYPE_AUTHOR,
            $this->contributionService->getContributionTypeByUserGroupLocaleKey('default.groups.name.author')
        );
        $this->assertEquals(
            ThothContribution::CONTRIBUTION_TYPE_AUTHOR,
            $this->contributionService->getContributionTypeByUserGroupLocaleKey('default.groups.name.chapterAuthor')
        );
        $this->assertEquals(
            ThothContribution::CONTRIBUTION_TYPE_EDITOR,
            $this->contributionService->getContributionTypeByUserGroupLocaleKey('default.groups.name.volumeEditor')
        );
        $this->assertEquals(
            ThothContribution::CONTRIBUTION_TYPE_TRANSLATOR,
            $this->contributionService->getContributionTypeByUserGroupLocaleKey('default.groups.name.translator')
        );
    }

    public function testCreateNewContributionByAuthor()
    {
        $authorMock = Mockery::mock(\APP\author\Author::class)
            ->makePartial()
            ->shouldReceive([
                'getId' => 1,
                'getUserGroupId' => 1,
                'getSequence' => 0,
                'getLocalizedGivenName' => 'Reza',
                'getFullName' => 'Reza Negarestani',
                'getLocalizedBiography' => 'Reza Negarestani is a philosopher. His current philosophical project ' .
                    'is focused on rationalist universalism beginning with the evolution of the modern system of ' .
                    'knowledge and advancing toward contemporary philosophies of rationalism.',
            ])
            ->shouldReceive('getLocalizedData')
            ->with('familyName')
            ->andReturn('Negarestani')
            ->shouldReceive('getData')
            ->with('publicationId')
            ->andReturn(1)
            ->getMock();

        $expectedContribution = new ThothContribution();
        $expectedContribution->setContributionType(ThothContribution::CONTRIBUTION_TYPE_AUTHOR);
        $expectedContribution->setMainContribution(true);
        $expectedContribution->setContributionOrdinal(1);
        $expectedContribution->setFirstName('Reza');
        $expectedContribution->setLastName('Negarestani');
        $expectedContribution->setFullName('Reza Negarestani');
        $expectedContribution->setBiography(
            'Reza Negarestani is a philosopher. His current philosophical project is focused on ' .
            'rationalist universalism beginning with the evolution of the modern system of knowledge and ' .
            'advancing toward contemporary philosophies of rationalism.'
        );

        $contribution = $this->contributionService->newByAuthor($authorMock);
        $this->assertEquals($expectedContribution, $contribution);
    }

    public function testCreateNewContribution()
    {
        $expectedContribution = new ThothContribution();
        $expectedContribution->setContributionType(ThothContribution::CONTRIBUTION_TYPE_EDITOR);
        $expectedContribution->setMainContribution(false);
        $expectedContribution->setContributionOrdinal(3);
        $expectedContribution->setLastName('Steyerl');
        $expectedContribution->setFullName('Hito Steyerl');

        $params = [
            'contributionType' => ThothContribution::CONTRIBUTION_TYPE_EDITOR,
            'mainContribution' => false,
            'contributionOrdinal' => 3,
            'lastName' => 'Steyerl',
            'fullName' => 'Hito Steyerl'
        ];

        $contribution = $this->contributionService->new($params);
        $this->assertEquals($expectedContribution, $contribution);
    }

    public function testRegisterContribution()
    {
        $expectedContribution = new ThothContribution();
        $expectedContribution->setId('67afac83-b015-4f32-9576-60b665a9e685');
        $expectedContribution->setWorkId('45a6622c-a306-4559-bb77-25367dc881b8');
        $expectedContribution->setContributorId('f70f709e-2137-4c87-a2e5-d52b263759ec');
        $expectedContribution->setContributionType(ThothContribution::CONTRIBUTION_TYPE_AUTHOR);
        $expectedContribution->setMainContribution(false);
        $expectedContribution->setContributionOrdinal(1);
        $expectedContribution->setFirstName('Michael');
        $expectedContribution->setLastName('Wilson');
        $expectedContribution->setFullName('Michael Wilson');

        $authorMock = Mockery::mock(\APP\author\Author::class)
            ->makePartial()
            ->shouldReceive([
                'getId' => 2,
                'getUserGroupId' => 4,
                'getSequence' => 0,
                'getLocalizedGivenName' => 'Michael',
                'getFullName' => 'Michael Wilson',
            ])
            ->shouldReceive('getLocalizedData')
            ->with('familyName')
            ->andReturn('Wilson')
            ->shouldReceive('getData')
            ->with('publicationId')
            ->andReturn(1)
            ->getMock();

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createContribution','contributors'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createContribution')
            ->will($this->returnValue('67afac83-b015-4f32-9576-60b665a9e685'));
        $mockThothClient->expects($this->any())
            ->method('contributors')
            ->will($this->returnValue([
                [
                    'contributorId' => 'f70f709e-2137-4c87-a2e5-d52b263759ec',
                    'lastName' => 'Wilson',
                    'fullName' => 'Michael Wilson'
                ]
            ]));

        $contribution = $this->contributionService->register(
            $mockThothClient,
            $authorMock,
            '45a6622c-a306-4559-bb77-25367dc881b8'
        );
        $this->assertEquals($expectedContribution, $contribution);
    }
}
