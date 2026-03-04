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
 * @see ThothBookService
 *
 * @brief Test class for the ThothBookService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Work as ThothWork;
use APP\plugins\generic\thoth\classes\container\ThothContainer;
use APP\plugins\generic\thoth\classes\factories\ThothBookFactory;
use APP\plugins\generic\thoth\classes\repositories\ThothBookRepository;
use APP\plugins\generic\thoth\classes\services\ThothBookService;
use APP\plugins\generic\thoth\classes\services\ThothContributionService;
use APP\plugins\generic\thoth\classes\services\ThothPublicationService;
use APP\plugins\generic\thoth\classes\services\ThothLanguageService;
use APP\plugins\generic\thoth\classes\services\ThothSubjectService;
use APP\plugins\generic\thoth\classes\services\ThothReferenceService;
use APP\plugins\generic\thoth\classes\services\ThothWorkRelationService;

class ThothBookServiceTest extends PKPTestCase
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
            'languageService' => $container->backup('languageService'),
            'subjectService' => $container->backup('subjectService'),
            'referenceService' => $container->backup('referenceService'),
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

    public function testRegisterBook()
    {
        $container = ThothContainer::getInstance();

        $container->set('client', function () {
            return $this->getMockBuilder(ThothClient::class)->getMock();
        });

        $mockContributionService = $this->createMock(ThothContributionService::class);
        $mockContributionService->method('registerByPublication');
        $container->set('contributionService', fn() => $mockContributionService);

        $mockPublicationService = $this->createMock(ThothPublicationService::class);
        $mockPublicationService->method('registerByPublication');
        $container->set('publicationService', fn() => $mockPublicationService);

        $mockLanguageService = $this->createMock(ThothLanguageService::class);
        $mockLanguageService->method('registerByPublication');
        $container->set('languageService', fn() => $mockLanguageService);

        $mockSubjectService = $this->createMock(ThothSubjectService::class);
        $mockSubjectService->method('registerByPublication');
        $container->set('subjectService', fn() => $mockSubjectService);

        $mockReferenceService = $this->createMock(ThothReferenceService::class);
        $mockReferenceService->method('registerByPublication');
        $container->set('referenceService', fn() => $mockReferenceService);

        $mockWorkRelationService = $this->createMock(ThothWorkRelationService::class);
        $mockWorkRelationService->method('registerByPublication');
        $container->set('workRelationService', fn() => $mockWorkRelationService);

        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->onlyMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->willReturn(new ThothWork());

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->willReturn('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3');

        $mockPublication = $this->getMockBuilder(\APP\publication\Publication::class)
            ->onlyMethods(['getData'])
            ->getMock();
        $mockPublication->expects($this->any())
            ->method('getData')
            ->willReturnMap([
                ['locale', null, 'en_US']
            ]);

        $thothImprintId = 'f740cf4e-16d1-487c-9a92-615882a591e9';

        $service = new ThothBookService($mockFactory, $mockRepository);
        $thothBookId = $service->register($mockPublication, $thothImprintId);

        $this->assertSame('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3', $thothBookId);
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

        $service = new ThothBookService($mockFactory, $mockRepository);
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

        $service = new ThothBookService($mockFactory, $mockRepository);
        $errors = $service->validate($mockPublication);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.landingPageExists##',
        ], $errors);
    }
}
