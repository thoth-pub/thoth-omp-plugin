<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
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

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

import('classes.publication.Publication');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothBookFactory');
import('plugins.generic.thoth.classes.repositories.ThothBookRepository');
import('plugins.generic.thoth.classes.services.ThothAbstractService');
import('plugins.generic.thoth.classes.services.ThothBookRegistrationResult');
import('plugins.generic.thoth.classes.services.ThothBookRegistrationService');
import('plugins.generic.thoth.classes.services.ThothContributionService');
import('plugins.generic.thoth.classes.services.ThothFrontcoverService');
import('plugins.generic.thoth.classes.services.ThothLanguageService');
import('plugins.generic.thoth.classes.services.ThothPublicationService');
import('plugins.generic.thoth.classes.services.ThothReferenceService');
import('plugins.generic.thoth.classes.services.ThothSubjectService');
import('plugins.generic.thoth.classes.services.ThothTitleService');
import('plugins.generic.thoth.classes.services.ThothWorkRelationService');

class ThothBookRegistrationServiceTest extends PKPTestCase
{
    public function testRegisterBookMetadataAndRelations()
    {
        $mockPublication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData'])
            ->getMock();
        $mockPublication->expects($this->exactly(2))
            ->method('getData')
            ->with('locale')
            ->willReturn('en_US');

        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->setMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->with($mockPublication)
            ->willReturn(new ThothWork());

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf(ThothWork::class))
            ->willReturn('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3');

        $mockAbstractService = $this->createMock(ThothAbstractService::class);
        $mockAbstractService->expects($this->once())->method('registerByPublication');

        $mockContributionService = $this->createMock(ThothContributionService::class);
        $mockContributionService->expects($this->once())->method('registerByPublication');

        $mockPublicationService = $this->createMock(ThothPublicationService::class);
        $mockPublicationService->expects($this->once())->method('registerByPublication');

        $mockLanguageService = $this->createMock(ThothLanguageService::class);
        $mockLanguageService->expects($this->once())->method('registerByPublication');

        $mockSubjectService = $this->createMock(ThothSubjectService::class);
        $mockSubjectService->expects($this->once())->method('registerByPublication');

        $mockReferenceService = $this->createMock(ThothReferenceService::class);
        $mockReferenceService->expects($this->once())->method('registerByPublication');

        $mockTitleService = $this->createMock(ThothTitleService::class);
        $mockTitleService->expects($this->once())->method('registerByPublication');

        $mockWorkRelationService = $this->createMock(ThothWorkRelationService::class);
        $mockWorkRelationService->expects($this->once())->method('registerByPublication');

        $mockFrontcoverService = $this->createMock(ThothFrontcoverService::class);
        $mockFrontcoverService->expects($this->once())
            ->method('sync')
            ->with($mockPublication, 'd8fa2e63-5513-45e5-84c1-e9c2d89f99d3')
            ->willReturn('plugins.generic.thoth.frontcover.unsupportedFormat');

        $service = new ThothBookRegistrationService(
            $mockFactory,
            $mockRepository,
            $mockAbstractService,
            $mockContributionService,
            $mockLanguageService,
            $mockPublicationService,
            $mockReferenceService,
            $mockSubjectService,
            $mockTitleService,
            $mockWorkRelationService,
            $mockFrontcoverService
        );

        $registrationResult = $service->register($mockPublication, 'f740cf4e-16d1-487c-9a92-615882a591e9');

        $this->assertInstanceOf(ThothBookRegistrationResult::class, $registrationResult);
        $this->assertSame('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3', $registrationResult->getWorkId());
        $this->assertSame(
            'plugins.generic.thoth.frontcover.unsupportedFormat',
            $registrationResult->getWarning()
        );
    }

    public function testSetActiveUsesOnlyTheGivenRegistrationResult()
    {
        $firstBook = new ThothWork();
        $firstBook->setWorkStatus(WorkStatus::ACTIVE);

        $secondBook = new ThothWork();

        $mockPublication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData'])
            ->getMock();
        $mockPublication->method('getData')
            ->with('locale')
            ->willReturn('en_US');

        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->setMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->exactly(2))
            ->method('createFromPublication')
            ->with($mockPublication)
            ->willReturnOnConsecutiveCalls($firstBook, $secondBook);

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add', 'edit'])
            ->getMock();
        $mockRepository->expects($this->exactly(2))
            ->method('add')
            ->with($this->isInstanceOf(ThothWork::class))
            ->willReturnOnConsecutiveCalls(
                'first-work-id',
                'second-work-id'
            );
        $mockRepository->expects($this->never())
            ->method('edit');

        $service = new ThothBookRegistrationService(
            $mockFactory,
            $mockRepository,
            $this->createMock(ThothAbstractService::class),
            $this->createMock(ThothContributionService::class),
            $this->createMock(ThothLanguageService::class),
            $this->createMock(ThothPublicationService::class),
            $this->createMock(ThothReferenceService::class),
            $this->createMock(ThothSubjectService::class),
            $this->createMock(ThothTitleService::class),
            $this->createMock(ThothWorkRelationService::class)
        );

        $service->register($mockPublication, 'f740cf4e-16d1-487c-9a92-615882a591e9');
        $secondRegistrationResult = $service->register($mockPublication, 'f740cf4e-16d1-487c-9a92-615882a591e9');

        $service->setActive($secondRegistrationResult);
    }
}
