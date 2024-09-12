<?php

/**
 * @file plugins/generic/thoth/tests/thoth/models/ThothLocationTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothLocation
 *
 * @brief Test class for the ThothLocation class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.lib.thothAPI.models.ThothLocation');

class ThothLocationTest extends PKPTestCase
{
    public function testGettersAndSetters()
    {
        $location = new ThothLocation();
        $location->setId('03b0367d-bba3-4e26-846a-4c36d3920db2');
        $location->setPublicationId('8ac3e585-c32a-42d7-bd36-ef42ee397e6e');
        $location->setLandingPage('https://omp.publicknowledgeproject.org/press/catalog/book/23');
        $location->setFullTextUrl('https://omp.publicknowledgeproject.org/press/catalog/view/17');
        $location->setLocationPlatform(ThothLocation::LOCATION_PLATFORM_PUBLISHER_WEBSITE);
        $location->setCanonical(true);

        $this->assertEquals(
            '03b0367d-bba3-4e26-846a-4c36d3920db2',
            $location->getId()
        );
        $this->assertEquals(
            '8ac3e585-c32a-42d7-bd36-ef42ee397e6e',
            $location->getPublicationId()
        );
        $this->assertEquals(
            'https://omp.publicknowledgeproject.org/press/catalog/book/23',
            $location->getLandingPage()
        );
        $this->assertEquals(
            'https://omp.publicknowledgeproject.org/press/catalog/view/17',
            $location->getFullTextUrl()
        );
        $this->assertEquals(
            ThothLocation::LOCATION_PLATFORM_PUBLISHER_WEBSITE,
            $location->getLocationPlatform()
        );
        $this->assertEquals(
            true,
            $location->getCanonical()
        );
    }

    public function testGetRelationData()
    {
        $location = new ThothLocation();
        $location->setId('0d9f4a87-8eab-40f1-a25c-6487a0cb9251');
        $location->setPublicationId('7d473e27-8b7a-4623-a853-55e1dffea2dc');
        $location->setLandingPage('https://omp.publicknowledgeproject.org/press/catalog/book/12');
        $location->setFullTextUrl('https://www.bookstore.com/site/books/book34/');
        $location->setLocationPlatform(ThothLocation::LOCATION_PLATFORM_OTHER);
        $location->setCanonical(false);

        $this->assertEquals([
            'locationId' => '0d9f4a87-8eab-40f1-a25c-6487a0cb9251',
            'publicationId' => '7d473e27-8b7a-4623-a853-55e1dffea2dc',
            'landingPage' => 'https://omp.publicknowledgeproject.org/press/catalog/book/12',
            'fullTextUrl' => 'https://www.bookstore.com/site/books/book34/',
            'locationPlatform' => ThothLocation::LOCATION_PLATFORM_OTHER,
            'canonical' => false
        ], $location->getData());
    }
}
