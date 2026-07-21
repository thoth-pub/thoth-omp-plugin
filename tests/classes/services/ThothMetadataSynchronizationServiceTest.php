<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\publication\Publication;
use PKP\tests\PKPTestCase;

import('plugins.generic.thoth.classes.services.ThothBookService');
import('plugins.generic.thoth.classes.services.ThothMetadataSynchronizationService');

class ThothMetadataSynchronizationServiceTest extends PKPTestCase
{
    public function testSynchronizeUpdatesWorkMetadata()
    {
        $publication = $this->createMock(Publication::class);
        $bookService = $this->createMock(ThothBookService::class);
        $bookService->expects($this->once())
            ->method('update')
            ->with($publication, 'work-id')
            ->willReturn('warning-key');

        $service = new ThothMetadataSynchronizationService($bookService);

        $this->assertSame('warning-key', $service->synchronize($publication, 'work-id'));
    }
}
