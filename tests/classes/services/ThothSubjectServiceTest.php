<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/services/ThothSubjectServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSubjectServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothSubjectService
 *
 * @brief Test class for the ThothSubjectService class
 */

use APP\publication\Publication;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\SubjectType;

import('plugins.generic.thoth.classes.repositories.ThothSubjectRepository');
import('plugins.generic.thoth.classes.services.ThothSubjectClassifier');
import('plugins.generic.thoth.classes.services.ThothSubjectService');

class ThothSubjectServiceTest extends PKPTestCase
{
    public function testRegisterSubject()
    {
        $mockRepository = $this->getMockBuilder(ThothSubjectRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('ebad8694-0dbe-48cf-a704-5d7e1f54b63d'));

        $keyword = 'Education';
        $sequence = 1;
        $thothWorkId = '114b96c3-6a51-45e6-a18a-f925128cb597';

        $service = new ThothSubjectService($mockRepository);
        $thothSubjectId = $service->register($keyword, $sequence, $thothWorkId);

        $this->assertSame('ebad8694-0dbe-48cf-a704-5d7e1f54b63d', $thothSubjectId);
    }

    public function testRegisterByPublicationIncludesCodedSubjectsBeforeKeywords()
    {
        $registeredSubjects = [];
        $repository = $this->getMockBuilder(ThothSubjectRepository::class)
            ->setConstructorArgs([$this->createMock(ThothClient::class)])
            ->setMethods(['add'])
            ->getMock();
        $repository->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function ($subject) use (&$registeredSubjects) {
                $registeredSubjects[] = $subject->getAllData();
                return 'subject-id';
            });

        $publication = $this->createMock(Publication::class);
        $publication->method('getData')->willReturnMap([
            ['thothBookId', null, 'work-id'],
            ['locale', null, 'en'],
            ['subjects', null, ['en' => ['10:EDU000000']]],
            ['keywords', null, ['en' => ['Open access']]],
        ]);

        $service = new ThothSubjectService($repository);
        $service->registerByPublication($publication);

        $this->assertSame([
            [
                'workId' => 'work-id',
                'subjectType' => SubjectType::BISAC,
                'subjectCode' => 'EDU000000',
                'subjectOrdinal' => 1,
            ],
            [
                'workId' => 'work-id',
                'subjectType' => SubjectType::KEYWORD,
                'subjectCode' => 'Open access',
                'subjectOrdinal' => 2,
            ],
        ], $registeredSubjects);
    }

    public function testSynchronizeReconcilesSubjectsAndKeywords()
    {
        $repository = $this->getMockBuilder(ThothSubjectRepository::class)
            ->setConstructorArgs([$this->createMock(ThothClient::class)])
            ->setMethods(['getByWorkId', 'add', 'edit', 'delete'])
            ->getMock();
        $repository->method('getByWorkId')->with('work-id')->willReturn([
            [
                'subjectId' => 'thema-id',
                'workId' => 'work-id',
                'subjectType' => SubjectType::THEMA,
                'subjectCode' => 'MFGV',
                'subjectOrdinal' => 2,
            ],
            [
                'subjectId' => 'old-keyword-id',
                'workId' => 'work-id',
                'subjectType' => SubjectType::KEYWORD,
                'subjectCode' => 'Old keyword',
                'subjectOrdinal' => 1,
            ],
        ]);
        $repository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function ($subject) {
                return $subject->getAllData() === [
                    'workId' => 'work-id',
                    'subjectType' => SubjectType::THEMA,
                    'subjectCode' => 'MFGV',
                    'subjectOrdinal' => 1,
                    'subjectId' => 'thema-id',
                ];
            }));
        $repository->expects($this->once())->method('add');
        $repository->expects($this->once())->method('delete')->with('old-keyword-id');

        $publication = $this->createMock(Publication::class);
        $publication->method('getData')->willReturnMap([
            ['locale', null, 'en'],
            ['subjects', null, ['en' => ['MFGV']]],
            ['keywords', null, ['en' => ['Open access']]],
        ]);
        $classifier = new ThothSubjectClassifier(fn ($code) => false, fn ($code) => $code === 'MFGV');
        $service = new ThothSubjectService($repository, $classifier);

        $service->synchronizeByPublication($publication, 'work-id');
    }

    public function testUpdateReordersSubjectsWithoutOrdinalCollisions()
    {
        $currentSubjects = [
            'first-keyword-id' => [
                'subjectId' => 'first-keyword-id',
                'workId' => 'work-id',
                'subjectType' => SubjectType::KEYWORD,
                'subjectCode' => 'First keyword',
                'subjectOrdinal' => 1,
            ],
            'second-keyword-id' => [
                'subjectId' => 'second-keyword-id',
                'workId' => 'work-id',
                'subjectType' => SubjectType::KEYWORD,
                'subjectCode' => 'Second keyword',
                'subjectOrdinal' => 2,
            ],
        ];
        $assertAvailableOrdinal = function (array $subject, $subjectId = null) use (&$currentSubjects) {
            foreach ($currentSubjects as $currentSubjectId => $currentSubject) {
                if (
                    $currentSubjectId !== $subjectId
                    && $currentSubject['subjectType'] === $subject['subjectType']
                    && $currentSubject['subjectOrdinal'] === $subject['subjectOrdinal']
                ) {
                    throw new RuntimeException('A subject with this ordinal number and type already exists.');
                }
            }
        };

        $repository = $this->getMockBuilder(ThothSubjectRepository::class)
            ->setConstructorArgs([$this->createMock(ThothClient::class)])
            ->setMethods(['add', 'edit', 'delete'])
            ->getMock();
        $repository->method('add')
            ->willReturnCallback(
                function ($subject) use (&$currentSubjects, $assertAvailableOrdinal) {
                    $subjectData = $subject->getAllData();
                    $assertAvailableOrdinal($subjectData);
                    $subjectData['subjectId'] = 'new-subject-id';
                    $currentSubjects['new-subject-id'] = $subjectData;
                    return 'new-subject-id';
                }
            );
        $repository->method('edit')
            ->willReturnCallback(
                function ($subject) use (&$currentSubjects, $assertAvailableOrdinal) {
                    $subjectData = $subject->getAllData();
                    $subjectId = $subjectData['subjectId'];
                    $assertAvailableOrdinal($subjectData, $subjectId);
                    $currentSubjects[$subjectId] = $subjectData;
                    return $subjectId;
                }
            );
        $repository->expects($this->never())->method('delete');

        $service = new ThothSubjectService($repository);
        $service->update(
            [
                [
                    'subjectType' => SubjectType::KEYWORD,
                    'subjectCode' => 'New subject',
                    'subjectOrdinal' => 1,
                ],
                [
                    'subjectType' => SubjectType::KEYWORD,
                    'subjectCode' => 'First keyword',
                    'subjectOrdinal' => 2,
                ],
                [
                    'subjectType' => SubjectType::KEYWORD,
                    'subjectCode' => 'Second keyword',
                    'subjectOrdinal' => 3,
                ],
            ],
            'work-id',
            array_values($currentSubjects)
        );

        $this->assertSame([
            'First keyword' => 2,
            'Second keyword' => 3,
            'New subject' => 1,
        ], array_column($currentSubjects, 'subjectOrdinal', 'subjectCode'));
    }
}
