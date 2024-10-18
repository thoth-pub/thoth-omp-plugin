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

import('plugins.generic.thoth.lib.thothAPI.models.ThothInstitution');

class ThothInstitutionService
{
    public function new($params)
    {
        $thothInstitution = new ThothInstitution();
        $thothInstitution->setId($params['institutionId']);
        $thothInstitution->setInstitutionName($params['institutionName']);
        $thothInstitution->setInstitutionDoi($params['institutionDoi']);
        $thothInstitution->setCountryCode($params['countryCode']);
        $thothInstitution->setRor($params['ror']);
        return $thothInstitution;
    }

    public function getMany($thothClient, $params = [])
    {
        $institutionsData = $thothClient->institutions($params);
        return array_map([$this, 'new'], $institutionsData);
    }
}
