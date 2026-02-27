<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothContributionFactory.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionFactory
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth contributions
 */

namespace APP\plugins\generic\thoth\classes\factories;

use APP\plugins\generic\thoth\classes\formatters\HtmlStripper;
use ThothApi\GraphQL\Models\Contribution as ThothContribution;

class ThothContributionFactory
{
    public function createFromAuthor($author, $seq, $primaryContactId = null)
    {
        $userGroupLocaleKey = $author->getUserGroup()->nameLocaleKey;

        return new ThothContribution([
            'contributionType' => $this->getContributionTypeByUserGroupLocaleKey($userGroupLocaleKey),
            'mainContribution' => $this->isMainContribution($author, $primaryContactId),
            'contributionOrdinal' => $seq + 1,
            'firstName' => $author->getLocalizedGivenName(),
            'lastName' => $author->getLocalizedFamilyName(),
            'fullName' => $author->getFullName(false),
            'biography' => HtmlStripper::stripTags($author->getLocalizedBiography())
        ]);
    }

    private function isMainContribution($author, $primaryContactId = null)
    {
        if ($mainContribution = $author->getData('mainContribution')) {
            return $mainContribution;
        }

        return $primaryContactId == $author->getId();
    }

    private function getContributionTypeByUserGroupLocaleKey($userGroupLocaleKey)
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
