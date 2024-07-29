<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothContributorServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributorServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothContributorService
 *
 * @brief Test class for the ThothContributorService class
 */

import('lib.pkp.tests.PKPTestCase');
import('classes.monograph.Author');
import('plugins.generic.thoth.classes.services.ThothContributorService');

class ThothContributorServiceTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->contributorService = new ThothContributorService();
    }

    protected function tearDown(): void
    {
        unset($this->contributorService);
        parent::tearDown();
    }

    public function testGetContributorsPropertiesByAuthor()
    {
        $expectedProps = [
            'fullName' => 'Chantal Allan',
            'firstName' => 'Chantal',
            'lastName' => 'Allan',
            'orcid' => 'https://orcid.org/0000-0002-1825-0097',
            'website' => 'https://sites.google.com/site/chantalallan'
        ];

        $author = new Author();
        $author->setGivenName('Chantal', 'en_US');
        $author->setFamilyName('Allan', 'en_US');
        $author->setOrcid('https://orcid.org/0000-0002-1825-0097');
        $author->setUrl('https://sites.google.com/site/chantalallan');

        $contributorProps = $this->contributorService->getPropertiesByAuthor($author);

        $this->assertEquals($expectedProps, $contributorProps);
    }

    public function testCreateNewContributor()
    {
        $expectedContributor = new ThothContributor();
        $expectedContributor->setFirstName('Brian');
        $expectedContributor->setLastName('Dupuis');
        $expectedContributor->setFullName('Brian Dupuis');

        $params = [
            'firstName' => 'Brian',
            'lastName' => 'Dupuis',
            'fullName' => 'Brian Dupuis'
        ];

        $contributor = $this->contributorService->new($params);
        $this->assertEquals($expectedContributor, $contributor);
    }
}
