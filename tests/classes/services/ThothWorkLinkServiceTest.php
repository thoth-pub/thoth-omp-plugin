<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothWorkLinkServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkLinkServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Test class for the ThothWorkLinkService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

require_once __DIR__ . '/../../../vendor/autoload.php';

use APP\plugins\generic\thoth\classes\repositories\ThothWorkRepository;
use APP\plugins\generic\thoth\classes\services\ThothWorkLinkService;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PKP\tests\PKPTestCase;
use ThothApi\Exception\QueryException;
use ThothApi\GraphQL\Client;
use ThothApi\GraphQL\Schemas\Work;

class ThothWorkLinkServiceTest extends PKPTestCase
{
    public function testReturnsStatusWhenWorkExists(): void
    {
        $client = Mockery::mock(Client::class);
        $repository = new ThothWorkRepository($client);
        $client
            ->shouldReceive('work')->once()
            ->andReturn(new Work(['workStatus' => 'ACTIVE']));

        $service = new ThothWorkLinkService($repository);

        self::assertSame('ACTIVE', $service->getStatus('work-id'));
    }

    #[DataProvider('missingWorkMessages')]
    public function testReturnsNullWhenWorkDoesNotExistInThoth(string $message): void
    {
        $client = Mockery::mock(Client::class);
        $repository = new ThothWorkRepository($client);
        $client
            ->shouldReceive('work')->once()
            ->andThrow(new QueryException(['message' => $message], null, null, null, 200));

        $service = new ThothWorkLinkService($repository);

        self::assertNull($service->getStatus('work-id'));
    }

    #[DataProvider('otherQueryExceptions')]
    public function testRethrowsOtherQueryExceptions(string $message, int $statusCode): void
    {
        $exception = new QueryException(
            ['message' => $message],
            null,
            null,
            null,
            $statusCode
        );
        $client = Mockery::mock(Client::class);
        $repository = new ThothWorkRepository($client);
        $client->shouldReceive('work')->once()->andThrow($exception);

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
