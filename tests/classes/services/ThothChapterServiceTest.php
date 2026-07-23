<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothChapterServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothChapterServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothChapterService
 *
 * @brief Test class for the ThothChapterService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

use APP\plugins\generic\thoth\classes\container\ThothContainer;
use APP\plugins\generic\thoth\classes\factories\ThothChapterFactory;
use APP\plugins\generic\thoth\classes\repositories\ThothChapterRepository;
use APP\plugins\generic\thoth\classes\services\ThothAbstractService;
use APP\plugins\generic\thoth\classes\services\ThothChapterService;
use APP\plugins\generic\thoth\classes\services\ThothContributionService;
use APP\plugins\generic\thoth\classes\services\ThothPublicationService;
use APP\plugins\generic\thoth\classes\services\ThothTitleService;
use APP\publication\Repository as PublicationRepository;
use Illuminate\Support\LazyCollection;
use Mockery;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

class ThothChapterServiceTest extends PKPTestCase
{
    protected array $backups = [];
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

    protected function getMockedContainerKeys(): array
    {
        return [...parent::getMockedContainerKeys(), PublicationRepository::class];
    }

    public function testRegisterChapter()
    {
        $container = ThothContainer::getInstance();

        $container->set('client', function () {
            return $this->getMockBuilder(ThothClient::class)->getMock();
        });

        $mockAbstractService = $this->createMock(ThothAbstractService::class);
        $mockAbstractService->expects($this->once())->method('registerByChapter');
        $mockContributionService = $this->createMock(ThothContributionService::class);
        $mockContributionService->expects($this->once())->method('registerByChapter');

        $mockPublicationService = $this->createMock(ThothPublicationService::class);
        $mockPublicationService->expects($this->once())->method('registerByChapter');

        $publicationRepoMock = Mockery::mock(app(PublicationRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn(
                Mockery::mock(\APP\publication\Publication::class)
                    ->shouldReceive('getData')
                    ->with('submissionId')
                    ->andReturn(99)
                    ->shouldReceive('getData')
                    ->with('locale')
                    ->andReturn('en_US')
                    ->getMock()
            )
            ->getMock();
        app()->instance(PublicationRepository::class, $publicationRepoMock);

        $mockTitleService = $this->createMock(ThothTitleService::class);
        $mockTitleService->expects($this->once())->method('registerByChapter');

        $mockFactory = $this->getMockBuilder(ThothChapterFactory::class)
            ->onlyMethods(['createFromChapter'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromChapter')
            ->willReturn(new ThothWork());

        $mockRepository = $this->getMockBuilder(ThothChapterRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->willReturn('fed8b9ee-2537-4a66-a1a1-eeadf4001c59');

        $mockChapter = $this->getMockBuilder(\APP\monograph\Chapter::class)
            ->onlyMethods(['getAuthors', 'getData'])
            ->getMock();
        $mockChapter->expects($this->any())
            ->method('getAuthors')
            ->willReturn(LazyCollection::make([]));
        $mockChapter->expects($this->any())
            ->method('getData')
            ->willReturnMap([
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
            ]);

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

    public function testUpdateChapterAndItsMetadata(): void
    {
        $publicationRepoMock = Mockery::mock(app(PublicationRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->with(99)
            ->andReturn(
                Mockery::mock(\APP\publication\Publication::class)
                    ->shouldReceive('getData')
                    ->with('locale')
                    ->andReturn('en_US')
                    ->getMock()
            )
            ->getMock();
        app()->instance(PublicationRepository::class, $publicationRepoMock);

        $authors = [Mockery::mock(\APP\author\Author::class)];
        $chapter = $this->getMockBuilder(\APP\monograph\Chapter::class)
            ->onlyMethods(['getAuthors', 'getData', 'setData'])
            ->getMock();
        $chapter->method('getAuthors')->willReturn(LazyCollection::make($authors));
        $chapter->method('getData')->willReturnMap([
            ['publicationId', null, 99],
        ]);
        $chapter->expects($this->once())
            ->method('setData')
            ->with('thothChapterId', 'chapter-id');

        $desiredWork = new ThothWork();
        $factory = $this->createMock(ThothChapterFactory::class);
        $factory->expects($this->once())
            ->method('createFromChapter')
            ->with($chapter)
            ->willReturn($desiredWork);
        $repository = $this->getMockBuilder(ThothChapterRepository::class)
            ->setConstructorArgs([$this->createMock(ThothClient::class)])
            ->onlyMethods(['edit'])
            ->getMock();
        $repository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function (ThothWork $work): bool {
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
