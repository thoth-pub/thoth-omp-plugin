<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ContributorServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContributorServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ContributorService
 *
 * @brief Test class for the ContributorService class
 */

import('lib.pkp.tests.PKPTestCase');
import('classes.monograph.Author');
import('plugins.generic.thoth.classes.services.ContributorService');

class ContributorServiceTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->contributorService = new ContributorService();
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
        $expectedContributor = new Contributor();
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
