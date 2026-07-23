<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

import('classes.publication.Publication');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.services.ThothBookService');
import('plugins.generic.thoth.classes.services.ThothContributionService');
import('plugins.generic.thoth.classes.services.ThothLanguageService');
import('plugins.generic.thoth.classes.services.ThothMetadataSynchronizationService');
import('plugins.generic.thoth.classes.services.ThothPublicationService');
import('plugins.generic.thoth.classes.services.ThothReferenceService');
import('plugins.generic.thoth.classes.services.ThothSubjectService');
import('plugins.generic.thoth.classes.services.ThothWorkRelationService');

class ThothMetadataSynchronizationServiceTest extends PKPTestCase
{
    public function testSynchronizeUpdatesImplementedMetadataDomains()
    {
        $publication = $this->createMock(Publication::class);
        $bookService = $this->createMock(ThothBookService::class);
        $bookService->expects($this->once())
            ->method('update')
            ->with($publication, 'work-id', true)
            ->willReturn('warning-key');
        $contributionService = $this->createMock(ThothContributionService::class);
        $contributionService->expects($this->once())
            ->method('synchronizeByPublication')
            ->with($publication, 'work-id');
        $publicationService = $this->createMock(ThothPublicationService::class);
        $publicationService->expects($this->once())
            ->method('synchronizeByPublication')
            ->with($publication, 'work-id')
            ->willReturn(false);
        $languageService = $this->createMock(ThothLanguageService::class);
        $languageService->expects($this->once())
            ->method('synchronizeByPublication')
            ->with($publication, 'work-id');
        $subjectService = $this->createMock(ThothSubjectService::class);
        $subjectService->expects($this->once())
            ->method('synchronizeByPublication')
            ->with($publication, 'work-id');
        $referenceService = $this->createMock(ThothReferenceService::class);
        $referenceService->expects($this->once())
            ->method('synchronizeByPublication')
            ->with($publication, 'work-id');
        $workRelationService = $this->createMock(ThothWorkRelationService::class);
        $workRelationService->expects($this->once())
            ->method('synchronizeByPublication')
            ->with($publication, 'work-id')
            ->willReturn(false);

        $service = new ThothMetadataSynchronizationService(
            $bookService,
            $contributionService,
            $publicationService,
            $languageService,
            $subjectService,
            $referenceService,
            $workRelationService
        );

        $this->assertSame(['warning-key'], $service->synchronize($publication, 'work-id'));
    }

    public function testSynchronizeDoesNotWarnAfterAutomaticDeletions()
    {
        $publication = $this->createMock(Publication::class);
        $bookService = $this->createMock(ThothBookService::class);
        $bookService->method('update')->willReturn(null);
        $contributionService = $this->createMock(ThothContributionService::class);
        $publicationService = $this->createMock(ThothPublicationService::class);
        $publicationService->method('synchronizeByPublication')->willReturn(false);
        $languageService = $this->createMock(ThothLanguageService::class);
        $subjectService = $this->createMock(ThothSubjectService::class);
        $referenceService = $this->createMock(ThothReferenceService::class);
        $workRelationService = $this->createMock(ThothWorkRelationService::class);
        $workRelationService->method('synchronizeByPublication')->willReturn(false);

        $service = new ThothMetadataSynchronizationService(
            $bookService,
            $contributionService,
            $publicationService,
            $languageService,
            $subjectService,
            $referenceService,
            $workRelationService
        );

        $this->assertSame([], $service->synchronize($publication, 'work-id'));
    }

    public function testSynchronizeCombinesBookWarningWithSkippedPublicationDeletionWarning()
    {
        $publication = $this->createMock(Publication::class);
        $bookService = $this->createMock(ThothBookService::class);
        $bookService->method('update')->willReturn('book-warning-key');
        $contributionService = $this->createMock(ThothContributionService::class);
        $publicationService = $this->createMock(ThothPublicationService::class);
        $publicationService->method('synchronizeByPublication')->willReturn(true);
        $languageService = $this->createMock(ThothLanguageService::class);
        $subjectService = $this->createMock(ThothSubjectService::class);
        $referenceService = $this->createMock(ThothReferenceService::class);
        $workRelationService = $this->createMock(ThothWorkRelationService::class);
        $workRelationService->method('synchronizeByPublication')->willReturn(true);

        $service = new ThothMetadataSynchronizationService(
            $bookService,
            $contributionService,
            $publicationService,
            $languageService,
            $subjectService,
            $referenceService,
            $workRelationService
        );

        $this->assertSame([
            'book-warning-key',
            'plugins.generic.thoth.synchronize.activeWorkPublicationDeletionsSkipped',
        ], $service->synchronize($publication, 'work-id'));
    }
}
