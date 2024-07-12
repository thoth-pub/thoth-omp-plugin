<?php

/**
 * @file plugins/generic/thoth/thoth/models/Contributor.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Contributor
 * @ingroup plugins_generic_thoth
 *
 * @brief Class for a Thoth contributor.
 */

import('plugins.generic.thoth.thoth.models.ThothModel');

class Contributor extends ThothModel
{
    private $contributorId;

    private $firstName;

    private $lastName;

    private $fullName;

    private $orcid;

    private $website;

    public function getReturnValue()
    {
        return 'contributorId';
    }

    public function getEnclosedValues()
    {
        return parent::getEnclosedValues() + [
            'firstName',
            'lastName',
            'fullName',
            'orcid',
            'website',
        ];
    }

    public function getId()
    {
        return $this->contributorId;
    }

    public function setId($id)
    {
        $this->contributorId = $id;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    public function getFullName()
    {
        return $this->fullName;
    }

    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }

    public function getOrcid()
    {
        return $this->orcid;
    }

    public function setOrcid($orcid)
    {
        $this->orcid = $orcid;
    }

    public function getWebsite()
    {
        return $this->website;
    }

    public function setWebsite($website)
    {
        $this->website = $website;
    }
}
