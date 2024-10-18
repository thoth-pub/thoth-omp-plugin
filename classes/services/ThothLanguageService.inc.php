<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothLanguageService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLanguageService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth languages
 */

use PKP\i18n\LocaleConversion;

import('plugins.generic.thoth.lib.thothAPI.models.ThothLanguage');

class ThothLanguageService
{
    public function new($params)
    {
        $thothLanguage = new ThothLanguage();
        $thothLanguage->setId($params['languageId'] ?? null);
        $thothLanguage->setWorkId($params['workId'] ?? null);
        $thothLanguage->setLanguageCode($params['languageCode']);
        $thothLanguage->setLanguageRelation($params['languageRelation']);
        $thothLanguage->setMainLanguage($params['mainLanguage']);
        return $thothLanguage;
    }

    public function register($thothClient, $submissionLocale, $thothWorkId)
    {
        $thothLanguage = $this->new([
            'workId' => $thothWorkId,
            'languageCode' => strtoupper(LocaleConversion::getIso3FromLocale($submissionLocale)),
            'languageRelation' => ThothLanguage::LANGUAGE_RELATION_ORIGINAL,
            'mainLanguage' => true
        ]);

        $thothLanguageId = $thothClient->createLanguage($thothLanguage);
        $thothLanguage->setId($thothLanguageId);

        return $thothLanguage;
    }
}
