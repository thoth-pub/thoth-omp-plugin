<?php

/**
 * @file plugins/generic/thoth/tests/classes/api/ThothEndpointTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothEndpointTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Test class for the ThothEndpoint class
 */

namespace APP\plugins\generic\thoth\tests\classes\api;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\api\ThothEndpoint;
use PKP\core\PKPRequest;
use PKP\plugins\interfaces\HasAuthorizationPolicy;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\tests\PKPTestCase;

class ThothEndpointTest extends PKPTestCase
{
    protected function getMockedRegistryKeys(): array
    {
        return ['request'];
    }

    protected function getMockedContainerKeys(): array
    {
        return [...parent::getMockedContainerKeys(), \APP\submission\Repository::class];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('registrationFailures')]
    public function testRegistrationReportsRemoteCauseAndProtectsInternalDiagnostics(
        \Throwable $failure,
        ?string $expectedCause
    ): void {
        $publication = new \APP\publication\Publication();
        $submission = $this->createMock(\APP\submission\Submission::class);
        $submission->method('getCurrentPublication')->willReturn($publication);
        $submission->method('getId')->willReturn(123);
        $repository = $this->createMock(\APP\submission\Repository::class);
        $repository->method('get')->with(123)->willReturn($submission);
        app()->instance(\APP\submission\Repository::class, $repository);
        $request = $this->createMock(\APP\core\Request::class);
        $request->method('getContext')->willReturn(new \APP\press\Press());
        \PKP\core\Registry::set('request', $request);
        $input = \Illuminate\Http\Request::create('/123/register', 'PUT', [
            'thothImprintId' => 'imprint',
            'thothWorkType' => 'TEXTBOOK',
        ]);
        $route = new \Illuminate\Routing\Route('PUT', '{submissionId}/register', fn () => null);
        $route->bind($input);
        $input->setRouteResolver(fn () => $route);
        $bookService = $this->createMock(\APP\plugins\generic\thoth\classes\services\ThothBookService::class);
        $bookService->method('validate')->willReturn([]);
        $registrationService = $this->createMock(
            \APP\plugins\generic\thoth\classes\services\ThothBookRegistrationService::class
        );
        $registrationService->expects($this->once())->method('register')
            ->with($publication, 'imprint', $submission, 'TEXTBOOK')->willThrowException($failure);
        $notification = $this->createMock(\APP\plugins\generic\thoth\classes\notification\ThothNotification::class);
        $notification->expects($this->once())->method('notifyError')->with($request, $submission, $failure);
        $unused = function () {
            self::fail('Unrelated services must not be resolved on registration failure.');
        };
        $endpoint = new ThothEndpoint(
            fn () => $bookService,
            fn () => $registrationService,
            $unused,
            $unused,
            $unused,
            $unused,
            $unused,
            $notification
        );

        $response = $endpoint->register($input);

        self::assertSame($failure instanceof \ThothApi\Exception\QueryException ? 400 : 500, $response->getStatusCode());
        self::assertStringNotContainsString('Private database diagnostic', $response->getContent());
        if ($expectedCause !== null) {
            self::assertStringContainsString($expectedCause, $response->getContent());
        }
    }

    public static function registrationFailures(): array
    {
        return [
            'internal persistence failure' => [new \RuntimeException('Private database diagnostic'), null],
            'remote GraphQL failure' => [new \ThothApi\Exception\QueryException(
                ['message' => 'Permission denied for createWork'],
                'mutation { createWork { workId } }',
                null,
                null,
                403
            ), 'Permission denied for createWork'],
        ];
    }

    public function testEndpointProvidesSubmissionAccessPolicy(): void
    {
        $endpoint = $this->getMockBuilder(ThothEndpoint::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();
        $args = [];

        $policies = $endpoint->getPolicies($this->createMock(PKPRequest::class), $args, []);

        self::assertInstanceOf(HasAuthorizationPolicy::class, $endpoint);
        self::assertCount(1, $policies);
        self::assertInstanceOf(SubmissionAccessPolicy::class, $policies[0]);
    }
}
