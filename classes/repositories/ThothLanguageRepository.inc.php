<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothLanguageRepository.inc.php
 *
 * Copyright (c) 2025 Lepidus Tecnologia
 * Copyright (c) 2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLanguageRepository
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth languages
 */

use ThothApi\GraphQL\Models\Language as ThothLanguage;

class ThothLanguageRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothLanguage($data);
    }

    public function get($thothLanguageId)
    {
        return $this->thothClient->language($thothLanguageId);
    }

    public function add($thothLanguage)
    {
        return $this->thothClient->createLanguage($thothLanguage);
    }

    public function edit($thothPatchLanguage)
    {
        return $this->thothClient->updateLanguage($thothPatchLanguage);
    }

    public function delete($thothLanguageId)
    {
        return $this->thothClient->deleteLanguage($thothLanguageId);
    }
}
