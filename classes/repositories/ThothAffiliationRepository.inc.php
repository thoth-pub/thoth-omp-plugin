<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothAffiliationRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAffiliationRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth affiliations
 */

use ThothApi\GraphQL\Models\Affiliation as ThothAffiliation;

class ThothAffiliationRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothAffiliation($data);
    }

    public function get($thothAffiliationId)
    {
        return $this->thothClient->affiliation($thothAffiliationId);
    }

    public function add($thothAffiliation)
    {
        return $this->thothClient->createAffiliation($thothAffiliation);
    }

    public function edit($thothPatchAffiliation)
    {
        return $this->thothClient->updateAffiliation($thothPatchAffiliation);
    }

    public function delete($thothAffiliationId)
    {
        return $this->thothClient->deleteAffiliation($thothAffiliationId);
    }
}
