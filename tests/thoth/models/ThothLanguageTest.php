<?php

/**
 * @file plugins/generic/thoth/tests/thoth/models/ThothLanguageTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLanguageTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothLanguage
 *
 * @brief Test class for the ThothLanguage class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.lib.thothAPI.models.ThothLanguage');

class ThothLanguageTest extends PKPTestCase
{
    public function testGettersAndSetters()
    {
        $language = new ThothLanguage();
        $language->setId('212aecd9-8b9e-4cc0-8ed7-9766c56b6de2');
        $language->setWorkId('f1963ed9-1b8d-43f7-af89-ee589b6e7116');
        $language->setLanguageCode('ENG');
        $language->setLanguageRelation(ThothLanguage::LANGUAGE_RELATION_ORIGINAL);
        $language->setMainLanguage(true);

        $this->assertEquals('212aecd9-8b9e-4cc0-8ed7-9766c56b6de2', $language->getId());
        $this->assertEquals('f1963ed9-1b8d-43f7-af89-ee589b6e7116', $language->getWorkId());
        $this->assertEquals('ENG', $language->getLanguageCode());
        $this->assertEquals(ThothLanguage::LANGUAGE_RELATION_ORIGINAL, $language->getLanguageRelation());
        $this->assertEquals(true, $language->getMainLanguage());
    }

    public function testGetLanguageData()
    {
        $language = new ThothLanguage();
        $language->setId('1da01129-e259-4ba4-8630-17ca5193d350');
        $language->setWorkId('cc020008-4a84-42fd-af6b-8a3a99aecd4f');
        $language->setLanguageCode('BRA');
        $language->setLanguageRelation(ThothLanguage::LANGUAGE_RELATION_ORIGINAL);
        $language->setMainLanguage(true);

        $this->assertEquals([
            'languageId' => '1da01129-e259-4ba4-8630-17ca5193d350',
            'workId' => 'cc020008-4a84-42fd-af6b-8a3a99aecd4f',
            'languageCode' => 'BRA',
            'languageRelation' => ThothLanguage::LANGUAGE_RELATION_ORIGINAL,
            'mainLanguage' => true
        ], $language->getData());
    }
}
