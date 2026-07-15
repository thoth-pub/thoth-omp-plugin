<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothBiographyRepository.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBiographyRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth biographies
 */

namespace APP\plugins\generic\thoth\classes\repositories;

use APP\plugins\generic\thoth\classes\formatters\ThothMarkupFormat;
use ThothApi\GraphQL\Inputs\PatchBiography as ThothBiography;

class ThothBiographyRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothBiography($data);
    }

    public function add($thothBiography)
    {
        return $this->thothClient->createBiography(
            ThothMarkupFormat::fromContent($thothBiography->getContent()),
            $thothBiography
        );
    }

    public function edit($thothPatchBiography)
    {
        return $this->thothClient->updateBiography(
            ThothMarkupFormat::fromContent($thothPatchBiography->getContent()),
            $thothPatchBiography
        );
    }

    public function delete($thothBiographyId)
    {
        return $this->thothClient->deleteBiography($thothBiographyId);
    }
}
