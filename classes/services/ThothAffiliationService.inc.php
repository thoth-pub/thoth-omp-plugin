<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothAffiliationService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAffiliationService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth affiliations
 */

import('plugins.generic.thoth.classes.facades.ThothRepository');

class ThothAffiliationService
{
    public $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    public function register($affiliation, $thothContributionId)
    {
        $thothInstitution = ThothRepository::institution()->find($affiliation);

        if ($thothInstitution === null) {
            return null;
        }

        $thothAffiliation = $this->repository->new([
            'contributionId' => $thothContributionId,
            'institutionId' => $thothInstitution->getInstitutionId(),
            'affiliationOrdinal' => 1
        ]);

        return $this->repository->add($thothAffiliation);
    }
}
