<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\publication\Publication;
use PKP\tests\PKPTestCase;

import('plugins.generic.thoth.classes.services.ThothBookService');
import('plugins.generic.thoth.classes.services.ThothContributionService');
import('plugins.generic.thoth.classes.services.ThothMetadataSynchronizationService');

class ThothMetadataSynchronizationServiceTest extends PKPTestCase
{
    public function testSynchronizeUpdatesWorkTitlesAbstractsAndContributionsMetadata()
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

        $service = new ThothMetadataSynchronizationService($bookService, $contributionService);

        $this->assertSame('warning-key', $service->synchronize($publication, 'work-id'));
    }
}
