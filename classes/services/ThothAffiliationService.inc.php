<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothAffiliationService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAffiliationService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth affiliations
 */

import('plugins.generic.thoth.classes.facades.ThothService');
import('plugins.generic.thoth.lib.thothAPI.models.ThothAffiliation');

class ThothAffiliationService
{
    public function new($params)
    {
        $thothAffiliation = new ThothAffiliation();
        $thothAffiliation->setId($params['affiliationId'] ?? null);
        $thothAffiliation->setContributionId($params['contributionId']);
        $thothAffiliation->setInstitutionId($params['institutionId']);
        $thothAffiliation->setAffiliationOrdinal($params['affiliationOrdinal']);
        return $thothAffiliation;
    }

    public function register($thothClient, $affiliation, $thothContributionId)
    {
        $thothInstitutions = ThothService::institution()->getMany($thothClient, [
            'limit' => 1,
            'filter' => $affiliation
        ]);

        if (empty($thothInstitutions)) {
            return null;
        }

        $thothInstitution = array_shift($thothInstitutions);
        $thothAffiliation = $this->new([
            'contributionId' => $thothContributionId,
            'institutionId' => $thothInstitution->getId(),
            'affiliationOrdinal' => 1
        ]);

        $thothAffiliationId = $thothClient->createAffiliation($thothAffiliation);
        $thothAffiliation->setId($thothAffiliationId);

        return $thothAffiliation;
    }
}
