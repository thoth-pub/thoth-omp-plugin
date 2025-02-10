<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothWorkRelationRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkRelationRepository
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth work relations
 */

use ThothApi\GraphQL\Models\WorkRelation as ThothWorkRelation;

class ThothWorkRelationRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothWorkRelation($data);
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
