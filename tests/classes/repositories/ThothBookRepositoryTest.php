<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothBookRepositoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Test class for the ThothBookRepository class
 */

namespace APP\plugins\generic\thoth\tests\classes\repositories;

use APP\plugins\generic\thoth\classes\repositories\ThothBookRepository;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PKP\tests\PKPTestCase;
use ThothApi\Exception\QueryException;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

class ThothBookRepositoryTest extends PKPTestCase
{
    public function testGetBookByDoi()
    {
        $expectedThothBook = new ThothWork([
            'doi' => 'https://doi.org/10.12345/10101010'
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('bookByDoi')
            ->zeroOrMoreTimes()
            ->andReturn($expectedThothBook);
        $repository = new ThothBookRepository($mockThothClient);

        $thothBook = $repository->getByDoi('https://doi.org/10.12345/10101010');

        $this->assertEquals($expectedThothBook, $thothBook);
    }

    #[DataProvider('lookupErrors')]
    public function testDoiLookupOnlyTreatsConfirmedAbsenceAsMissing(string $message, int $status, bool $missing): void
    {
        $exception = new QueryException(['message' => $message], null, null, null, $status);
        $client = Mockery::mock(ThothClient::class);
        $client->shouldReceive('bookByDoi')->once()->andThrow($exception);
        $repository = new ThothBookRepository($client);

        if (!$missing) {
            $this->expectExceptionObject($exception);
        }

        $this->assertNull($repository->getByDoi('https://doi.org/10.12345/missing'));
    }

    public static function lookupErrors(): array
    {
        return [
            'missing record' => ['No record was found for the given ID.', 200, true],
            'missing without punctuation' => ['No record was found for the given ID', 200, true],
            'unavailable' => ['Thoth API unavailable', 503, false],
            'authorization' => ['Unauthorized', 200, false],
            'unexpected status' => ['No record was found for the given ID.', 500, false],
        ];
    }

    public function testFindBook()
    {
        $expectedThothBook = new ThothWork([
            'landingPage' => 'https://publisher.org/books/my_book'
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('books')
            ->zeroOrMoreTimes()
            ->andReturn([$expectedThothBook]);
        $repository = new ThothBookRepository($mockThothClient);

        $thothBook = $repository->find('https://publisher.org/books/my_book');

        $this->assertEquals($expectedThothBook, $thothBook);
    }
}
