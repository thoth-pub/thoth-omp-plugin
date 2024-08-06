<?php

/**
 * @file plugins/generic/thoth/tests/thoth/models/ThothImprintTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothImprintTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothImprint
 *
 * @brief Test class for the ThothImprint class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.models.ThothImprint');

class ThothImprintTest extends PKPTestCase
{
    public function testGettersAndSetters()
    {
        $imprint = new ThothImprint();
        $imprint->setId('be4c8448-93c8-4146-8d9c-84d121bc4bec');
        $imprint->setPublisherId('9c41b13c-cecc-4f6a-a151-be4682915ef5');
        $imprint->setImprintName('Tiny Collections');
        $imprint->setImprintUrl('https://punctumbooks.com/imprints/tiny-collections/');
        $imprint->setCrossmarkDoi('https://doi.org/10.55555/12345678');

        $this->assertEquals('be4c8448-93c8-4146-8d9c-84d121bc4bec', $imprint->getId());
        $this->assertEquals('9c41b13c-cecc-4f6a-a151-be4682915ef5', $imprint->getPublisherId());
        $this->assertEquals('Tiny Collections', $imprint->getImprintName());
        $this->assertEquals('https://punctumbooks.com/imprints/tiny-collections/', $imprint->getImprintUrl());
        $this->assertEquals('https://doi.org/10.55555/12345678', $imprint->getCrossmarkDoi());
    }

    public function testGetImprintData()
    {
        $imprint = new ThothImprint();
        $imprint->setId('5078b33c-5b3f-48bf-bf37-ced6b02beb7c');
        $imprint->setPublisherId('4ab3bec2-c491-46d4-8731-47a5d9b33cc5');
        $imprint->setImprintName('mediastudies.press');
        $imprint->setImprintUrl('https://www.mediastudies.press/');
        $imprint->setCrossmarkDoi('https://doi.org/10.33333/87654321');

        $this->assertEquals([
            'imprintId' => '5078b33c-5b3f-48bf-bf37-ced6b02beb7c',
            'publisherId' => '4ab3bec2-c491-46d4-8731-47a5d9b33cc5',
            'imprintName' => 'mediastudies.press',
            'imprintUrl' => 'https://www.mediastudies.press/',
            'crossmarkDoi' => 'https://doi.org/10.33333/87654321'
        ], $imprint->getData());
    }
}
