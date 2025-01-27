<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothContributorService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributorService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth contributors
 */

use ThothApi\GraphQL\Models\Contributor as ThothContributor;

class ThothContributorService
{
    public function new($params)
    {
        $contributor = new ThothContributor();
        $contributor->setContributorId($params['contributorId'] ?? null);
        $contributor->setFirstName($params['firstName'] ?? null);
        $contributor->setLastName($params['lastName']);
        $contributor->setFullName($params['fullName']);
        $contributor->setOrcid($params['orcid'] ?? null);
        $contributor->setWebsite($params['website'] ?? null);
        return $contributor;
    }

    public function newByAuthor($author)
    {
        $params = [];
        $params['firstName'] = $author->getLocalizedGivenName();
        $params['lastName'] = $author->getLocalizedData('familyName');
        $params['fullName'] = $author->getFullName(false);
        $params['orcid'] = $author->getOrcid();
        $params['website'] = $author->getUrl();
        return $this->new($params);
    }

    public function register($author)
    {
        $contributor = $this->newByAuthor($author);

        $thothClient = ThothContainer::getInstance()->get('client');
        $contributorId = $thothClient->createContributor($contributor);
        $contributor->setContributorId($contributorId);

        return $contributor;
    }

    public function getMany($params = [])
    {
        $thothClient = ThothContainer::getInstance()->get('client');
        return $thothClient->contributors($params);
    }
}
