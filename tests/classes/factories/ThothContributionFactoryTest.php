<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothContributionFactoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
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

namespace APP\plugins\generic\thoth\tests\classes\factories;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\factories\ThothContributionFactory;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Enums\ContributionType;
use ThothApi\GraphQL\Inputs\PatchContribution as ThothContribution;

class ThothContributionFactoryTest extends PKPTestCase
{
    protected array $mocks = [];
    private function setUpMockEnvironment(string $givenName = 'John')
    {
        $mockUserGroup = new class () {
            public $nameLocaleKey = 'default.groups.name.author';
        };

        $mockAuthor = $this->getMockBuilder(\APP\author\Author::class)
            ->onlyMethods([
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
            ->willReturn('John Doe');
        $mockAuthor->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $mockAuthor->expects($this->any())
            ->method('getLocalizedBiography')
            ->willReturn('This is my author biography');
        $mockAuthor->expects($this->any())
            ->method('getLocalizedFamilyName')
            ->willReturn('Doe');
        $mockAuthor->expects($this->any())
            ->method('getLocalizedGivenName')
            ->willReturn($givenName);
        $mockAuthor->expects($this->any())
            ->method('getSequence')
            ->willReturn(0);
        $mockAuthor->expects($this->any())
            ->method('getUserGroup')
            ->willReturn($mockUserGroup);

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
