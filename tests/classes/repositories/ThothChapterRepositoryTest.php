<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothChapterRepositoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothChapterRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Test class for the ThothChapterRepository class
 */

namespace APP\plugins\generic\thoth\tests\classes\repositories;

use APP\plugins\generic\thoth\classes\repositories\ThothChapterRepository;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PKP\tests\PKPTestCase;
use ThothApi\Exception\QueryException;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

class ThothChapterRepositoryTest extends PKPTestCase
{
    public function testGetChapterByDoi()
    {
        $expectedThothChapter = new ThothWork([
            'doi' => 'https://doi.org/10.12345/00001010'
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('chapterByDoi')
            ->zeroOrMoreTimes()
            ->andReturn($expectedThothChapter);
        $repository = new ThothChapterRepository($mockThothClient);

        $thothChapter = $repository->getByDoi('https://doi.org/10.12345/00001010');

        $this->assertEquals($expectedThothChapter, $thothChapter);
    }

    #[DataProvider('lookupErrors')]
    public function testDoiLookupOnlyTreatsConfirmedAbsenceAsMissing(string $message, int $status, bool $missing): void
    {
        $exception = new QueryException(['message' => $message], null, null, null, $status);
        $client = Mockery::mock(ThothClient::class);
        $client->shouldReceive('chapterByDoi')->once()->andThrow($exception);
        $repository = new ThothChapterRepository($client);

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

    public function testFindChapter()
    {
        $expectedThothChapter = new ThothWork([
            'landingPage' => 'https://publisher.org/chapters/my_chapter'
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('chapters')
            ->zeroOrMoreTimes()
            ->andReturn([$expectedThothChapter]);
        $repository = new ThothChapterRepository($mockThothClient);

        $thothChapter = $repository->find('https://publisher.org/chapters/my_chapter');

        $this->assertEquals($expectedThothChapter, $thothChapter);
    }
}
