<?php

namespace APP\plugins\generic\thoth\tests\classes\listeners;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\listeners\PublicationEditListener;
use PKP\tests\PKPTestCase;
use stdClass;

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

    public function testUnsupportedFrontcoverShowsWarningAndSuccess(): void
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

    private function createListener(?string $warning = null): array
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
        $bookService = new class ($warning) {
            public int $updates = 0;
            private ?string $warning;

            public function __construct(?string $warning)
            {
                $this->warning = $warning;
            }

            public function update($publication, $workId): ?string
            {
                $this->updates++;
                return $this->warning;
            }
        };
        $notification = new class () {
            public int $successes = 0;
            public array $warnings = [];

            public function notifySuccess($request, $submission): void
            {
                $this->successes++;
            }

            public function notifyError($request, $submission, $error): void
            {
            }

            public function notifyWarning($request, $submission, $messageKey): void
            {
                $this->warnings[] = $messageKey;
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
