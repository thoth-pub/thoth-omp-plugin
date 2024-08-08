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
import('plugins.generic.thoth.thoth.ThothClient');

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

    public function testGetManyContributors()
    {
        $expectedContributors = [];
        $expectedContributors[] = new ThothContributor();
        $expectedContributors[0]->setId('59383141-fff9-46e2-bc66-f71e42189380');
        $expectedContributors[0]->setFirstName('Brenna Clarke');
        $expectedContributors[0]->setLastName('Gray');
        $expectedContributors[0]->setFullName('Brenna Clarke Gray');
        $expectedContributors[0]->setOrcid('https://orcid.org/0000-0002-6079-0484');
        $expectedContributors[0]->setWebsite('http://brennaclarkegray.ca');
        $expectedContributors[] = new ThothContributor();
        $expectedContributors[1]->setId('5b0d32d4-bfd9-4db1-88fb-4cb91bdaf246');
        $expectedContributors[1]->setFirstName('Dilton Oliveira de');
        $expectedContributors[1]->setLastName('Araújo');
        $expectedContributors[1]->setFullName('Dilton Oliveira de Araújo');
        $expectedContributors[1]->setWebsite('http://buscatextual.cnpq.br/buscatextual/visualizacv.do?id=B00408');

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods([
                'contributors',
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('contributors')
            ->will($this->returnValue([
                [
                    'contributorId' => '59383141-fff9-46e2-bc66-f71e42189380',
                    'firstName' => 'Brenna Clarke',
                    'lastName' => 'Gray',
                    'fullName' => 'Brenna Clarke Gray',
                    'orcid' => 'https://orcid.org/0000-0002-6079-0484',
                    'website' => 'http://brennaclarkegray.ca'
                ],
                [
                    'contributorId' => '5b0d32d4-bfd9-4db1-88fb-4cb91bdaf246',
                    'firstName' => 'Dilton Oliveira de',
                    'lastName' => 'Araújo',
                    'fullName' => 'Dilton Oliveira de Araújo',
                    'orcid' => null,
                    'website' => 'http://buscatextual.cnpq.br/buscatextual/visualizacv.do?id=B00408'
                ]
            ]));

        $contributors = $this->contributorService->getMany($mockThothClient);

        $this->assertEquals($expectedContributors, $contributors);
    }
}
