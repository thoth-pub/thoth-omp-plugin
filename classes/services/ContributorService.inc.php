<?php

/**
 * @file plugins/generic/thoth/classes/services/ContributorService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContributorService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth contributors
 */

import('plugins.generic.thoth.thoth.models.Contributor');

class ContributorService
{
    public function getPropertiesByAuthor($author)
    {
        $props = [];
        $props['firstName'] = $author->getLocalizedGivenName();
        $props['lastName'] = $author->getLocalizedFamilyName();
        $props['fullName'] = $author->getFullName(false);
        $props['orcid'] = $author->getOrcid();
        $props['website'] = $author->getUrl();
        return $props;
    }

    public function new($params)
    {
        $contributor = new Contributor();
        $contributor->setFirstName($params['firstName'] ?? null);
        $contributor->setLastName($params['lastName']);
        $contributor->setFullName($params['fullName']);
        $contributor->setOrcid($params['orcid'] ?? null);
        $contributor->setWebsite($params['website'] ?? null);
        return $contributor;
    }
}
