<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothInstitutionService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothInstitutionService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth institutions
 */

use ThothApi\GraphQL\Models\Institution as ThothInstitution;

class ThothInstitutionService
{
    public function new($params)
    {
        $thothInstitution = new ThothInstitution();
        $thothInstitution->setInstitutionId($params['institutionId']);
        $thothInstitution->setInstitutionName($params['institutionName']);
        $thothInstitution->setInstitutionDoi($params['institutionDoi']);
        $thothInstitution->setCountryCode($params['countryCode']);
        $thothInstitution->setRor($params['ror']);
        return $thothInstitution;
    }

    public function getMany($params = [])
    {
        $thothClient = ThothContainer::getInstance()->get('client');
        return $thothClient->institutions($params);
    }
}
