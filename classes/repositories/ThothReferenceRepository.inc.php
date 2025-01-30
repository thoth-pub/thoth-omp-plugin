<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothReferenceRepository.inc.php
 *
 * Copyright (c) 2025 Lepidus Tecnologia
 * Copyright (c) 2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothReferenceRepository
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth references
 */

use ThothApi\GraphQL\Models\Reference as ThothReference;

class ThothReferenceRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothReference($data);
    }

    public function get($thothReferenceId)
    {
        return $this->thothClient->reference($thothReferenceId);
    }

    public function add($thothReference)
    {
        return $this->thothClient->createReference($thothReference);
    }

    public function edit($thothPatchReference)
    {
        return $this->thothClient->updateReference($thothPatchReference);
    }

    public function delete($thothReferenceId)
    {
        return $this->thothClient->deleteReference($thothReferenceId);
    }
}
