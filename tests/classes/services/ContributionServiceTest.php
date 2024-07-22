<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ContributionServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContributionServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ContributionService
 *
 * @brief Test class for the ContributionService class
 */

import('lib.pkp.tests.PKPTestCase');
import('classes.monograph.Author');
import('classes.publication.Publication');
import('plugins.generic.thoth.classes.services.ContributionService');

class ContributionServiceTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->contributionService = new ContributionService();
        $this->setUpMockEnvironment();
    }

    protected function getMockedDAOs()
    {
        return ['UserGroupDAO', 'PublicationDAO'];
    }

    private function setUpMockEnvironment()
    {
        $userGroupMockDao = $this->getMockBuilder(UserGroupDAO::class)
            ->setMethods(['getById'])
            ->getMock();

        $userGroup = new UserGroup();
        $userGroup->setData('nameLocaleKey', 'default.groups.name.author');

        $userGroupMockDao->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($userGroup));

        DAORegistry::registerDAO('UserGroupDAO', $userGroupMockDao);

        $publicationMockDao = $this->getMockBuilder(PublicationDAO::class)
            ->setMethods(['getById'])
            ->getMock();

        $publication = new Publication();
        $publication->setData('primaryContactId', 7);

        $publicationMockDao->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($publication));

        DAORegistry::registerDAO('PublicationDAO', $publicationMockDao);
    }

    protected function tearDown(): void
    {
        unset($this->contributionService);
        parent::tearDown();
    }

    public function testGettingContributionTypeByUserGroupLocaleKey()
    {
        $this->assertEquals(
            Contribution::CONTRIBUTION_TYPE_AUTHOR,
            $this->contributionService->getContributionTypeByUserGroupLocaleKey('default.groups.name.author')
        );
        $this->assertEquals(
            Contribution::CONTRIBUTION_TYPE_AUTHOR,
            $this->contributionService->getContributionTypeByUserGroupLocaleKey('default.groups.name.chapterAuthor')
        );
        $this->assertEquals(
            Contribution::CONTRIBUTION_TYPE_EDITOR,
            $this->contributionService->getContributionTypeByUserGroupLocaleKey('default.groups.name.volumeEditor')
        );
        $this->assertEquals(
            Contribution::CONTRIBUTION_TYPE_TRANSLATOR,
            $this->contributionService->getContributionTypeByUserGroupLocaleKey('default.groups.name.translator')
        );
    }

    public function testGetPropertiesByAuthor()
    {
        $expectedProps = [
            'contributionType' => Contribution::CONTRIBUTION_TYPE_AUTHOR,
            'mainContribution' => true,
            'contributionOrdinal' => 1,
            'firstName' => 'Reza',
            'lastName' => 'Negarestani',
            'fullName' => 'Reza Negarestani',
            'biography' => 'Reza Negarestani is a philosopher. His current philosophical project is focused on ' .
                'rationalist universalism beginning with the evolution of the modern system of knowledge and ' .
                'advancing toward contemporary philosophies of rationalism.'
        ];

        $author = new Author();
        $author->setId(7);
        $author->setGivenName('Reza', 'en_US');
        $author->setFamilyName('Negarestani', 'en_US');
        $author->setSequence(0);
        $author->setUserGroupId(2);
        $author->setBiography(
            'Reza Negarestani is a philosopher. His current philosophical project is focused on rationalist ' .
            'universalism beginning with the evolution of the modern system of knowledge and ' .
            'advancing toward contemporary philosophies of rationalism.',
            'en_US'
        );

        $contributionProps = $this->contributionService->getPropertiesByAuthor($author);
        $this->assertEquals($expectedProps, $contributionProps);
    }

    public function testCreateNewContribution()
    {
        $expectedContribution = new Contribution();
        $expectedContribution->setContributionType(Contribution::CONTRIBUTION_TYPE_EDITOR);
        $expectedContribution->setMainContribution(false);
        $expectedContribution->setContributionOrdinal(3);
        $expectedContribution->setLastName('Steyerl');
        $expectedContribution->setFullName('Hito Steyerl');

        $params = [
            'contributionType' => Contribution::CONTRIBUTION_TYPE_EDITOR,
            'mainContribution' => false,
            'contributionOrdinal' => 3,
            'lastName' => 'Steyerl',
            'fullName' => 'Hito Steyerl'
        ];

        $contribution = $this->contributionService->new($params);
        $this->assertEquals($expectedContribution, $contribution);
    }
}
