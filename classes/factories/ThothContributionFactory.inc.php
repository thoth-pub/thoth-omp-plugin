<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothContributionFactory.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionFactory
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth contributions
 */

use ThothApi\GraphQL\Enums\ContributionType;
use ThothApi\GraphQL\Inputs\PatchContribution as ThothContribution;

import('classes.core.Services');

class ThothContributionFactory
{
    public function createFromAuthor($author, $seq, $primaryContactId = null)
    {
        $userGroupLocaleKey = $author->getUserGroup()->getData('nameLocaleKey');

        return new ThothContribution([
            'contributionType' => $this->getContributionTypeByUserGroupLocaleKey($userGroupLocaleKey),
            'mainContribution' => $this->isMainContribution($author, $primaryContactId),
            'contributionOrdinal' => $seq + 1,
            'firstName' => $author->getLocalizedGivenName(),
            'lastName' => $author->getLocalizedData('familyName'),
            'fullName' => $author->getFullName(false),
        ]);
    }

    private function isMainContribution($author, $primaryContactId = null)
    {
        if ($mainContribution = $author->getData('mainContribution')) {
            return $mainContribution;
        }

        if ($author instanceof ChapterAuthor) {
            return (bool) $author->getPrimaryContact();
        }

        return $primaryContactId == $author->getId();
    }

    private function getContributionTypeByUserGroupLocaleKey($userGroupLocaleKey)
    {
        $contributionTypeMapping = [
            'default.groups.name.author' => ContributionType::AUTHOR,
            'default.groups.name.chapterAuthor' => ContributionType::AUTHOR,
            'default.groups.name.volumeEditor' => ContributionType::EDITOR,
            'default.groups.name.translator' => ContributionType::TRANSLATOR,
        ];
        return $contributionTypeMapping[$userGroupLocaleKey];
    }
}
