<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothLanguageRepositoryTest.php
 *
 * Copyright (c) 2025 Lepidus Tecnologia
 * Copyright (c) 2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLanguageRepositoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothLanguageRepository
 *
 * @brief Test class for the ThothLanguageRepository class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Language as ThothLanguage;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothLanguageRepository');

class ThothLanguageRepositoryTest extends PKPTestCase
{
    public function testNewThothLanguage()
    {
        $data = [
            'languageCode' => 'ENG',
            'languageRelation' => ThothLanguage::LANGUAGE_RELATION_ORIGINAL,
            'mainLanguage' => true
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothLanguageRepository($mockThothClient);

        $thothLanguage = $repository->new($data);
        $this->assertSame($data, $thothLanguage->getAllData());
    }

    public function testGetLanguage()
    {
        $expectedThothLanguage = new ThothLanguage([
            'languageId' => '01ff14a6-da2d-466b-a49f-ec1061fce8da',
            'languageCode' => 'ENG',
            'languageRelation' => ThothLanguage::LANGUAGE_RELATION_ORIGINAL,
            'mainLanguage' => true
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['language'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('language')
            ->will($this->returnValue($expectedThothLanguage));

        $repository = new ThothLanguageRepository($mockThothClient);
        $thothLanguage = $repository->get('8a3a7422-e5fb-4d2d-810d-513987735b4e');

        $this->assertEquals($expectedThothLanguage, $thothLanguage);
    }

    public function testAddLanguage()
    {
        $thothLanguage = new ThothLanguage([
            'languageCode' => 'ENG',
            'languageRelation' => ThothLanguage::LANGUAGE_RELATION_ORIGINAL,
            'mainLanguage' => true
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createLanguage'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createLanguage')
            ->will($this->returnValue('531ee83b-2a71-41f5-b4db-3af150c6ecde'));

        $repository = new ThothLanguageRepository($mockThothClient);
        $thothLanguageId = $repository->add($thothLanguage);

        $this->assertEquals('531ee83b-2a71-41f5-b4db-3af150c6ecde', $thothLanguageId);
    }

    public function testEditLanguage()
    {
        $thothPatchLanguage = new ThothLanguage([
            'languageId' => '39200d3a-397d-4d39-a6b2-86089520615a',
            'languageCode' => 'ESP',
            'languageRelation' => ThothLanguage::LANGUAGE_RELATION_ORIGINAL,
            'mainLanguage' => true
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['updateLanguage'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updateLanguage')
            ->will($this->returnValue('39200d3a-397d-4d39-a6b2-86089520615a'));

        $repository = new ThothLanguageRepository($mockThothClient);
        $thothLanguageId = $repository->edit($thothPatchLanguage);

        $this->assertEquals('39200d3a-397d-4d39-a6b2-86089520615a', $thothLanguageId);
    }

    public function testDeleteLanguage()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['deleteLanguage'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deleteLanguage')
            ->will($this->returnValue('dbad156c-f368-4ef7-8ccd-1c488b5e5189'));

        $repository = new ThothLanguageRepository($mockThothClient);
        $thothLanguageId = $repository->delete('dbad156c-f368-4ef7-8ccd-1c488b5e5189');

        $this->assertEquals('dbad156c-f368-4ef7-8ccd-1c488b5e5189', $thothLanguageId);
    }
}
