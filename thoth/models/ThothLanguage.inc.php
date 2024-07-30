<?php

/**
 * @file plugins/generic/thoth/thoth/models/ThothLanguage.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLanguage
 * @ingroup plugins_generic_thoth
 *
 * @brief Class for a Thoth language.
 */

import('plugins.generic.thoth.thoth.models.ThothModel');

class ThothLanguage extends ThothModel
{
    private $languageId;

    private $workId;

    private $languageCode;

    private $languageRelation;

    private $mainLanguage;

    public const LANGUAGE_RELATION_ORIGINAL = 'ORIGINAL';

    public function getEnumeratedValues()
    {
        return parent::getEnumeratedValues() + [
            'languageRelation'
        ];
    }

    public function getReturnValue()
    {
        return 'languageId';
    }

    public function getId()
    {
        return $this->languageId;
    }

    public function setId($languageId)
    {
        $this->languageId = $languageId;
    }

    public function getWorkId()
    {
        return $this->workId;
    }

    public function setWorkId($workId)
    {
        $this->workId = $workId;
    }

    public function getLanguageCode()
    {
        return $this->languageCode;
    }

    public function setLanguageCode($languageCode)
    {
        $this->languageCode = $languageCode;
    }

    public function getLanguageRelation()
    {
        return $this->languageRelation;
    }

    public function setLanguageRelation($languageRelation)
    {
        $this->languageRelation = $languageRelation;
    }

    public function getMainLanguage()
    {
        return $this->mainLanguage;
    }

    public function setMainLanguage($mainLanguage)
    {
        $this->mainLanguage = $mainLanguage;
    }
}
