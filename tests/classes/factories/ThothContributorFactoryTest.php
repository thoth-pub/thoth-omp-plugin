<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothContributorFactoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
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

require_once(__DIR__ . '/../../../vendor/autoload.php');

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Models\Contributor as ThothContributor;

import('plugins.generic.thoth.classes.factories.ThothContributorFactory');

class ThothContributorFactoryTest extends PKPTestCase
{
    private function setUpMockEnvironment()
    {
        $mockAuthor = $this->getMockBuilder(Author::class)
            ->setMethods([
                'getFullName',
                'getLocalizedFamilyName',
                'getLocalizedGivenName',
                'getOrcid',
                'getUrl'
            ])
            ->getMock();
        $mockAuthor->expects($this->any())
            ->method('getFullName')
            ->will($this->returnValue('John Doe'));
        $mockAuthor->expects($this->any())
            ->method('getLocalizedFamilyName')
            ->will($this->returnValue('Doe'));
        $mockAuthor->expects($this->any())
            ->method('getLocalizedGivenName')
            ->will($this->returnValue('John'));
        $mockAuthor->expects($this->any())
            ->method('getOrcid')
            ->will($this->returnValue('https://orcid.org/0000-0001-2345-678X'));
        $mockAuthor->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue('https://john.doe.org/'));

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
