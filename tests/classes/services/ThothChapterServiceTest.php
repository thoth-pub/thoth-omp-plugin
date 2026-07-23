<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/services/ThothChapterServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothChapterServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothChapterService
 *
 * @brief Test class for the ThothChapterService class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Enums\WorkType;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

import('classes.monograph.Chapter');
import('classes.publication.Publication');
import('classes.publication.PublicationDAO');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.container.ThothContainer');
import('plugins.generic.thoth.classes.factories.ThothChapterFactory');
import('plugins.generic.thoth.classes.repositories.ThothChapterRepository');
import('plugins.generic.thoth.classes.services.ThothAbstractService');
import('plugins.generic.thoth.classes.services.ThothChapterService');
import('plugins.generic.thoth.classes.services.ThothContributionService');
import('plugins.generic.thoth.classes.services.ThothPublicationService');
import('plugins.generic.thoth.classes.services.ThothTitleService');

class ThothChapterServiceTest extends PKPTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $container = ThothContainer::getInstance();
        $this->backups = [
            'client' => $container->backup('client'),
            'abstractService' => $container->backup('abstractService'),
            'contributionService' => $container->backup('contributionService'),
            'publicationService' => $container->backup('publicationService'),
            'titleService' => $container->backup('titleService'),
        ];
    }

    protected function tearDown(): void
    {
        $container = ThothContainer::getInstance();
        foreach ($this->backups as $key => $factory) {
            $container->set($key, $factory);
        }
        parent::tearDown();
    }

    protected function getMockedDAOs()
    {
        return ['PublicationDAO'];
    }

    public function testRegisterChapter()
    {
        ThothContainer::getInstance()->set('client', function () {
            return $this->getMockBuilder(ThothClient::class)->getMock();
        });

        $mockAbstractService = $this->createMock(ThothAbstractService::class);
        $mockAbstractService->expects($this->once())->method('registerByChapter');

        $mockContributionService = $this->createMock(ThothContributionService::class);
        $mockContributionService->expects($this->once())->method('registerByChapter');

        $mockPublicationService = $this->createMock(ThothPublicationService::class);
        $mockPublicationService->expects($this->once())->method('registerByChapter');

        $mockPublication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData'])
            ->getMock();
        $mockPublication->expects($this->any())
            ->method('getData')
            ->will($this->returnValueMap([
                ['submissionId', null, 99],
                ['locale', null, 'en_US'],
            ]));

        $mockPublicationDao = $this->getMockBuilder(PublicationDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $mockPublicationDao->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($mockPublication));
        DAORegistry::registerDAO('PublicationDAO', $mockPublicationDao);

        $mockFactory = $this->getMockBuilder(ThothChapterFactory::class)
            ->setMethods(['createFromChapter'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromChapter')
            ->will($this->returnValue(new ThothWork()));

        $mockRepository = $this->getMockBuilder(ThothChapterRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('fed8b9ee-2537-4a66-a1a1-eeadf4001c59'));

        $mockTitleService = $this->createMock(ThothTitleService::class);
        $mockTitleService->expects($this->once())->method('registerByChapter');

        $mockResult = $this->getMockBuilder(DAOResultFactory::class)
            ->setMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockResult->method('toArray')
            ->will($this->returnValue([]));

        $mockChapter = $this->getMockBuilder(Chapter::class)
            ->setMethods(['getAuthors', 'getData'])
            ->getMock();
        $mockChapter->method('getAuthors')
            ->will($this->returnValue($mockResult));
        $mockChapter->expects($this->any())
            ->method('getData')
            ->will($this->returnValueMap([
                ['publicationId', null, 99],
                ['thothChapterId', null, 'a518bebb-4a2c-48bb-8781-071ece2f2745'],
                ['title', null, [
                    'en_US' => 'My chapter title',
                    'pt_BR' => 'Meu titulo de capitulo',
                ]],
                ['subtitle', null, [
                    'en_US' => 'My chapter subtitle',
                    'pt_BR' => 'Meu subtitulo de capitulo',
                ]],
                ['abstract', null, [
                    'en_US' => 'This is my chapter abstract',
                    'pt_BR' => 'Este e meu resumo de capitulo',
                ]],
            ]));

        $thothImprintId = 'd7991bfa-0ed3-432f-b9bd-0c7d0a4a1dec';

        $service = new ThothChapterService(
            $mockFactory,
            $mockRepository,
            $mockContributionService,
            $mockPublicationService,
            $mockTitleService,
            $mockAbstractService
        );
        $thothChapterId = $service->register($mockChapter, $thothImprintId);

        $this->assertSame('fed8b9ee-2537-4a66-a1a1-eeadf4001c59', $thothChapterId);
    }

    public function testUpdateChapterAndItsMetadata()
    {
        $publication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData'])
            ->getMock();
        $publication->method('getData')->willReturnMap([
            ['locale', null, 'en_US'],
        ]);
        $publicationDao = $this->getMockBuilder(PublicationDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $publicationDao->expects($this->once())
            ->method('getById')
            ->with(99)
            ->willReturn($publication);
        DAORegistry::registerDAO('PublicationDAO', $publicationDao);

        $authors = [new stdClass()];
        $authorResult = $this->getMockBuilder(DAOResultFactory::class)
            ->setMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $authorResult->method('toArray')->willReturn($authors);
        $chapter = $this->getMockBuilder(Chapter::class)
            ->setMethods(['getAuthors', 'getData', 'setData'])
            ->getMock();
        $chapter->method('getAuthors')->willReturn($authorResult);
        $chapter->method('getData')->willReturnMap([
            ['publicationId', null, 99],
        ]);
        $chapter->expects($this->once())
            ->method('setData')
            ->with('thothChapterId', 'chapter-id');

        $desiredWork = new ThothWork();
        $factory = $this->getMockBuilder(ThothChapterFactory::class)
            ->setMethods(['createFromChapter'])
            ->getMock();
        $factory->expects($this->once())
            ->method('createFromChapter')
            ->with($chapter)
            ->willReturn($desiredWork);
        $repository = $this->getMockBuilder(ThothChapterRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['edit'])
            ->getMock();
        $repository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function (ThothWork $work) {
                return $work->getWorkId() === 'chapter-id'
                    && $work->getImprintId() === 'imprint-id';
            }));

        $titleService = $this->createMock(ThothTitleService::class);
        $titleService->expects($this->once())
            ->method('updateByChapter')
            ->with($chapter, 'chapter-id', [['titleId' => 'title-id']], 'en_US');
        $abstractService = $this->createMock(ThothAbstractService::class);
        $abstractService->expects($this->once())
            ->method('updateByChapter')
            ->with($chapter, 'chapter-id', [['abstractId' => 'abstract-id']], 'en_US');
        $contributionService = $this->createMock(ThothContributionService::class);
        $contributionService->expects($this->once())
            ->method('update')
            ->with($authors, 'chapter-id', [['contributionId' => 'contribution-id']]);
        $publicationService = $this->createMock(ThothPublicationService::class);
        $publicationService->expects($this->once())
            ->method('updateByChapter')
            ->with(
                $chapter,
                'chapter-id',
                [['publicationId' => 'publication-id']],
                'FORTHCOMING'
            )
            ->willReturn(true);

        $service = new ThothChapterService(
            $factory,
            $repository,
            $contributionService,
            $publicationService,
            $titleService,
            $abstractService
        );

        $this->assertTrue($service->update($chapter, [
            'workId' => 'chapter-id',
            'workStatus' => 'FORTHCOMING',
            'titles' => [['titleId' => 'title-id']],
            'abstracts' => [['abstractId' => 'abstract-id']],
            'contributions' => [['contributionId' => 'contribution-id']],
            'publications' => [['publicationId' => 'publication-id']],
        ], 'imprint-id'));
    }
}
