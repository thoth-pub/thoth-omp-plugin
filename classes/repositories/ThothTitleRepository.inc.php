<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothTitleRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothTitleRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth titles
 */

use ThothApi\GraphQL\Inputs\PatchTitle as ThothTitle;

import('plugins.generic.thoth.classes.formatters.ThothMarkupFormat');

class ThothTitleRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothTitle($data);
    }

    public function add($thothTitle)
    {
        return $this->thothClient->createTitle(
            ThothMarkupFormat::fromContent($thothTitle->getFullTitle()),
            $thothTitle
        );
    }

    public function edit($thothPatchTitle)
    {
        return $this->thothClient->updateTitle(
            ThothMarkupFormat::fromContent($thothPatchTitle->getFullTitle()),
            $thothPatchTitle
        );
    }

    public function delete($thothTitleId)
    {
        return $this->thothClient->deleteTitle($thothTitleId);
    }
}
