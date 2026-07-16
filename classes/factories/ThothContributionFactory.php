<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothContributionFactory.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionFactory
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth contributions
 */

namespace APP\plugins\generic\thoth\classes\factories;

use ThothApi\GraphQL\Enums\ContributionType;
use ThothApi\GraphQL\Inputs\PatchContribution as ThothContribution;

class ThothContributionFactory
{
    public function createFromAuthor($author, $seq, $primaryContactId = null)
    {
        $userGroupLocaleKey = $author->getUserGroup()->nameLocaleKey;

        $contributionData = [
            'contributionType' => $this->getContributionTypeByUserGroupLocaleKey($userGroupLocaleKey),
            'mainContribution' => $this->isMainContribution($author, $primaryContactId),
            'contributionOrdinal' => $seq + 1,
            'lastName' => $author->getLocalizedFamilyName(),
            'fullName' => $author->getFullName(false),
        ];

        $firstName = $author->getLocalizedGivenName();
        if ($firstName !== null && $firstName !== '') {
            $contributionData['firstName'] = $firstName;
        }

        return new ThothContribution($contributionData);
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
            'default.groups.name.author' => ContributionType::AUTHOR,
            'default.groups.name.chapterAuthor' => ContributionType::AUTHOR,
            'default.groups.name.volumeEditor' => ContributionType::EDITOR,
            'default.groups.name.translator' => ContributionType::TRANSLATOR,
        ];
        return $contributionTypeMapping[$userGroupLocaleKey];
    }
}
