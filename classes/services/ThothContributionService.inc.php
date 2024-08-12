<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothContributionService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth contributions
 */

import('plugins.generic.thoth.classes.facades.ThothService');
import('plugins.generic.thoth.thoth.models.ThothContribution');
import('classes.core.Services');

class ThothContributionService
{
    public function new($params)
    {
        $contribution = new ThothContribution();
        $contribution->setContributionType($params['contributionType']);
        $contribution->setMainContribution($params['mainContribution']);
        $contribution->setContributionOrdinal($params['contributionOrdinal']);
        $contribution->setLastName($params['lastName']);
        $contribution->setFullName($params['fullName']);
        $contribution->setFirstName($params['firstName'] ?? null);
        $contribution->setBiography($params['biography'] ?? null);
        return $contribution;
    }

    public function newByAuthor($author)
    {
        $userGroupLocaleKey = $author->getUserGroup()->getData('nameLocaleKey');

        $params = [];
        $params['contributionType'] = $this->getContributionTypeByUserGroupLocaleKey($userGroupLocaleKey);
        $params['mainContribution'] = $this->isMainContribution($author);
        $params['contributionOrdinal'] = $author->getSequence() + 1;
        $params['firstName'] = $author->getLocalizedGivenName();
        $params['lastName'] = $author->getLocalizedData('familyName');
        $params['fullName'] = $author->getFullName(false);
        $params['biography'] = $author->getLocalizedBiography();
        return $this->new($params);
    }

    public function register($thothClient, $author, $workId)
    {
        $contribution = $this->newByAuthor($author);
        $contribution->setWorkId($workId);

        $contributors = ThothService::contributor()->getMany($thothClient, [
            'limit' => 1,
            'filter' => (!empty($author->getOrcid())) ?
                $author->getOrcid() :
                $author->getFullName(false)
        ]);

        $contributor = empty($contributors) ?
            $contributorService->register($thothClient, $author) :
            $contributor = array_shift($contributors);

        $contribution->setContributorId($contributor->getId());

        $contributionId = $thothClient->createContribution($contribution);
        $contribution->setId($contributionId);

        if ($affiliation = $author->getLocalizedAffiliation()) {
            ThothService::affiliation()->register($thothClient, $affiliation, $contributionId);
        }

        return $contribution;
    }

    private function isMainContribution($author)
    {
        if ($author instanceof ChapterAuthor) {
            return (bool) $author->getPrimaryContact();
        }

        $publication = Services::get('publication')->get($author->getData('publicationId'));
        return $publication->getData('primaryContactId') == $author->getId();
    }

    public function getContributionTypeByUserGroupLocaleKey($userGroupLocaleKey)
    {
        $contributionTypeMapping = [
            'default.groups.name.author' => ThothContribution::CONTRIBUTION_TYPE_AUTHOR,
            'default.groups.name.chapterAuthor' => ThothContribution::CONTRIBUTION_TYPE_AUTHOR,
            'default.groups.name.volumeEditor' => ThothContribution::CONTRIBUTION_TYPE_EDITOR,
            'default.groups.name.translator' => ThothContribution::CONTRIBUTION_TYPE_TRANSLATOR,
        ];
        return $contributionTypeMapping[$userGroupLocaleKey];
    }
}
