<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothBiographyRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBiographyRepository
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth biographies
 */

use ThothApi\GraphQL\Models\Biography as ThothBiography;

class ThothBiographyRepository
{
    private const MARKUP_FORMAT = 'HTML';

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
        return $this->thothClient->createBiography($thothBiography, self::MARKUP_FORMAT);
    }

    public function edit($thothPatchBiography)
    {
        return $this->thothClient->updateBiography($thothPatchBiography, self::MARKUP_FORMAT);
    }

    public function delete($thothBiographyId)
    {
        return $this->thothClient->deleteBiography($thothBiographyId);
    }
}
