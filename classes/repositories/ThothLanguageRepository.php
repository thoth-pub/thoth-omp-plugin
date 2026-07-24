<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothLanguageRepository.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLanguageRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth languages
 */

namespace APP\plugins\generic\thoth\classes\repositories;

use ThothApi\GraphQL\Inputs\PatchLanguage as ThothLanguage;

class ThothLanguageRepository
{
    private const WORK_LANGUAGES_SELECTION = [
        'languages' => [
            'languageId',
            'workId',
            'languageCode',
            'languageRelation',
        ],
    ];
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

    public function getByWorkId(string $thothWorkId): array
    {
        $thothWork = $this->thothClient->work($thothWorkId, self::WORK_LANGUAGES_SELECTION);

        return array_map(
            fn ($language) => $language->toArray(),
            $thothWork->getLanguages() ?? []
        );
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
