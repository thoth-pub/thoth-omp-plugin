<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothContributorRepository.inc.php
 *
 * Copyright (c) 2025 Lepidus Tecnologia
 * Copyright (c) 2025 Thoth
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
