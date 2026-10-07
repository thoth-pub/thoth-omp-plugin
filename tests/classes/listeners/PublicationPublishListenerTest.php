<?php

/**
 * @file plugins/generic/thoth/tests/classes/listeners/PublicationPublishListenerTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationPublishListenerTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Test class for the PublicationPublishListener class
 */

namespace APP\plugins\generic\thoth\tests\classes\listeners;

use APP\core\Request;
use APP\plugins\generic\thoth\classes\listeners\PublicationPublishListener;
use APP\plugins\generic\thoth\classes\notification\ThothNotification;
use APP\plugins\generic\thoth\classes\services\ThothBookRegistrationResult;
use APP\plugins\generic\thoth\classes\services\ThothBookRegistrationService;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\tests\PKPTestCase;
use RuntimeException;
use ThothApi\GraphQL\Enums\WorkType;

class PublicationPublishListenerTest extends PKPTestCase
{
    public function testDelegatesCompleteRegistrationAndShowsWarnings(): void
    {
        $publication = new Publication();
        $submission = new Submission();
        $service = $this->createMock(ThothBookRegistrationService::class);
        $service->expects($this->once())->method('register')
            ->with($publication, 'imprint', $submission, WorkType::TEXTBOOK)
            ->willReturn(new ThothBookRegistrationResult('work-id', ['warning-one', 'warning-two']));
        $notification = $this->createMock(ThothNotification::class);
        $notification->expects($this->once())->method('notifySuccess');
        $warnings = [];
        $notification->method('notifyWarning')->willReturnCallback(
            function ($request, $submission, $key) use (&$warnings): void {
                $warnings[] = $key;
            }
        );
        $listener = new PublicationPublishListener(fn () => $service, $notification, $this->createRequest());

        self::assertFalse($listener->registerThothBook('Publication::publish', [$publication, null, $submission]));
        self::assertSame(['warning-one', 'warning-two'], $warnings);
    }

    public function testReportsRegistrationFailureWithoutRetryingOrNotifyingSuccess(): void
    {
        $failure = new RuntimeException('Registration failed');
        $service = $this->createMock(ThothBookRegistrationService::class);
        $service->expects($this->once())->method('register')->willThrowException($failure);
        $request = $this->createRequest();
        $submission = new Submission();
        $notification = $this->createMock(ThothNotification::class);
        $notification->expects($this->never())->method('notifySuccess');
        $notification->expects($this->once())->method('notifyError')->with($request, $submission, $failure);
        $listener = new PublicationPublishListener(fn () => $service, $notification, $request);

        self::assertFalse($listener->registerThothBook('Publication::publish', [new Publication(), null, $submission]));
    }

    public function testDoesNotResolveRegistrationServiceWithoutOptIn(): void
    {
        $listener = new PublicationPublishListener(
            function () {
                self::fail('A publication without opt-in must not load the registration service.');
            },
            $this->createMock(ThothNotification::class),
            $this->createRequest(false)
        );
        self::assertFalse($listener->registerThothBook(
            'Publication::publish',
            [new Publication(), null, new Submission()]
        ));
    }

    private function createRequest(bool $confirmation = true): Request
    {
        $request = $this->createMock(Request::class);
        $request->method('getUserVar')->willReturnCallback(fn ($key) => [
            'registerConfirmation' => $confirmation ? 'true' : 'false',
            'thothImprintId' => 'imprint',
            'thothWorkType' => WorkType::TEXTBOOK,
        ][$key] ?? null);
        return $request;
    }
}
