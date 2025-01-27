<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothLanguageServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLanguageServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothLanguageService
 *
 * @brief Test class for the ThothLanguageService class
 */

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Models\Language as ThothLanguage;

import('plugins.generic.thoth.classes.services.ThothLanguageService');

class ThothLanguageServiceTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->languageService = new ThothLanguageService();
    }

    protected function tearDown(): void
    {
        unset($this->languageService);
        parent::tearDown();
    }

    public function testCreateNewLanguage()
    {
        $expectedThothLanguage = new ThothLanguage();
        $expectedThothLanguage->setLanguageCode('ENG');
        $expectedThothLanguage->setLanguageRelation(ThothLanguage::LANGUAGE_RELATION_ORIGINAL);
        $expectedThothLanguage->setMainLanguage(true);

        $params = [
            'languageCode' => 'ENG',
            'languageRelation' => ThothLanguage::LANGUAGE_RELATION_ORIGINAL,
            'mainLanguage' => true
        ];

        $thothLanguage = $this->languageService->new($params);

        $this->assertEquals($expectedThothLanguage, $thothLanguage);
    }

    public function testRegisterLanguage()
    {
        $workId = '0600200b-865b-4706-a7e5-b5861a60dbc4';

        $expectedThothLanguage = new ThothLanguage();
        $expectedThothLanguage->setLanguageId('47b9ecbe-98af-4c01-8b5c-0c222e996429');
        $expectedThothLanguage->setWorkId($workId);
        $expectedThothLanguage->setLanguageCode('ENG');
        $expectedThothLanguage->setLanguageRelation(ThothLanguage::LANGUAGE_RELATION_ORIGINAL);
        $expectedThothLanguage->setMainLanguage(true);

        $submissionLocale = 'en_US';

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods([
                'createLanguage',
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createLanguage')
            ->will($this->returnValue('47b9ecbe-98af-4c01-8b5c-0c222e996429'));

        ThothContainer::getInstance()->set('client', function () use ($mockThothClient) {
            return $mockThothClient;
        });

        $thothLanguage = $this->languageService->register($submissionLocale, $workId);
        $this->assertEquals($expectedThothLanguage, $thothLanguage);
    }
}
