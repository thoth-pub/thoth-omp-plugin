<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothContributorRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributorRepository
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth contributors
 */

use ThothApi\GraphQL\Models\Contributor as ThothContributor;

class ThothContributorRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothContributor($data);
    }

    public function get($thothContributorId)
    {
        return $this->thothClient->contributor($thothContributorId);
    }

    public function find($filter)
    {
        $thothContributors =  $this->thothClient->contributors([
            'filter' => $filter,
            'limit' => 1
        ]);

        if (empty($thothContributors)) {
            return null;
        }

        return array_shift($thothContributors);
    }

    public function add($thothContributor)
    {
        return $this->thothClient->createContributor($thothContributor);
    }

    public function edit($thothPatchContributor)
    {
        return $this->thothClient->updateContributor($thothPatchContributor);
    }

    public function delete($thothContributorId)
    {
        return $this->thothClient->deleteContributor($thothContributorId);
    }
}
