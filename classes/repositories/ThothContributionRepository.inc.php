<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothContributionRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth contributions
 */

use ThothApi\GraphQL\Models\Contribution as ThothContribution;

class ThothContributionRepository
{
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
