<?php

/**
 * @file plugins/generic/thoth/tests/thoth/models/ThothInstitutionTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothInstitutionTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothInstitution
 *
 * @brief Test class for the ThothInstitution class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.lib.thothAPI.models.ThothInstitution');

class ThothInstitutionTest extends PKPTestCase
{
    public function testGettersAndSetters()
    {
        $institution = new ThothInstitution();
        $institution->setId('92937abd-87c4-43eb-bb2d-cd17d3760b1d');
        $institution->setInstitutionName('1675 Foundation');
        $institution->setInstitutionDoi('https://doi.org/10.13039/100001436');
        $institution->setCountryCode('USA');
        $institution->setRor('https://ror.org/04n3dya31');

        $this->assertEquals('92937abd-87c4-43eb-bb2d-cd17d3760b1d', $institution->getId());
        $this->assertEquals('1675 Foundation', $institution->getInstitutionName());
        $this->assertEquals('https://doi.org/10.13039/100001436', $institution->getInstitutionDoi());
        $this->assertEquals('USA', $institution->getCountryCode());
        $this->assertEquals('https://ror.org/04n3dya31', $institution->getRor());
    }

    public function testGetInstitutionData()
    {
        $institution = new ThothInstitution();
        $institution->setId('2f292272-21c4-4f97-bd28-e7ed642b1158');
        $institution->setInstitutionName('Zorggroep Meander');
        $institution->setCountryCode('NLD');
        $institution->setRor('https://ror.org/00vtp3850');

        $this->assertEquals([
            'institutionId' => '2f292272-21c4-4f97-bd28-e7ed642b1158',
            'institutionName' => 'Zorggroep Meander',
            'countryCode' => 'NLD',
            'ror' => 'https://ror.org/00vtp3850'
        ], $institution->getData());
    }
}
