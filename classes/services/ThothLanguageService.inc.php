<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothLanguageService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLanguageService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth languages
 */

use ThothApi\GraphQL\Models\Language as ThothLanguage;

class ThothLanguageService
{
    public function new($params)
    {
        $thothLanguage = new ThothLanguage();
        $thothLanguage->setLanguageId($params['languageId'] ?? null);
        $thothLanguage->setWorkId($params['workId'] ?? null);
        $thothLanguage->setLanguageCode($params['languageCode']);
        $thothLanguage->setLanguageRelation($params['languageRelation']);
        $thothLanguage->setMainLanguage($params['mainLanguage']);
        return $thothLanguage;
    }

    public function register($submissionLocale, $thothWorkId)
    {
        $thothLanguage = $this->new([
            'workId' => $thothWorkId,
            'languageCode' => strtoupper(AppLocale::getIso3FromLocale($submissionLocale)),
            'languageRelation' => ThothLanguage::LANGUAGE_RELATION_ORIGINAL,
            'mainLanguage' => true
        ]);

        $thothClient = ThothContainer::getInstance()->get('client');
        $thothLanguageId = $thothClient->createLanguage($thothLanguage);
        $thothLanguage->setLanguageId($thothLanguageId);

        return $thothLanguage;
    }
}
