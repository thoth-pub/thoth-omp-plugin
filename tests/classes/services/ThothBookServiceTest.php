<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/services/ThothBookServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothBookService
 *
 * @brief Test class for the ThothBookService class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Enums\WorkType;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

import('classes.publication.Publication');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.container.ThothContainer');
import('plugins.generic.thoth.classes.factories.ThothBookFactory');
import('plugins.generic.thoth.classes.repositories.ThothBookRepository');
import('plugins.generic.thoth.classes.services.ThothAbstractService');
import('plugins.generic.thoth.classes.services.ThothBookService');
import('plugins.generic.thoth.classes.services.ThothContributionService');
import('plugins.generic.thoth.classes.services.ThothFrontcoverService');
import('plugins.generic.thoth.classes.services.ThothLanguageService');
import('plugins.generic.thoth.classes.services.ThothPublicationService');
import('plugins.generic.thoth.classes.services.ThothReferenceService');
import('plugins.generic.thoth.classes.services.ThothSubjectService');
import('plugins.generic.thoth.classes.services.ThothTitleService');
import('plugins.generic.thoth.classes.services.ThothWorkRelationService');

class ThothBookServiceTest extends PKPTestCase
{
    protected $backups = [];

    public function setUp(): void
    {
        parent::setUp();
        $container = ThothContainer::getInstance();
        $this->backups = [
            'client' => $container->backup('client'),
            'abstractService' => $container->backup('abstractService'),
            'contributionService' => $container->backup('contributionService'),
            'languageService' => $container->backup('languageService'),
            'publicationService' => $container->backup('publicationService'),
            'referenceService' => $container->backup('referenceService'),
            'subjectService' => $container->backup('subjectService'),
            'titleService' => $container->backup('titleService'),
            'workRelationService' => $container->backup('workRelationService'),
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

    private function createBookService(
        $factory,
        $repository,
        $publicationService = null,
        $titleService = null,
        $abstractService = null,
        $frontcoverService = null
    ) {
        return new ThothBookService(
            $factory,
            $repository,
            $publicationService ?? $this->createMock(ThothPublicationService::class),
            $titleService ?? $this->createMock(ThothTitleService::class),
            $abstractService ?? $this->createMock(ThothAbstractService::class),
            $frontcoverService
        );
    }

    public function testRegisterBook()
    {
        ThothContainer::getInstance()->set('client', function () {
            return $this->getMockBuilder(ThothClient::class)->getMock();
        });

        $mockAbstractService = $this->createMock(ThothAbstractService::class);
        $mockAbstractService->expects($this->never())->method('registerByPublication');
        ThothContainer::getInstance()->set('abstractService', fn () => $mockAbstractService);

        $mockContributionService = $this->createMock(ThothContributionService::class);
        $mockContributionService->method('registerByPublication');
        ThothContainer::getInstance()->set('contributionService', fn () => $mockContributionService);

        $mockPublicationService = $this->createMock(ThothPublicationService::class);
        $mockPublicationService->method('registerByPublication');
        ThothContainer::getInstance()->set('publicationService', fn () => $mockPublicationService);

        $mockLanguageService = $this->createMock(ThothLanguageService::class);
        $mockLanguageService->method('registerByPublication');
        ThothContainer::getInstance()->set('languageService', fn () => $mockLanguageService);

        $mockSubjectService = $this->createMock(ThothSubjectService::class);
        $mockSubjectService->method('registerByPublication');
        ThothContainer::getInstance()->set('subjectService', fn () => $mockSubjectService);

        $mockReferenceService = $this->createMock(ThothReferenceService::class);
        $mockReferenceService->method('registerByPublication');
        ThothContainer::getInstance()->set('referenceService', fn () => $mockReferenceService);

        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->setMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->will($this->returnValue(new ThothWork()));

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3'));

        $mockTitleService = $this->createMock(ThothTitleService::class);
        $mockTitleService->expects($this->never())->method('registerByPublication');
        ThothContainer::getInstance()->set('titleService', fn () => $mockTitleService);

        $mockWorkRelationService = $this->createMock(ThothWorkRelationService::class);
        $mockWorkRelationService->method('registerByPublication');
        ThothContainer::getInstance()->set('workRelationService', fn () => $mockWorkRelationService);

        $mockPublication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData'])
            ->getMock();
        $mockPublication->method('getData')->will($this->returnValueMap([
            ['locale', null, 'en_US'],
            ['title', null, [
                'en_US' => 'My book title',
                'pt_BR' => 'Meu titulo',
            ]],
            ['subtitle', null, [
                'en_US' => 'My book subtitle',
                'pt_BR' => 'Meu subtitulo',
            ]],
            ['abstract', null, [
                'en_US' => 'This is my book abstract',
                'pt_BR' => 'Este e meu resumo',
            ]],
        ]));

        $thothImprintId = 'f740cf4e-16d1-487c-9a92-615882a591e9';
        $mockFrontcoverService = $this->createMock(ThothFrontcoverService::class);
        $mockFrontcoverService->expects($this->once())
            ->method('sync')
            ->with($mockPublication, 'd8fa2e63-5513-45e5-84c1-e9c2d89f99d3');

        $service = $this->createBookService(
            $mockFactory,
            $mockRepository,
            $this->createMock(ThothPublicationService::class),
            $this->createMock(ThothTitleService::class),
            $this->createMock(ThothAbstractService::class),
            $mockFrontcoverService
        );
        $thothBookId = $service->register($mockPublication, $thothImprintId);

        $this->assertSame('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3', $thothBookId);
    }

    public function testUpdateOnlySynchronizesWorkMetadata()
    {
        $mockTitleService = $this->createMock(ThothTitleService::class);
        $mockTitleService->expects($this->never())
            ->method('updateByPublication');
        $mockAbstractService = $this->createMock(ThothAbstractService::class);
        $mockAbstractService->expects($this->never())
            ->method('updateByPublication');
        $oldThothBook = new class () {
            public function toArray()
            {
                return [
                    'workId' => '9f65f147-1d9d-4dd1-9f78-89b58d088a2c',
                    'workType' => WorkType::MONOGRAPH,
                    'workStatus' => WorkStatus::ACTIVE,
                    'fullTitle' => 'Old title',
                    'title' => 'Old title',
                    'subtitle' => 'Old subtitle',
                    'titles' => [['titleId' => 'title-id']],
                    'abstracts' => [['abstractId' => 'abstract-id']],
                ];
            }
        };

        $newThothBook = new ThothWork([
            'doi' => 'https://doi.org/10.12345/updated',
        ]);

        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->setMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->willReturn($newThothBook);

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['get', 'new', 'edit'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('get')
            ->with('9f65f147-1d9d-4dd1-9f78-89b58d088a2c')
            ->willReturn($oldThothBook);
        $mockRepository->expects($this->once())
            ->method('new')
            ->with([
                'workId' => '9f65f147-1d9d-4dd1-9f78-89b58d088a2c',
                'workType' => WorkType::MONOGRAPH,
                'workStatus' => WorkStatus::ACTIVE,
                'doi' => 'https://doi.org/10.12345/updated',
            ])
            ->willReturn(new ThothWork());
        $mockRepository->expects($this->once())
            ->method('edit');

        $mockPublication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData'])
            ->getMock();
        $mockPublication->method('getData')->will($this->returnValueMap([
            ['locale', null, 'en_US'],
        ]));

        $service = $this->createBookService(
            $mockFactory,
            $mockRepository,
            null,
            $mockTitleService,
            $mockAbstractService
        );
        $service->update($mockPublication, '9f65f147-1d9d-4dd1-9f78-89b58d088a2c');
    }

    public function testUpdateIncludesTitlesAndAbstractsWhenRequested()
    {
        $oldThothBook = new class () {
            public function toArray()
            {
                return [
                    'workId' => 'work-id',
                    'titles' => [['titleId' => 'title-id']],
                    'abstracts' => [['abstractId' => 'abstract-id']],
                ];
            }
        };
        $newThothBook = new ThothWork();
        $publication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData'])
            ->getMock();
        $publication->method('getData')->with('locale')->willReturn('en_US');

        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->setMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->method('createFromPublication')->willReturn($newThothBook);
        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['get', 'new', 'edit'])
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

        $service = $this->createBookService(
            $mockFactory,
            $mockRepository,
            null,
            $mockTitleService,
            $mockAbstractService
        );

        $service->update($publication, 'work-id', true);
    }

    public function testDoiExistsBookValidationFails()
    {
        $mockPublicationService = $this->createMock(ThothPublicationService::class);
        $mockPublicationService->method('validate')->willReturn([]);

        $mockPublicationFormatDao = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getByPublicationId'])
            ->getMock();
        $mockPublicationFormatDao->method('getByPublicationId')->willReturn([]);
        DAORegistry::registerDAO('PublicationFormatDAO', $mockPublicationFormatDao);

        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->setMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->will($this->returnValue(new ThothWork([
                'doi' => 'https://doi.org/10.12345/10101010'
            ])));

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['getByDoi'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('getByDoi')
            ->will($this->returnValue(new ThothWork()));

        $mockPublication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getId'])
            ->getMock();
        $mockPublication->method('getId')->willReturn(1);

        $service = $this->createBookService($mockFactory, $mockRepository, $mockPublicationService);
        $errors = $service->validate($mockPublication);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.doiExists##',
        ], $errors);
    }

    public function testLandingPageExistsBookValidationFails()
    {
        $mockPublicationService = $this->createMock(ThothPublicationService::class);
        $mockPublicationService->method('validate')->willReturn([]);

        $mockPublicationFormatDao = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getByPublicationId'])
            ->getMock();
        $mockPublicationFormatDao->method('getByPublicationId')->willReturn([]);
        DAORegistry::registerDAO('PublicationFormatDAO', $mockPublicationFormatDao);

        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->setMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->will($this->returnValue(new ThothWork([
                'landingPage' => 'http://www.publicknowledge.omp/index.php/publicknowledge/catalog/book/14'
            ])));

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['find'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('find')
            ->will($this->returnValue(new ThothWork([
                'landingPage' => 'http://www.publicknowledge.omp/index.php/publicknowledge/catalog/book/14'
            ])));

        $mockPublication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getId'])
            ->getMock();
        $mockPublication->method('getId')->willReturn(1);

        $service = $this->createBookService($mockFactory, $mockRepository, $mockPublicationService);
        $errors = $service->validate($mockPublication);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.landingPageExists##',
        ], $errors);
    }

    public function testPublicationFormatsValidationUsesMaterializedResults()
    {
        $mockPublicationService = $this->createMock(ThothPublicationService::class);
        $mockPublicationService->expects($this->once())
            ->method('validate')
            ->with($this->isInstanceOf(stdClass::class))
            ->willReturn(['format-error']);

        $mockPublicationFormats = new class () {
            public function toArray()
            {
                return [(object) ['name' => 'PDF']];
            }
        };

        $mockPublicationFormatDao = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getByPublicationId'])
            ->getMock();
        $mockPublicationFormatDao->expects($this->once())
            ->method('getByPublicationId')
            ->with(1)
            ->willReturn($mockPublicationFormats);
        DAORegistry::registerDAO('PublicationFormatDAO', $mockPublicationFormatDao);

        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->setMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->will($this->returnValue(new ThothWork()));

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->getMock();

        $mockPublication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getId'])
            ->getMock();
        $mockPublication->method('getId')->willReturn(1);

        $service = $this->createBookService($mockFactory, $mockRepository, $mockPublicationService);
        $errors = $service->validate($mockPublication);

        $this->assertEquals(['format-error'], $errors);
    }
}
