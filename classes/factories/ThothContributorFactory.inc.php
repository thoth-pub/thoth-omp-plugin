<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothContributorFactory.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributorFactory
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth contributors
 */

use ThothApi\GraphQL\Inputs\PatchContributor as ThothContributor;

class ThothContributorFactory
{
    public function createFromAuthor($author)
    {
        $contributorData = [
            'lastName' => $author->getLocalizedData('familyName'),
            'fullName' => $author->getFullName(false),
        ];

        $optionalData = [
            'firstName' => $author->getLocalizedGivenName(),
            'orcid' => $author->getOrcid(),
            'website' => $author->getUrl(),
        ];
        foreach ($optionalData as $fieldName => $fieldValue) {
            if ($fieldValue !== null && $fieldValue !== '') {
                $contributorData[$fieldName] = $fieldValue;
            }
        }

        return new ThothContributor($contributorData);
    }
}
