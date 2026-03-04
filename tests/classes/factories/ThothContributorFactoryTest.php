<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothContributorFactoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributorFactoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothContributorFactory
 *
 * @brief Test class for the ThothContributorFactory class
 */

namespace APP\plugins\generic\thoth\tests\classes\factories;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\author\Author;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Models\Contributor as ThothContributor;
use APP\plugins\generic\thoth\classes\factories\ThothContributorFactory;

class ThothContributorFactoryTest extends PKPTestCase
{
    protected array $mock = [];
    private function setUpMockEnvironment()
    {
        $mockAuthor = $this->getMockBuilder(Author::class)
            ->onlyMethods([
                'getFullName',
                'getLocalizedFamilyName',
                'getLocalizedGivenName',
                'getOrcid',
                'getUrl'
            ])
            ->getMock();
        $mockAuthor->expects($this->any())
            ->method('getFullName')
            ->willReturn('John Doe');
        $mockAuthor->expects($this->any())
            ->method('getLocalizedFamilyName')
            ->willReturn('Doe');
        $mockAuthor->expects($this->any())
            ->method('getLocalizedGivenName')
            ->willReturn('John');
        $mockAuthor->expects($this->any())
            ->method('getOrcid')
            ->willReturn('https://orcid.org/0000-0001-2345-678X');
        $mockAuthor->expects($this->any())
            ->method('getUrl')
            ->willReturn('https://john.doe.org/');

        $this->mock = [];
        $this->mock['author'] = $mockAuthor;
    }

    public function testCreateThothContributorFromAuthor()
    {
        $this->setUpMockEnvironment();
        $mockAuthor = $this->mock['author'];

        $factory = new ThothContributorFactory();
        $thothContributor = $factory->createFromAuthor($mockAuthor);

        $this->assertEquals(new ThothContributor([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'fullName' => 'John Doe',
            'orcid' => 'https://orcid.org/0000-0001-2345-678X',
            'website' => 'https://john.doe.org/'
        ]), $thothContributor);
    }
}
