<?php

/**
 * @file plugins/generic/thoth/tests/thoth/models/ThothContributorTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributorTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothContributor
 *
 * @brief Test class for the ThothContributor class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.models.ThothContributor');

class ThothContributorTest extends PKPTestCase
{
    public function testGettersAndSetters()
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $contributor = new ThothContributor();
        $contributor->setId($uuid);
        $contributor->setFirstName('John');
        $contributor->setLastName('Doe');
        $contributor->setFullName('John Doe');
        $contributor->setOrcid('https://orcid.org/0000-0001-2345-6789');
        $contributor->setWebsite('https://sites.google.com/site/johndoe');

        $this->assertEquals($uuid, $contributor->getId());
        $this->assertEquals('John', $contributor->getFirstName());
        $this->assertEquals('Doe', $contributor->getLastName());
        $this->assertEquals('John Doe', $contributor->getFullName());
        $this->assertEquals('https://orcid.org/0000-0001-2345-6789', $contributor->getOrcid());
        $this->assertEquals('https://sites.google.com/site/johndoe', $contributor->getWebsite());
        $this->assertEquals('contributorId', $contributor->getReturnValue());
    }

    public function testGetContributorData()
    {
        $uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $contributor = new ThothContributor();
        $contributor->setId($uuid);
        $contributor->setFirstName('Adriana Laura');
        $contributor->setLastName('Massidda');
        $contributor->setFullName('Adriana Laura Massidda');
        $contributor->setOrcid('https://orcid.org/0000-0001-8735-7990');
        $contributor->setWebsite('https://sites.google.com/site/adrianamassidda');

        $this->assertEquals(
            [
                'contributorId' => $uuid,
                'firstName' => 'Adriana Laura',
                'lastName' => 'Massidda',
                'fullName' => 'Adriana Laura Massidda',
                'orcid' => 'https://orcid.org/0000-0001-8735-7990',
                'website' => 'https://sites.google.com/site/adrianamassidda',
            ],
            $contributor->getData()
        );
    }
}
