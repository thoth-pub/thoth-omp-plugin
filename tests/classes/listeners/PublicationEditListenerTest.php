<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

use PKP\tests\PKPTestCase;

import('plugins.generic.thoth.classes.listeners.PublicationEditListener');

class PublicationEditListenerTest extends PKPTestCase
{
    public function testDoiAssignmentIsSynchronizedWithoutSuccessNotification(): void
    {
        [$listener, $bookService, $notification] = $this->createListener();

        $listener->updateThothBook('Publication::edit', [
            $this->createPublication(),
            null,
            ['doiId' => 11],
            new stdClass(),
        ]);

        $this->assertSame(1, $bookService->updates);
        $this->assertSame(0, $notification->successes);
    }

    public function testMetadataEditIsSynchronizedWithSuccessNotification(): void
    {
        [$listener, $bookService, $notification] = $this->createListener();

        $listener->updateThothBook('Publication::edit', [
            $this->createPublication(),
            null,
            ['title' => ['en' => 'Updated title']],
            new stdClass(),
        ]);

        $this->assertSame(1, $bookService->updates);
        $this->assertSame(1, $notification->successes);
    }

    private function createListener(): array
    {
        $submission = new class () {
            public function getData($key)
            {
                return $key === 'thothWorkId' ? 'work-id' : null;
            }
        };
        $submissionRepository = new class ($submission) {
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
        $bookService = new class () {
            public $updates = 0;

            public function update($publication, $workId): void
            {
                $this->updates++;
            }
        };
        $notification = new class () {
            public $successes = 0;

            public function notifySuccess($request, $submission): void
            {
                $this->successes++;
            }

            public function notifyError($request, $submission, $error): void
            {
            }
        };

        return [
            new PublicationEditListener($submissionRepository, $bookService, $notification),
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
