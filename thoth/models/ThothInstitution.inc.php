<?php

/**
 * @file plugins/generic/thoth/thoth/models/ThothInstitution.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothInstitution
 * @ingroup plugins_generic_thoth
 *
 * @brief Class for a Thoth institution.
 */

import('plugins.generic.thoth.thoth.models.ThothModel');

class ThothInstitution extends ThothModel
{
    private $institutionId;

    private $institutionName;

    private $institutionDoi;

    private $countryCode;

    private $ror;

    public function getReturnValue()
    {
        return 'institutionId';
    }

    public function getId()
    {
        return $this->institutionId;
    }

    public function setId($institutionId)
    {
        $this->institutionId = $institutionId;
    }

    public function getInstitutionName()
    {
        return $this->institutionName;
    }

    public function setInstitutionName($institutionName)
    {
        $this->institutionName = $institutionName;
    }

    public function getInstitutionDoi()
    {
        return $this->institutionDoi;
    }

    public function setInstitutionDoi($institutionDoi)
    {
        $this->institutionDoi = $institutionDoi;
    }

    public function getCountryCode()
    {
        return $this->countryCode;
    }

    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
    }

    public function getRor()
    {
        return $this->ror;
    }

    public function setRor($ror)
    {
        $this->ror = $ror;
    }
}
