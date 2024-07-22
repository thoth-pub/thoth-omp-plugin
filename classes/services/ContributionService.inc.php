<?php

/**
 * @file plugins/generic/thoth/classes/services/ContributionService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContributionService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth contributions
 */

import('plugins.generic.thoth.thoth.models.Contribution');
import('classes.core.Services');

class ContributionService
{
    public function getPropertiesByAuthor($author)
    {
        $userGroupLocaleKey = $author->getUserGroup()->getData('nameLocaleKey');

        $props = [];
        $props['contributionType'] = $this->getContributionTypeByUserGroupLocaleKey($userGroupLocaleKey);
        $props['mainContribution'] = $this->isMainContribution($author);
        $props['contributionOrdinal'] = $author->getSequence() + 1;
        $props['firstName'] = $author->getLocalizedGivenName();
        $props['lastName'] = $author->getLocalizedFamilyName();
        $props['fullName'] = $author->getFullName(false);
        $props['biography'] = $author->getLocalizedBiography();
        return $props;
    }

    public function new($params)
    {
        $contribution = new Contribution();
        $contribution->setContributionType($params['contributionType']);
        $contribution->setMainContribution($params['mainContribution']);
        $contribution->setContributionOrdinal($params['contributionOrdinal']);
        $contribution->setLastName($params['lastName']);
        $contribution->setFullName($params['fullName']);
        $contribution->setFirstName($params['firstName'] ?? null);
        $contribution->setBiography($params['biography'] ?? null);
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
            'default.groups.name.author' => Contribution::CONTRIBUTION_TYPE_AUTHOR,
            'default.groups.name.chapterAuthor' => Contribution::CONTRIBUTION_TYPE_AUTHOR,
            'default.groups.name.volumeEditor' => Contribution::CONTRIBUTION_TYPE_EDITOR,
            'default.groups.name.translator' => Contribution::CONTRIBUTION_TYPE_TRANSLATOR,
        ];
        return $contributionTypeMapping[$userGroupLocaleKey];
    }
}
