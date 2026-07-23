<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothLanguageService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLanguageService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth languages
 */

namespace APP\plugins\generic\thoth\classes\services;

use APP\plugins\generic\thoth\classes\exceptions\MetadataSynchronizationException;
use PKP\i18n\LocaleConversion;
use ThothApi\GraphQL\Enums\LanguageRelation;

class ThothLanguageService
{
    public $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    public function register($locale, $thothWorkId)
    {
        return $this->repository->add($this->createLanguage($locale, $thothWorkId));
    }

    public function registerByPublication($publication)
    {
        $locale = $publication->getData('locale');
        $thothBookId = $publication->getData('thothBookId');
        $this->register($locale, $thothBookId);
    }

    public function synchronizeByPublication($publication, string $thothWorkId): void
    {
        $this->update(
            $publication->getData('locale'),
            $thothWorkId,
            $this->repository->getByWorkId($thothWorkId)
        );
    }

    public function update($locale, string $thothWorkId, array $existingLanguages): void
    {
        $originalLanguages = array_values(array_filter(
            $existingLanguages,
            fn ($language) => ($language['languageRelation'] ?? null) === LanguageRelation::ORIGINAL
        ));
        if (count($originalLanguages) > 1) {
            throw new MetadataSynchronizationException('Thoth work has multiple original languages');
        }

        $thothLanguage = $this->createLanguage($locale, $thothWorkId);
        $existingLanguage = $originalLanguages[0] ?? null;
        if ($existingLanguage === null) {
            $this->repository->add($thothLanguage);
            return;
        }

        if (strtoupper((string) ($existingLanguage['languageCode'] ?? '')) === $thothLanguage->getLanguageCode()) {
            return;
        }

        $thothLanguage->setLanguageId($existingLanguage['languageId']);
        $this->repository->edit($thothLanguage);
    }

    private function createLanguage($locale, string $thothWorkId)
    {
        return $this->repository->new([
            'workId' => $thothWorkId,
            'languageCode' => strtoupper(LocaleConversion::get3LetterIsoFromLocale($locale)),
            'languageRelation' => LanguageRelation::ORIGINAL,
        ]);
    }
}
