<?php

/**
 * @file plugins/generic/thoth/tests/thoth/models/ThothAffiliationTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAffiliationTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothAffiliation
 *
 * @brief Test class for the ThothAffiliation class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.models.ThothAffiliation');

class ThothAffiliationTest extends PKPTestCase
{
    public function testGettersAndSetters()
    {
        $thothAffiliation = new ThothAffiliation();
        $thothAffiliation->setId('bb4d5b3c-592e-47ed-badc-dcc2d66d2cc1');
        $thothAffiliation->setContributionId('898bb18b-2725-4254-96b4-d3c5f5094ae7');
        $thothAffiliation->setInstitutionId('485e6488-b96e-4b76-aa5f-f6f717eed0b8');
        $thothAffiliation->setAffiliationOrdinal(1);

        $this->assertEquals('bb4d5b3c-592e-47ed-badc-dcc2d66d2cc1', $thothAffiliation->getId());
        $this->assertEquals('898bb18b-2725-4254-96b4-d3c5f5094ae7', $thothAffiliation->getContributionId());
        $this->assertEquals('485e6488-b96e-4b76-aa5f-f6f717eed0b8', $thothAffiliation->getInstitutionId());
        $this->assertEquals(1, $thothAffiliation->getAffiliationOrdinal());
    }

    public function testGetContributionData()
    {
        $thothAffiliation = new ThothAffiliation();
        $thothAffiliation->setId('3266c9b6-3f9a-4675-97dd-78c5f3b10ce9');
        $thothAffiliation->setContributionId('9ce4155d-bf71-4d71-8ac3-36e58161d901');
        $thothAffiliation->setInstitutionId('9674c5e2-6497-48ed-a93e-4c0a5b4f7874');
        $thothAffiliation->setAffiliationOrdinal(1);

        $this->assertEquals([
            'affiliationId' => '3266c9b6-3f9a-4675-97dd-78c5f3b10ce9',
            'contributionId' => '9ce4155d-bf71-4d71-8ac3-36e58161d901',
            'institutionId' => '9674c5e2-6497-48ed-a93e-4c0a5b4f7874',
            'affiliationOrdinal' => 1
        ], $thothAffiliation->getData());
    }
}
