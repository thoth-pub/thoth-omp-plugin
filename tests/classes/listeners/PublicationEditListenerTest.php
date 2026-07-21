<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.listeners.PublicationEditListener');

class PublicationEditListenerTest extends PKPTestCase
{
    public function testDoiAssignmentIsSynchronizedWithoutSuccessNotification()
    {
        [$listener, $bookService, $notification] = $this->createListener();

        $listener->updateThothBook('Publication::edit', [
            $this->createPublication(),
            null,
            ['doiId' => 11],
            new stdClass(),
        ]);

        $this->assertSame(1, $bookService->updates);
        $this->assertFalse($bookService->includedTitlesAndAbstracts);
        $this->assertSame(0, $notification->successes);
    }

    public function testMetadataEditIsSynchronizedWithSuccessNotification()
    {
        [$listener, $bookService, $notification] = $this->createListener();

        $listener->updateThothBook('Publication::edit', [
            $this->createPublication(),
            null,
            ['title' => ['en' => 'Updated title']],
            new stdClass(),
        ]);

        $this->assertSame(1, $bookService->updates);
        $this->assertTrue($bookService->includedTitlesAndAbstracts);
        $this->assertSame(1, $notification->successes);
    }

    public function testCatalogEntryEditOnlySynchronizesWorkMetadata()
    {
        [$listener, $bookService] = $this->createListener();

        $listener->updateThothBook('Publication::edit', [
            $this->createPublication(),
            null,
            ['place' => 'Manaus'],
            new stdClass(),
        ]);

        $this->assertSame(1, $bookService->updates);
        $this->assertFalse($bookService->includedTitlesAndAbstracts);
    }

    public function testUnsupportedFrontcoverShowsWarningAndSuccess()
    {
        $warning = 'plugins.generic.thoth.frontcover.unsupportedFormat';
        [$listener, $bookService, $notification] = $this->createListener($warning);

        $listener->updateThothBook('Publication::edit', [
            $this->createPublication(),
            null,
            ['thothUploadFrontcover' => true],
            new stdClass(),
        ]);

        $this->assertSame(1, $bookService->updates);
        $this->assertSame(1, $notification->successes);
        $this->assertSame([$warning], $notification->warnings);
    }

    private function createListener($warning = null)
    {
        $submission = new class () {
            public function getData($key)
            {
                return $key === 'thothWorkId' ? 'work-id' : null;
            }
        };
        $submissionService = new class ($submission) {
            private $submission;

            public function __construct($submission)
            {
                $this->submission = $submission;
            }

            public function get($submissionId)
            {
                return $this->submission;
            }
        };
        $bookService = new class ($warning) {
            public $updates = 0;
            public $includedTitlesAndAbstracts = false;
            private $warning;

            public function __construct($warning)
            {
                $this->warning = $warning;
            }

            public function update($publication, $workId, $includeTitlesAndAbstracts = false)
            {
                $this->updates++;
                $this->includedTitlesAndAbstracts = $includeTitlesAndAbstracts;
                return $this->warning;
            }
        };
        $notification = new class () {
            public $successes = 0;
            public $warnings = [];

            public function notifySuccess($request, $submission)
            {
                $this->successes++;
            }

            public function notifyError($request, $submission, $error)
            {
            }

            public function notifyWarning($request, $submission, $messageKey)
            {
                $this->warnings[] = $messageKey;
            }
        };

        return [
            new PublicationEditListener($submissionService, $bookService, $notification),
            $bookService,
            $notification,
        ];
    }

    private function createPublication()
    {
        return new class () {
            public function getData($key)
            {
                return $key === 'submissionId' ? 13 : null;
            }
        };
    }
}
