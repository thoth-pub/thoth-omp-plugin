<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothContributionFactoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionFactoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothContributionFactory
 *
 * @brief Test class for the ThothContributionFactory class
 */

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Enums\ContributionType;
use ThothApi\GraphQL\Inputs\PatchContribution as ThothContribution;

import('plugins.generic.thoth.classes.factories.ThothContributionFactory');

class ThothContributionFactoryTest extends PKPTestCase
{
    private function setUpMockEnvironment(string $givenName = 'John')
    {
        $mockUserGroup = $this->getMockBuilder(\PKP\userGroup\UserGroup::class)
            ->setMethods(['getData'])
            ->getMock();
        $mockUserGroup->expects($this->any())
            ->method('getData')
            ->will($this->returnValueMap([
                ['nameLocaleKey', null, 'default.groups.name.author'],
            ]));

        $mockAuthor = $this->getMockBuilder(\APP\author\Author::class)
            ->setMethods([
                'getFullName',
                'getId',
                'getLocalizedBiography',
                'getLocalizedFamilyName',
                'getLocalizedGivenName',
                'getSequence',
                'getUserGroup',
            ])
            ->getMock();
        $mockAuthor->expects($this->any())
            ->method('getFullName')
            ->will($this->returnValue('John Doe'));
        $mockAuthor->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $mockAuthor->expects($this->any())
            ->method('getLocalizedBiography')
            ->will($this->returnValue('This is my author biography'));
        $mockAuthor->expects($this->any())
            ->method('getLocalizedFamilyName')
            ->will($this->returnValue('Doe'));
        $mockAuthor->expects($this->any())
            ->method('getLocalizedGivenName')
            ->will($this->returnValue($givenName));
        $mockAuthor->expects($this->any())
            ->method('getSequence')
            ->will($this->returnValue(0));
        $mockAuthor->expects($this->any())
            ->method('getUserGroup')
            ->will($this->returnValue($mockUserGroup));

        $this->mocks = [];
        $this->mocks['author'] = $mockAuthor;
    }

    public function testCreateThothContributionFromAuthor()
    {
        $this->setUpMockEnvironment();
        $mockAuthor = $this->mocks['author'];
        $primaryContactId = 1;

        $factory = new ThothContributionFactory();
        $thothContribution = $factory->createFromAuthor($mockAuthor, 0, $primaryContactId);

        $this->assertEquals(new ThothContribution([
            'contributionType' => ContributionType::AUTHOR,
            'mainContribution' => true,
            'contributionOrdinal' => 1,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
        ]), $thothContribution);
    }

    public function testCreateThothContributionOmitsEmptyOptionalMetadata()
    {
        $this->setUpMockEnvironment('');

        $factory = new ThothContributionFactory();
        $data = $factory->createFromAuthor($this->mocks['author'], 0, 1)->getAllData();

        $this->assertArrayNotHasKey('firstName', $data);
    }
}
