<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothBookRegistrationServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookRegistrationServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothBookRegistrationService
 *
 * @brief Test class for the ThothBookRegistrationService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

use APP\plugins\generic\thoth\classes\container\ThothContainer;
use APP\plugins\generic\thoth\classes\factories\ThothBookFactory;
use APP\plugins\generic\thoth\classes\repositories\ThothBookRepository;
use APP\plugins\generic\thoth\classes\services\ThothAbstractService;
use APP\plugins\generic\thoth\classes\services\ThothBookRegistrationService;
use APP\plugins\generic\thoth\classes\services\ThothContributionService;
use APP\plugins\generic\thoth\classes\services\ThothLanguageService;
use APP\plugins\generic\thoth\classes\services\ThothPublicationService;
use APP\plugins\generic\thoth\classes\services\ThothReferenceService;
use APP\plugins\generic\thoth\classes\services\ThothSubjectService;
use APP\plugins\generic\thoth\classes\services\ThothTitleService;
use APP\plugins\generic\thoth\classes\services\ThothWorkRelationService;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

class ThothBookRegistrationServiceTest extends PKPTestCase
{
    protected array $backups = [];

    public function setUp(): void
    {
        parent::setUp();
        $container = ThothContainer::getInstance();
        $this->backups = [
            'abstractService' => $container->backup('abstractService'),
            'contributionService' => $container->backup('contributionService'),
            'publicationService' => $container->backup('publicationService'),
            'languageService' => $container->backup('languageService'),
            'subjectService' => $container->backup('subjectService'),
            'referenceService' => $container->backup('referenceService'),
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

    public function testRegisterBookMetadataAndRelations(): void
    {
        $container = ThothContainer::getInstance();

        $mockPublication = $this->getMockBuilder(\APP\publication\Publication::class)
            ->onlyMethods(['getData'])
            ->getMock();
        $mockPublication->method('getData')
            ->with('locale')
            ->willReturn('en_US');

        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->onlyMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->with($mockPublication)
            ->willReturn(new ThothWork());

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf(ThothWork::class))
            ->willReturn('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3');

        $mockAbstractService = $this->createMock(ThothAbstractService::class);
        $mockAbstractService->expects($this->once())
            ->method('registerByPublication')
            ->with($mockPublication, 'd8fa2e63-5513-45e5-84c1-e9c2d89f99d3', 'en_US');
        $container->set('abstractService', fn () => $mockAbstractService);

        $mockContributionService = $this->createMock(ThothContributionService::class);
        $mockContributionService->expects($this->once())
            ->method('registerByPublication')
            ->with($mockPublication);
        $container->set('contributionService', fn () => $mockContributionService);

        $mockPublicationService = $this->createMock(ThothPublicationService::class);
        $mockPublicationService->expects($this->once())
            ->method('registerByPublication')
            ->with($mockPublication);
        $container->set('publicationService', fn () => $mockPublicationService);

        $mockLanguageService = $this->createMock(ThothLanguageService::class);
        $mockLanguageService->expects($this->once())
            ->method('registerByPublication')
            ->with($mockPublication);
        $container->set('languageService', fn () => $mockLanguageService);

        $mockSubjectService = $this->createMock(ThothSubjectService::class);
        $mockSubjectService->expects($this->once())
            ->method('registerByPublication')
            ->with($mockPublication);
        $container->set('subjectService', fn () => $mockSubjectService);

        $mockReferenceService = $this->createMock(ThothReferenceService::class);
        $mockReferenceService->expects($this->once())
            ->method('registerByPublication')
            ->with($mockPublication);
        $container->set('referenceService', fn () => $mockReferenceService);

        $mockTitleService = $this->createMock(ThothTitleService::class);
        $mockTitleService->expects($this->once())
            ->method('registerByPublication')
            ->with($mockPublication, 'd8fa2e63-5513-45e5-84c1-e9c2d89f99d3', 'en_US');
        $container->set('titleService', fn () => $mockTitleService);

        $mockWorkRelationService = $this->createMock(ThothWorkRelationService::class);
        $mockWorkRelationService->expects($this->once())
            ->method('registerByPublication')
            ->with($mockPublication, 'f740cf4e-16d1-487c-9a92-615882a591e9');
        $container->set('workRelationService', fn () => $mockWorkRelationService);

        $service = new ThothBookRegistrationService($mockFactory, $mockRepository);

        $thothBookId = $service->register($mockPublication, 'f740cf4e-16d1-487c-9a92-615882a591e9');

        $this->assertSame('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3', $thothBookId);
    }
}
