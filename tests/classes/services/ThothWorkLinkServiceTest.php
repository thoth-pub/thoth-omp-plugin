<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

use ThothApi\Exception\QueryException;
use ThothApi\GraphQL\Schemas\Work;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothWorkRepository');
import('plugins.generic.thoth.classes.services.ThothWorkLinkService');

class ThothWorkLinkServiceTest extends PKPTestCase
{
    public function testReturnsStatusWhenWorkExists()
    {
        $repository = $this->createMock(ThothWorkRepository::class);
        $repository
            ->method('get')
            ->willReturn(new Work(['workStatus' => 'ACTIVE']));

        $service = new ThothWorkLinkService($repository);

        $this->assertSame('ACTIVE', $service->getStatus('work-id'));
    }

    /**
     * @dataProvider missingWorkMessages
     */
    public function testReturnsNullWhenWorkDoesNotExistInThoth($message)
    {
        $repository = $this->createMock(ThothWorkRepository::class);
        $repository
            ->method('get')
            ->willThrowException(new QueryException(['message' => $message], null, null, null, 200));

        $service = new ThothWorkLinkService($repository);

        $this->assertNull($service->getStatus('work-id'));
    }

    /**
     * @dataProvider otherQueryExceptions
     */
    public function testRethrowsOtherQueryExceptions($message, $statusCode)
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

    public static function missingWorkMessages()
    {
        return [
            'message without punctuation' => ['No record was found for the given ID'],
            'message returned by Thoth API' => ['No record was found for the given ID.'],
        ];
    }

    public static function otherQueryExceptions()
    {
        return [
            'different status code' => ['No record was found for the given ID.', 500],
            'different message' => ['Thoth API unavailable', 200],
        ];
    }
}
