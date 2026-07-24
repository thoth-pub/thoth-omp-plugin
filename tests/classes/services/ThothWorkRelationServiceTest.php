<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/services/ThothWorkRelationServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkRelationServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothWorkRelationService
 *
 * @brief Test class for the ThothWorkRelationService class
 */

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\RelationType;
use ThothApi\GraphQL\Enums\WorkType;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

import('plugins.generic.thoth.classes.factories.ThothChapterFactory');
import('plugins.generic.thoth.classes.repositories.ThothWorkRelationRepository');
import('plugins.generic.thoth.classes.repositories.ThothChapterRepository');
import('plugins.generic.thoth.classes.services.ThothChapterService');
import('plugins.generic.thoth.classes.services.ThothWorkRelationService');
import('plugins.generic.thoth.classes.exceptions.MetadataSynchronizationException');

class ThothWorkRelationServiceTest extends PKPTestCase
{
    public function testRegisterWorkRelation()
    {
        $mockChapterService = $this->createMock(ThothChapterService::class);
        $mockChapterService->expects($this->once())
            ->method('register')
            ->will($this->returnValue('dccd9dfd-fee2-4e85-b1f8-0440f9b43ce8'));

        $mockRepository = $this->getMockBuilder(ThothWorkRelationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('91966e15-0203-4eb8-b7e7-02b72c57cedc'));

        $mockChapter = $this->getMockBuilder(\APP\monograph\Chapter::class)->getMock();
        $thothRelatedWorkId = '813e0519-05ca-455b-b330-af623456dace';
        $thothImprintId = '41b6a2a4-c3e1-4045-882c-c0f31386dee5';

        $service = new ThothWorkRelationService($mockRepository, $mockChapterService);
        $thothWorkRelationId = $service->register($mockChapter, $thothRelatedWorkId, $thothImprintId);

        $this->assertSame('91966e15-0203-4eb8-b7e7-02b72c57cedc', $thothWorkRelationId);
    }

    public function testUpdateReconcilesChaptersAndBookRelations()
    {
        $existingChapter = $this->createChapter(0, 'Existing chapter');
        $newChapter = $this->createChapter(1, 'New chapter');
        $desiredExistingWork = new ThothWork([
            'doi' => 'https://doi.org/10.1234/EXISTING',
            'landingPage' => 'https://press.example/book',
        ]);
        $desiredNewWork = new ThothWork([
            'doi' => '10.1234/new',
            'landingPage' => 'https://press.example/book',
        ]);

        $chapterService = $this->createMock(ThothChapterService::class);
        $chapterService->expects($this->exactly(2))
            ->method('getDesiredWork')
            ->willReturnMap([
                [$existingChapter, 'imprint-id', $desiredExistingWork],
                [$newChapter, 'imprint-id', $desiredNewWork],
            ]);
        $chapterService->expects($this->once())
            ->method('update')
            ->with(
                $existingChapter,
                $this->callback(function (array $chapter) {
                    return $chapter['workId'] === 'existing-work-id';
                }),
                'imprint-id',
                $desiredExistingWork
            )
            ->willReturn(true);
        $chapterService->expects($this->once())
            ->method('register')
            ->with($newChapter, 'imprint-id', $desiredNewWork)
            ->willReturn('new-work-id');
        $chapterService->expects($this->once())
            ->method('delete')
            ->with('removed-work-id');

        $repository = $this->getMockBuilder(ThothWorkRelationRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['add', 'edit', 'delete'])
            ->getMock();
        $repository->expects($this->once())
            ->method('delete')
            ->with('removed-relation-id');
        $editedOrdinals = [];
        $repository->expects($this->exactly(2))
            ->method('edit')
            ->with($this->callback(function ($relation) use (&$editedOrdinals) {
                $editedOrdinals[] = $relation->getRelationOrdinal();
                return $relation->getWorkRelationId() === 'existing-relation-id';
            }));
        $repository->expects($this->once())
            ->method('add')
            ->with($this->callback(function ($relation) {
                return $relation->getRelatorWorkId() === 'book-id'
                    && $relation->getRelatedWorkId() === 'new-work-id'
                    && $relation->getRelationType() === RelationType::HAS_CHILD
                    && $relation->getRelationOrdinal() === 2;
            }))
            ->willReturn('new-relation-id');

        $service = new ThothWorkRelationService($repository, $chapterService);
        $deletionsSkipped = $service->update(
            [$existingChapter, $newChapter],
            'book-id',
            'imprint-id',
            [
                $this->createExistingRelation(
                    'removed-relation-id',
                    'removed-work-id',
                    1,
                    '10.1234/removed',
                    'Removed chapter'
                ),
                $this->createExistingRelation(
                    'existing-relation-id',
                    'existing-work-id',
                    2,
                    '10.1234/existing',
                    'Existing chapter'
                ),
            ]
        );

        $this->assertTrue($deletionsSkipped);
        $this->assertSame([5, 1], $editedOrdinals);
    }

    public function testUpdateRejectsAmbiguousDoiMatches()
    {
        $chapter = $this->createChapter(0, 'Chapter');
        $desiredWork = new ThothWork(['doi' => '10.1234/chapter']);
        $chapterService = $this->createMock(ThothChapterService::class);
        $chapterService->method('getDesiredWork')->willReturn($desiredWork);
        $repository = $this->getMockBuilder(ThothWorkRelationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $service = new ThothWorkRelationService($repository, $chapterService);

        $this->expectException(MetadataSynchronizationException::class);
        $service->update(
            [$chapter],
            'book-id',
            'imprint-id',
            [
                $this->createExistingRelation('relation-1', 'work-1', 1, '10.1234/chapter', 'Chapter'),
                $this->createExistingRelation('relation-2', 'work-2', 2, '10.1234/chapter', 'Chapter'),
            ]
        );
    }

    private function createChapter($sequence, $title)
    {
        $chapter = $this->getMockBuilder(\APP\monograph\Chapter::class)
            ->setMethods(['getSequence', 'getLocalizedFullTitle'])
            ->getMock();
        $chapter->method('getSequence')->willReturn((float) $sequence);
        $chapter->method('getLocalizedFullTitle')->willReturn($title);
        return $chapter;
    }

    private function createExistingRelation($relationId, $workId, $ordinal, $doi, $title)
    {
        return [
            'workRelationId' => $relationId,
            'relatorWorkId' => 'book-id',
            'relatedWorkId' => $workId,
            'relationType' => RelationType::HAS_CHILD,
            'relationOrdinal' => $ordinal,
            'relatedWork' => [
                'workId' => $workId,
                'workType' => WorkType::BOOK_CHAPTER,
                'workStatus' => 'FORTHCOMING',
                'doi' => $doi,
                'fullTitle' => $title,
                'titles' => [],
                'abstracts' => [],
                'contributions' => [],
                'publications' => [],
            ],
        ];
    }
}
