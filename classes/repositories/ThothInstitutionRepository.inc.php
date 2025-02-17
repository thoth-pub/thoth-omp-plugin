<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothInstitutionRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothInstitutionRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth institutions
 */

use ThothApi\GraphQL\Models\Institution as ThothInstitution;

class ThothInstitutionRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothInstitution($data);
    }

    public function get($thothInstitutionId)
    {
        return $this->thothClient->institution($thothInstitutionId);
    }

    public function find($filter)
    {
        $thothInstitutions = $this->thothClient->institutions([
            'filter' => $filter,
            'limit' => 1
        ]);

        if (empty($thothInstitutions)) {
            return null;
        }

        return array_shift($thothInstitutions);
    }

    public function add($thothInstitution)
    {
        return $this->thothClient->createInstitution($thothInstitution);
    }

    public function edit($thothPatchInstitution)
    {
        return $this->thothClient->updateInstitution($thothPatchInstitution);
    }

    public function delete($thothInstitutionId)
    {
        return $this->thothClient->deleteInstitution($thothInstitutionId);
    }
}
