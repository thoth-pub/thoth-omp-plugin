<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/services/ThothLanguageServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
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
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\LanguageRelation;

import('plugins.generic.thoth.classes.exceptions.MetadataSynchronizationException');
import('plugins.generic.thoth.classes.repositories.ThothLanguageRepository');
import('plugins.generic.thoth.classes.services.ThothLanguageService');

class ThothLanguageServiceTest extends PKPTestCase
{
    public function testRegisterLanguage()
    {
        $mockRepository = $this->getMockBuilder(ThothLanguageRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('d3ddc7b3-d5f3-4394-9c34-320cd222a497'));

        $locale = 'en_US';
        $thothWorkId = 'fdd9321f-84e3-4d19-a914-24289e8aec09';

        $service = new ThothLanguageService($mockRepository);
        $thothLanguageId = $service->register($locale, $thothWorkId);

        $this->assertSame('d3ddc7b3-d5f3-4394-9c34-320cd222a497', $thothLanguageId);
    }

    public function testSynchronizeUpdatesOriginalLanguageAndPreservesTranslations()
    {
        $publication = $this->createMock(\APP\publication\Publication::class);
        $publication->method('getData')->with('locale')->willReturn('pt_BR');
        $repository = $this->getMockRepository();
        $repository->method('getByWorkId')->willReturn([
            [
                'languageId' => 'original-language-id',
                'workId' => 'work-id',
                'languageCode' => 'ENG',
                'languageRelation' => LanguageRelation::ORIGINAL,
            ],
            [
                'languageId' => 'translation-language-id',
                'workId' => 'work-id',
                'languageCode' => 'FRA',
                'languageRelation' => LanguageRelation::TRANSLATED_INTO,
            ],
        ]);
        $repository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function ($language) {
                return $language->getAllData() === [
                    'workId' => 'work-id',
                    'languageCode' => 'POR',
                    'languageRelation' => LanguageRelation::ORIGINAL,
                    'languageId' => 'original-language-id',
                ];
            }));
        $repository->expects($this->never())->method('add');

        $service = new ThothLanguageService($repository);

        $service->synchronizeByPublication($publication, 'work-id');
    }

    public function testSynchronizeSkipsUnchangedOriginalLanguage()
    {
        $publication = $this->createMock(\APP\publication\Publication::class);
        $publication->method('getData')->with('locale')->willReturn('en');
        $repository = $this->getMockRepository();
        $repository->method('getByWorkId')->willReturn([
            [
                'languageId' => 'original-language-id',
                'workId' => 'work-id',
                'languageCode' => 'ENG',
                'languageRelation' => LanguageRelation::ORIGINAL,
            ],
        ]);
        $repository->expects($this->never())->method('edit');
        $repository->expects($this->never())->method('add');

        $service = new ThothLanguageService($repository);

        $service->synchronizeByPublication($publication, 'work-id');
    }

    public function testSynchronizeCreatesOriginalLanguageWhenMissing()
    {
        $publication = $this->createMock(\APP\publication\Publication::class);
        $publication->method('getData')->with('locale')->willReturn('en');
        $repository = $this->getMockRepository();
        $repository->method('getByWorkId')->willReturn([
            [
                'languageId' => 'translation-language-id',
                'workId' => 'work-id',
                'languageCode' => 'FRA',
                'languageRelation' => LanguageRelation::TRANSLATED_INTO,
            ],
        ]);
        $repository->expects($this->once())
            ->method('add')
            ->with($this->callback(function ($language) {
                return $language->getAllData() === [
                    'workId' => 'work-id',
                    'languageCode' => 'ENG',
                    'languageRelation' => LanguageRelation::ORIGINAL,
                ];
            }));
        $repository->expects($this->never())->method('edit');

        $service = new ThothLanguageService($repository);

        $service->synchronizeByPublication($publication, 'work-id');
    }

    public function testSynchronizeRejectsMultipleOriginalLanguages()
    {
        $publication = $this->createMock(\APP\publication\Publication::class);
        $publication->method('getData')->with('locale')->willReturn('en');
        $repository = $this->getMockRepository();
        $repository->method('getByWorkId')->willReturn([
            [
                'languageId' => 'first-language-id',
                'languageRelation' => LanguageRelation::ORIGINAL,
            ],
            [
                'languageId' => 'second-language-id',
                'languageRelation' => LanguageRelation::ORIGINAL,
            ],
        ]);
        $repository->expects($this->never())->method('edit');
        $repository->expects($this->never())->method('add');

        $service = new ThothLanguageService($repository);

        $this->expectException(MetadataSynchronizationException::class);
        $service->synchronizeByPublication($publication, 'work-id');
    }

    private function getMockRepository()
    {
        return $this->getMockBuilder(ThothLanguageRepository::class)
            ->setConstructorArgs([$this->createMock(ThothClient::class)])
            ->setMethods(['getByWorkId', 'add', 'edit'])
            ->getMock();
    }
}
