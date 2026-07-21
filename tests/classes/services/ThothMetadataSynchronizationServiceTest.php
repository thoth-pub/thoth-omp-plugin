<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

import('classes.publication.Publication');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.services.ThothBookService');
import('plugins.generic.thoth.classes.services.ThothMetadataSynchronizationService');

class ThothMetadataSynchronizationServiceTest extends PKPTestCase
{
    public function testSynchronizeUpdatesWorkTitlesAndAbstractsMetadata()
    {
        $publication = $this->createMock(Publication::class);
        $bookService = $this->createMock(ThothBookService::class);
        $bookService->expects($this->once())
            ->method('update')
            ->with($publication, 'work-id', true)
            ->willReturn('warning-key');

        $service = new ThothMetadataSynchronizationService($bookService);

        $this->assertSame('warning-key', $service->synchronize($publication, 'work-id'));
    }
}
