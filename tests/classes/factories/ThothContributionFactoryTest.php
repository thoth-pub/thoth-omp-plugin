<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothContributionFactoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionFactoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothContributionFactory
 *
 * @brief Test class for the ThothContributionFactory class
 */

use ThothApi\GraphQL\Models\Contribution as ThothContribution;

import('classes.monograph.Author');
import('lib.pkp.classes.security.UserGroup');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothContributionFactory');

class ThothContributionFactoryTest extends PKPTestCase
{
    private function setUpMockEnvironment()
    {
        $mockUserGroup = $this->getMockBuilder(UserGroup::class)
            ->setMethods(['getData'])
            ->getMock();
        $mockUserGroup->expects($this->any())
            ->method('getData')
            ->will($this->returnValueMap([
                ['nameLocaleKey', null, 'default.groups.name.author'],
            ]));

        $mockAuthor = $this->getMockBuilder(Author::class)
            ->setMethods([
                'getFullName',
                'getId',
                'getLocalizedBiography',
                'getLocalizedData',
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
            ->method('getLocalizedData')
            ->will($this->returnValueMap([
                ['familyName', null, 'Doe'],
            ]));
        $mockAuthor->expects($this->any())
            ->method('getLocalizedGivenName')
            ->will($this->returnValue('John'));
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
        $thothContribution = $factory->createFromAuthor($mockAuthor, $primaryContactId);

        $this->assertEquals(new ThothContribution([
            'contributionType' => ThothContribution::CONTRIBUTION_TYPE_AUTHOR,
            'mainContribution' => true,
            'contributionOrdinal' => 1,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
            'biography' => 'This is my author biography'
        ]), $thothContribution);
    }
}
