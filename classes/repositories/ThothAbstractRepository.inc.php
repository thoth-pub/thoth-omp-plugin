<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothAbstractRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAbstractRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth abstracts
 */

use ThothApi\GraphQL\Inputs\PatchAbstract as ThothAbstract;

import('plugins.generic.thoth.classes.formatters.ThothMarkupFormat');

class ThothAbstractRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothAbstract($data);
    }

    public function add($thothAbstract)
    {
        return $this->thothClient->createAbstract(
            ThothMarkupFormat::fromContent($thothAbstract->getContent()),
            $thothAbstract
        );
    }

    public function edit($thothPatchAbstract)
    {
        return $this->thothClient->updateAbstract(
            ThothMarkupFormat::fromContent($thothPatchAbstract->getContent()),
            $thothPatchAbstract
        );
    }

    public function delete($thothAbstractId)
    {
        return $this->thothClient->deleteAbstract($thothAbstractId);
    }
}
