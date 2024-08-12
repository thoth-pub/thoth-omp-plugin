<?php

/**
 * @file plugins/generic/thoth/thoth/models/ThothAffiliation.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAffiliation
 * @ingroup plugins_generic_thoth
 *
 * @brief Class for a Thoth Contribution.
 */

import('plugins.generic.thoth.thoth.models.ThothModel');

class ThothAffiliation extends ThothModel
{
    private $affiliationId;

    private $contributionId;

    private $institutionId;

    private $affiliationOrdinal;

    public function getReturnValue()
    {
        return 'affiliationId';
    }

    public function getId()
    {
        return $this->affiliationId;
    }

    public function setId($affiliationId)
    {
        $this->affiliationId = $affiliationId;
    }

    public function getContributionId()
    {
        return $this->contributionId;
    }

    public function setContributionId($contributionId)
    {
        $this->contributionId = $contributionId;
    }

    public function getInstitutionId()
    {
        return $this->institutionId;
    }

    public function setInstitutionId($institutionId)
    {
        $this->institutionId = $institutionId;
    }

    public function getAffiliationOrdinal()
    {
        return $this->affiliationOrdinal;
    }

    public function setAffiliationOrdinal($affiliationOrdinal)
    {
        $this->affiliationOrdinal = $affiliationOrdinal;
    }
}
