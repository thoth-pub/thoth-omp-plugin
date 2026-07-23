<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothWorkRelationRepository.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkRelationRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth work relations
 */

namespace APP\plugins\generic\thoth\classes\repositories;

use ThothApi\GraphQL\Inputs\PatchWorkRelation as ThothWorkRelation;

class ThothWorkRelationRepository
{
    private const WORK_CHAPTERS_SELECTION = [
        'imprintId',
        'relations' => [
            'workRelationId',
            'relatorWorkId',
            'relatedWorkId',
            'relationType',
            'relationOrdinal',
            'relatedWork' => [
                'workId',
                'workType',
                'workStatus',
                'fullTitle',
                'imprintId',
                'doi',
                'publicationDate',
                'landingPage',
                'pageInterval',
                'firstPage',
                'lastPage',
                'titles' => [
                    'titleId',
                    'localeCode',
                    'canonical',
                ],
                'abstracts' => [
                    'abstractId',
                    'localeCode',
                    'abstractType',
                    'canonical',
                ],
                'contributions' => [
                    'contributionId',
                    'contributorId',
                    'contributionType',
                    'mainContribution',
                    'contributionOrdinal',
                    'firstName',
                    'lastName',
                    'fullName',
                    'contributor' => [
                        'contributorId',
                        'firstName',
                        'lastName',
                        'fullName',
                        'orcid',
                        'website',
                    ],
                    'biographies' => [
                        'biographyId',
                        'contributionId',
                        'localeCode',
                        'content',
                        'canonical',
                    ],
                    'affiliations' => [
                        'affiliationId',
                        'contributionId',
                        'institutionId',
                        'affiliationOrdinal',
                    ],
                ],
                'publications' => [
                    'publicationId',
                    'publicationType',
                    'workId',
                    'isbn',
                    'accessibilityStandard',
                    'accessibilityAdditionalStandard',
                    'accessibilityException',
                    'accessibilityReportUrl',
                    'locations' => [
                        'locationId',
                        'landingPage',
                        'fullTextUrl',
                        'locationPlatform',
                        'canonical',
                    ],
                ],
            ],
        ],
    ];

    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothWorkRelation($data);
    }

    public function getByWorkId(string $thothWorkId): array
    {
        return $this->thothClient
            ->work($thothWorkId, self::WORK_CHAPTERS_SELECTION)
            ->toArray();
    }

    public function add($thothWorkRelation)
    {
        return $this->thothClient->createWorkRelation($thothWorkRelation);
    }

    public function edit($thothPatchWorkRelation)
    {
        return $this->thothClient->updateWorkRelation($thothPatchWorkRelation);
    }

    public function delete($thothWorkRelationId)
    {
        return $this->thothClient->deleteWorkRelation($thothWorkRelationId);
    }
}
