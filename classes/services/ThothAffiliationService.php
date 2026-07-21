<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothAffiliationService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAffiliationService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth affiliations
 */

namespace APP\plugins\generic\thoth\classes\services;

use PKP\affiliation\Affiliation;

class ThothAffiliationService
{
    public $repository;
    public $institutionRepository;

    public function __construct($repository, $institutionRepository)
    {
        $this->repository = $repository;
        $this->institutionRepository = $institutionRepository;
    }

    public function register(Affiliation $affiliation, $thothContributionId, $affiliationOrdinal)
    {
        $thothAffiliation = $this->createFromAffiliation(
            $affiliation,
            $thothContributionId,
            $affiliationOrdinal
        );
        return $thothAffiliation ? $this->repository->add($thothAffiliation) : null;
    }

    public function registerByAuthor($author, string $thothContributionId): void
    {
        foreach (array_values($author->getAffiliations()) as $index => $affiliation) {
            $this->register($affiliation, $thothContributionId, $index + 1);
        }
    }

    public function updateByAuthor(
        $author,
        string $thothContributionId,
        array $existingAffiliations = []
    ): void {
        $remainingAffiliations = $existingAffiliations;

        foreach (array_values($author->getAffiliations()) as $index => $affiliation) {
            $thothAffiliation = $this->createFromAffiliation(
                $affiliation,
                $thothContributionId,
                $index + 1
            );
            if ($thothAffiliation === null) {
                continue;
            }

            $existingKey = $this->findMatchingAffiliationKey($thothAffiliation, $remainingAffiliations);
            if ($existingKey === null) {
                $this->repository->add($thothAffiliation);
                continue;
            }

            $thothAffiliation->setAffiliationId($remainingAffiliations[$existingKey]['affiliationId']);
            $this->repository->edit($thothAffiliation);
            unset($remainingAffiliations[$existingKey]);
        }

        foreach ($remainingAffiliations as $existingAffiliation) {
            if (isset($existingAffiliation['affiliationId'])) {
                $this->repository->delete($existingAffiliation['affiliationId']);
            }
        }
    }

    private function createFromAffiliation(
        Affiliation $affiliation,
        string $thothContributionId,
        int $affiliationOrdinal
    ) {
        $ror = $affiliation->getRor();

        if (empty($ror)) {
            return null;
        }

        $thothInstitution = $this->institutionRepository->find($ror);

        if ($thothInstitution === null) {
            return null;
        }

        return $this->repository->new([
            'contributionId' => $thothContributionId,
            'institutionId' => $thothInstitution->getInstitutionId(),
            'affiliationOrdinal' => $affiliationOrdinal
        ]);
    }

    private function findMatchingAffiliationKey($thothAffiliation, array $existingAffiliations): ?int
    {
        foreach ($existingAffiliations as $key => $existingAffiliation) {
            if (($existingAffiliation['institutionId'] ?? null) === $thothAffiliation->getInstitutionId()) {
                return $key;
            }
        }

        return null;
    }
}
