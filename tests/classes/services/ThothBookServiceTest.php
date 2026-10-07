<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothBookServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Test class for the ThothBookService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

use APP\plugins\generic\thoth\classes\factories\ThothBookFactory;
use APP\plugins\generic\thoth\classes\repositories\ThothBookRepository;
use APP\plugins\generic\thoth\classes\services\ThothAbstractService;
use APP\plugins\generic\thoth\classes\services\ThothBookService;
use APP\plugins\generic\thoth\classes\services\ThothFrontcoverService;
use APP\plugins\generic\thoth\classes\services\ThothPublicationService;
use APP\plugins\generic\thoth\classes\services\ThothTitleService;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

class ThothBookServiceTest extends PKPTestCase
{
    public function testUpdateOnlySynchronizesWorkMetadata(): void
    {
        $oldThothBook = new class () {
            public function toArray(): array
            {
                return [
                    'workId' => '9f65f147-1d9d-4dd1-9f78-89b58d088a2c',
                ];
            }
        };
        $newThothBook = new ThothWork([
            'doi' => 'https://doi.org/10.12345/updated',
        ]);

        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->onlyMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->willReturn($newThothBook);

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->createMock(ThothClient::class)])
            ->onlyMethods(['get', 'new', 'edit'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('get')
            ->with('9f65f147-1d9d-4dd1-9f78-89b58d088a2c')
            ->willReturn($oldThothBook);
        $mockRepository->expects($this->once())
            ->method('new')
            ->with([
                'workId' => '9f65f147-1d9d-4dd1-9f78-89b58d088a2c',
                'doi' => 'https://doi.org/10.12345/updated',
            ])
            ->willReturn($newThothBook);
        $mockRepository->expects($this->once())
            ->method('edit')
            ->with($newThothBook);

        $mockTitleService = $this->createMock(ThothTitleService::class);
        $mockTitleService->expects($this->never())->method('updateByPublication');
        $mockAbstractService = $this->createMock(ThothAbstractService::class);
        $mockAbstractService->expects($this->never())->method('updateByPublication');
        $mockFrontcoverService = $this->createMock(ThothFrontcoverService::class);
        $mockFrontcoverService->expects($this->once())
            ->method('sync')
            ->willReturn(null);

        $publication = $this->createMock(\APP\publication\Publication::class);
        $service = new ThothBookService(
            $mockFactory,
            $mockRepository,
            $this->createMock(ThothPublicationService::class),
            $mockTitleService,
            $mockAbstractService,
            $this->createMock(\APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource::class),
            $mockFrontcoverService
        );

        $service->update($publication, '9f65f147-1d9d-4dd1-9f78-89b58d088a2c');
    }

    public function testUpdateIncludesTitlesAndAbstractsWhenRequested(): void
    {
        $oldThothBook = new class () {
            public function toArray(): array
            {
                return [
                    'workId' => 'work-id',
                    'titles' => [['titleId' => 'title-id']],
                    'abstracts' => [['abstractId' => 'abstract-id']],
                ];
            }
        };
        $newThothBook = new ThothWork();
        $publication = $this->createMock(\APP\publication\Publication::class);
        $publication->method('getData')
            ->with('locale')
            ->willReturn('en_US');

        $mockFactory = $this->createMock(ThothBookFactory::class);
        $mockFactory->method('createFromPublication')->willReturn($newThothBook);
        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->createMock(ThothClient::class)])
            ->onlyMethods(['get', 'new', 'edit'])
            ->getMock();
        $mockRepository->method('get')->willReturn($oldThothBook);
        $mockRepository->method('new')->willReturn($newThothBook);

        $mockTitleService = $this->createMock(ThothTitleService::class);
        $mockTitleService->expects($this->once())
            ->method('updateByPublication')
            ->with($publication, 'work-id', [['titleId' => 'title-id']], 'en_US');
        $mockAbstractService = $this->createMock(ThothAbstractService::class);
        $mockAbstractService->expects($this->once())
            ->method('updateByPublication')
            ->with($publication, 'work-id', [['abstractId' => 'abstract-id']], 'en_US');

        $service = new ThothBookService(
            $mockFactory,
            $mockRepository,
            $this->createMock(ThothPublicationService::class),
            $mockTitleService,
            $mockAbstractService,
            $this->createMock(\APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource::class)
        );

        $service->update($publication, 'work-id', true);
    }

    public function testDoiExistsBookValidationFails()
    {
        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->onlyMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->willReturn(new ThothWork([
                'doi' => 'https://doi.org/10.12345/10101010'
            ]));

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['getByDoi'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('getByDoi')
            ->willReturn(new ThothWork());

        $mockPublication = $this->getMockBuilder(\APP\publication\Publication::class)->getMock();

        $service = new ThothBookService(
            $mockFactory,
            $mockRepository,
            $this->createMock(ThothPublicationService::class),
            $this->createMock(ThothTitleService::class),
            $this->createMock(ThothAbstractService::class),
            $this->createMock(\APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource::class)
        );
        $errors = $service->validate($mockPublication);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.doiExists##',
        ], $errors);
    }

    public function testLandingPageExistsBookValidationFails()
    {
        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->onlyMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->willReturn(new ThothWork([
                'landingPage' => 'http://www.publicknowledge.omp/index.php/publicknowledge/catalog/book/14'
            ]));

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['find'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('find')
            ->willReturn(new ThothWork([
                'landingPage' => 'http://www.publicknowledge.omp/index.php/publicknowledge/catalog/book/14'
            ]));

        $mockPublication = $this->getMockBuilder(\APP\publication\Publication::class)->getMock();

        $service = new ThothBookService(
            $mockFactory,
            $mockRepository,
            $this->createMock(ThothPublicationService::class),
            $this->createMock(ThothTitleService::class),
            $this->createMock(ThothAbstractService::class),
            $this->createMock(\APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource::class)
        );
        $errors = $service->validate($mockPublication);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.landingPageExists##',
        ], $errors);
    }
}
