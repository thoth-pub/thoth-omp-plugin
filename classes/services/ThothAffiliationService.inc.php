<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothAffiliationService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAffiliationService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth affiliations
 */

class ThothAffiliationService
{
    public $repository;
    public $institutionRepository;

    public function __construct($repository, $institutionRepository)
    {
        $this->repository = $repository;
        $this->institutionRepository = $institutionRepository;
    }

    public function register($rorId, $thothContributionId)
    {
        $thothAffiliation = $this->createFromRor($rorId, $thothContributionId);
        if ($thothAffiliation === null) {
            return null;
        }

        return $this->repository->add($thothAffiliation);
    }

    public function update($rorId, string $thothContributionId, array $existingAffiliations = []): void
    {
        $remainingAffiliations = $existingAffiliations;
        $thothAffiliation = $this->createFromRor($rorId, $thothContributionId);

        if ($thothAffiliation !== null) {
            $existingKey = $this->findMatchingAffiliationKey(
                $thothAffiliation->getInstitutionId(),
                $remainingAffiliations
            );

            if ($existingKey === null) {
                $this->repository->add($thothAffiliation);
            } else {
                $thothAffiliation->setAffiliationId($remainingAffiliations[$existingKey]['affiliationId']);
                $this->repository->edit($thothAffiliation);
                unset($remainingAffiliations[$existingKey]);
            }
        }

        foreach ($remainingAffiliations as $existingAffiliation) {
            if (isset($existingAffiliation['affiliationId'])) {
                $this->repository->delete($existingAffiliation['affiliationId']);
            }
        }
    }

    private function createFromRor($rorId, string $thothContributionId)
    {
        if (empty($rorId)) {
            return null;
        }

        $thothInstitution = $this->institutionRepository->find($rorId);
        if ($thothInstitution === null) {
            return null;
        }

        return $this->repository->new([
            'contributionId' => $thothContributionId,
            'institutionId' => $thothInstitution->getInstitutionId(),
            'affiliationOrdinal' => 1,
        ]);
    }

    private function findMatchingAffiliationKey(string $institutionId, array $existingAffiliations): ?int
    {
        foreach ($existingAffiliations as $key => $existingAffiliation) {
            if (($existingAffiliation['institutionId'] ?? null) === $institutionId) {
                return $key;
            }
        }

        return null;
    }
}
