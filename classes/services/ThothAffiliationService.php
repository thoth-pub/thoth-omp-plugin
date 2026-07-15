<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothAffiliationService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAffiliationService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth affiliations
 */

namespace APP\plugins\generic\thoth\classes\services;

use PKP\affiliation\Affiliation;

class ThothAffiliationService
{
    public $repository;
    public $institutionRepository;

    public function __construct($repository, $institutionRepository)
    {
        $this->repository = $repository;
        $this->institutionRepository = $institutionRepository;
    }

    public function register(Affiliation $affiliation, $thothContributionId, $affiliationOrdinal)
    {
        $ror = $affiliation->getRor();

        if (empty($ror)) {
            return null;
        }

        $thothInstitution = $this->institutionRepository->find($ror);

        if ($thothInstitution === null) {
            return null;
        }

        $thothAffiliation = $this->repository->new([
            'contributionId' => $thothContributionId,
            'institutionId' => $thothInstitution->getInstitutionId(),
            'affiliationOrdinal' => $affiliationOrdinal
        ]);

        return $this->repository->add($thothAffiliation);
    }
}
