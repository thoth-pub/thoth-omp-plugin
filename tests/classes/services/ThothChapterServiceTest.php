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
use APP\plugins\generic\thoth\classes\services\ThothChapterService;
use APP\plugins\generic\thoth\classes\services\ThothContributionService;
use APP\plugins\generic\thoth\classes\services\ThothPublicationService;
use APP\publication\Repository as PublicationRepository;
use Illuminate\Support\LazyCollection;
use Mockery;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Work as ThothWork;

class ThothChapterServiceTest extends PKPTestCase
{
    protected array $backups = [];
    public function setUp(): void
    {
        parent::setUp();
        $container = ThothContainer::getInstance();
        $this->backups = [
            'client' => $container->backup('client'),
            'contributionService' => $container->backup('contributionService'),
            'publicationService' => $container->backup('publicationService'),
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

        $mockContributionService = $this->createMock(ThothContributionService::class);
        $mockContributionService->method('registerByChapter');
        $container->set('contributionService', fn () => $mockContributionService);

        $mockPublicationService = $this->createMock(ThothPublicationService::class);
        $mockPublicationService->method('registerByChapter');
        $container->set('publicationService', fn () => $mockPublicationService);

        $publicationRepoMock = Mockery::mock(app(PublicationRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn(
                Mockery::mock(\APP\publication\Publication::class)
                    ->shouldReceive('getData')
                    ->with('submissionId')
                    ->andReturn(99)
                    ->getMock()
            )
            ->getMock();
        app()->instance(PublicationRepository::class, $publicationRepoMock);

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
                ['thothChapterId', null, 'a518bebb-4a2c-48bb-8781-071ece2f2745']
            ]);

        $thothImprintId = 'd7991bfa-0ed3-432f-b9bd-0c7d0a4a1dec';

        $service = new ThothChapterService($mockFactory, $mockRepository);
        $thothChapterId = $service->register($mockChapter, $thothImprintId);

        $this->assertSame('fed8b9ee-2537-4a66-a1a1-eeadf4001c59', $thothChapterId);
    }
}
