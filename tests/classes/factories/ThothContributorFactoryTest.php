<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothContributorFactoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributorFactoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothContributorFactory
 *
 * @brief Test class for the ThothContributorFactory class
 */

use ThothApi\GraphQL\Models\Contributor as ThothContributor;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothContributorFactory');

class ThothContributorFactoryTest extends PKPTestCase
{
    public function testCreateThothContributorFromAuthor()
    {
        $mockAuthor = $this->getMockBuilder(Author::class)
            ->setMethods([
                'getFullName',
                'getLocalizedData',
                'getLocalizedGivenName',
                'getOrcid',
                'getUrl'
            ])
            ->getMock();
        $mockAuthor->expects($this->any())
            ->method('getFullName')
            ->will($this->returnValue('John Doe'));
        $mockAuthor->expects($this->any())
            ->method('getLocalizedData')
            ->will($this->returnValueMap([
                ['familyName', null, 'Doe'],
            ]));
        $mockAuthor->expects($this->any())
            ->method('getLocalizedGivenName')
            ->will($this->returnValue('John'));
        $mockAuthor->expects($this->any())
            ->method('getOrcid')
            ->will($this->returnValue('https://orcid.org/0000-0001-2345-678X'));
        $mockAuthor->expects($this->any())
            ->method('getUrl')
            ->will($this->returnValue('https://john.doe.org/'));

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
