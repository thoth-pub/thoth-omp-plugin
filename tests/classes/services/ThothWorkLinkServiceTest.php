<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

use PKP\tests\PKPTestCase;
use ThothApi\Exception\QueryException;
use ThothApi\GraphQL\Schemas\Work;

import('plugins.generic.thoth.classes.repositories.ThothWorkRepository');
import('plugins.generic.thoth.classes.services.ThothWorkLinkService');

class ThothWorkLinkServiceTest extends PKPTestCase
{
    public function testReturnsStatusWhenWorkExists(): void
    {
        $repository = $this->createMock(ThothWorkRepository::class);
        $repository
            ->method('get')
            ->willReturn(new Work(['workStatus' => 'ACTIVE']));

        $service = new ThothWorkLinkService($repository);

        self::assertSame('ACTIVE', $service->getStatus('work-id'));
    }

    /**
     * @dataProvider missingWorkMessages
     */
    public function testReturnsNullWhenWorkDoesNotExistInThoth(string $message): void
    {
        $repository = $this->createMock(ThothWorkRepository::class);
        $repository
            ->method('get')
            ->willThrowException(new QueryException(['message' => $message], null, null, null, 200));

        $service = new ThothWorkLinkService($repository);

        self::assertNull($service->getStatus('work-id'));
    }

    /**
     * @dataProvider otherQueryExceptions
     */
    public function testRethrowsOtherQueryExceptions(string $message, int $statusCode): void
    {
        $exception = new QueryException(
            ['message' => $message],
            null,
            null,
            null,
            $statusCode
        );
        $repository = $this->createMock(ThothWorkRepository::class);
        $repository->method('get')->willThrowException($exception);

        $service = new ThothWorkLinkService($repository);

        $this->expectExceptionObject($exception);
        $service->getStatus('work-id');
    }

    public static function missingWorkMessages(): array
    {
        return [
            'message without punctuation' => ['No record was found for the given ID'],
            'message returned by Thoth API' => ['No record was found for the given ID.'],
        ];
    }

    public static function otherQueryExceptions(): array
    {
        return [
            'different status code' => ['No record was found for the given ID.', 500],
            'different message' => ['Thoth API unavailable', 200],
        ];
    }
}
