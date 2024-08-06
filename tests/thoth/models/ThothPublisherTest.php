<?php

/**
 * @file plugins/generic/thoth/tests/thoth/models/ThothPublisherTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublisherTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothPublisher
 *
 * @brief Test class for the ThothPublisher class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.models.ThothPublisher');

class ThothPublisherTest extends PKPTestCase
{
    public function testGettersAndSetters()
    {
        $publisher = new ThothPublisher();
        $publisher->setId('ea0ad5ff-dd59-48f8-8da7-95dfd40c90d8');
        $publisher->setPublisherName('Ediciones Universidad de Camagüey');
        $publisher->setPublisherShortName('UCEdiciones');
        $publisher->setPublisherUrl('https://edicionesuc.reduc.edu.cu/');

        $this->assertEquals('ea0ad5ff-dd59-48f8-8da7-95dfd40c90d8', $publisher->getId());
        $this->assertEquals('Ediciones Universidad de Camagüey', $publisher->getPublishername());
        $this->assertEquals('UCEdiciones', $publisher->getPublisherShortName());
        $this->assertEquals('https://edicionesuc.reduc.edu.cu/', $publisher->getPublisherUrl());
    }

    public function testGetPublisherData()
    {
        $publisher = new ThothPublisher();
        $publisher->setId('67d14e6c-8922-4cf9-9bc7-1c03a9357144');
        $publisher->setPublisherName('Editora da Universidade Federal da Bahia');
        $publisher->setPublisherShortname('EDUFBA');
        $publisher->setPublisherUrl('https://books.scielo.org/edufba/');

        $this->assertEquals([
            'publisherId' => '67d14e6c-8922-4cf9-9bc7-1c03a9357144',
            'publisherName' => 'Editora da Universidade Federal da Bahia',
            'publisherShortname' => 'EDUFBA',
            'publisherUrl' => 'https://books.scielo.org/edufba/'
        ], $publisher->getData());
    }
}
