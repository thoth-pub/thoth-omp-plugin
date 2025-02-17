<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothLanguageService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLanguageService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth languages
 */

use PKP\i18n\LocaleConversion;
use ThothApi\GraphQL\Models\Language as ThothLanguage;

class ThothLanguageService
{
    public $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    public function register($locale, $thothWorkId)
    {
        $thothLanguage = $this->repository->new([
            'workId' => $thothWorkId,
            'languageCode' => strtoupper(LocaleConversion::get3LetterIsoFromLocale($locale)),
            'languageRelation' => ThothLanguage::LANGUAGE_RELATION_ORIGINAL,
            'mainLanguage' => true
        ]);

        return $this->repository->add($thothLanguage);
    }

    public function registerByPublication($publication)
    {
        $locale = $publication->getData('locale');
        $thothBookId = $publication->getData('thothBookId');
        $this->register($locale, $thothBookId);
    }
}
