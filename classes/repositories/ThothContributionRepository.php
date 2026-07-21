<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothContributionRepository.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth contributions
 */

namespace APP\plugins\generic\thoth\classes\repositories;

use ThothApi\GraphQL\Inputs\PatchContribution as ThothContribution;

class ThothContributionRepository
{
    private const WORK_CONTRIBUTIONS_SELECTION = [
        'contributions' => [
            'contributionId',
            'contributorId',
            'contributionType',
            'mainContribution',
            'contributionOrdinal',
            'firstName',
            'lastName',
            'fullName',
            'contributor' => ['contributorId', 'orcid', 'fullName'],
        ],
    ];
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothContribution($data);
    }

    public function get($thothContributionId)
    {
        return $this->thothClient->contribution($thothContributionId);
    }

    public function getByWorkId(string $thothWorkId): array
    {
        $thothWork = $this->thothClient->work($thothWorkId, self::WORK_CONTRIBUTIONS_SELECTION);

        return array_map(
            fn ($contribution): array => $contribution->toArray(),
            $thothWork->getContributions() ?? []
        );
    }

    public function add($thothContribution)
    {
        return $this->thothClient->createContribution($thothContribution);
    }

    public function edit($thothPatchContribution)
    {
        return $this->thothClient->updateContribution($thothPatchContribution);
    }

    public function delete($thothContributionId)
    {
        return $this->thothClient->deleteContribution($thothContributionId);
    }
}
