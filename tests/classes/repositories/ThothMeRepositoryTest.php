<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothMeRepositoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothMeRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothMeRepository
 *
 * @brief Test class for the ThothMeRepository class
 */

namespace APP\plugins\generic\thoth\tests\classes\repositories;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\repositories\ThothMeRepository;
use Mockery;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Schemas\Me;

class ThothMeRepositoryTest extends PKPTestCase
{
    private const ME_SELECTION = [
        'userId',
        'email',
        'firstName',
        'lastName',
        'isSuperuser',
        'publisherContexts' => [
            'publisher' => ['publisherId', 'publisherName', 'imprints' => ['imprintId', 'imprintName']],
            'permissions' => ['publisherAdmin', 'workLifecycle', 'cdnWrite'],
        ],
    ];

    public function testGetMe()
    {
        $expectedMe = new Me([
            'userId' => 'user-id',
            'email' => 'user@example.com',
            'publisherContexts' => [
                [
                    'publisher' => [
                        'publisherId' => 'c1db6141-7af1-4f6a-97c4-2dc1065281ef',
                        'publisherName' => 'Test Publisher',
                        'imprints' => [
                            [
                                'imprintId' => 'imprint-id',
                                'imprintName' => 'Test Imprint',
                            ],
                        ],
                    ],
                    'permissions' => [
                        'publisherAdmin' => false,
                        'workLifecycle' => true,
                        'cdnWrite' => true,
                    ],
                ],
            ],
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('me')
            ->once()
            ->with(self::ME_SELECTION)
            ->andReturn($expectedMe);
        $repository = new ThothMeRepository($mockThothClient, $this->getContextId());

        $this->assertEquals($expectedMe, $repository->get());
    }

    public function testGetMeUsesCache()
    {
        $expectedMe = new Me([
            'userId' => 'user-id',
            'email' => 'user@example.com',
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('me')
            ->once()
            ->with(self::ME_SELECTION)
            ->andReturn($expectedMe);
        $repository = new ThothMeRepository($mockThothClient, $this->getContextId());

        $this->assertEquals($expectedMe, $repository->get());
        $this->assertEquals($expectedMe, $repository->get());
    }

    public function testGetMeCacheIsScopedByContext()
    {
        $firstMe = new Me([
            'userId' => 'first-user-id',
            'email' => 'first-user@example.com',
        ]);
        $secondMe = new Me([
            'userId' => 'second-user-id',
            'email' => 'second-user@example.com',
        ]);

        $firstThothClient = Mockery::mock(ThothClient::class);
        $firstThothClient->shouldReceive('me')
            ->once()
            ->with(self::ME_SELECTION)
            ->andReturn($firstMe);
        $secondThothClient = Mockery::mock(ThothClient::class);

        $secondThothClient->shouldReceive('me')
            ->once()
            ->with(self::ME_SELECTION)
            ->andReturn($secondMe);
        $firstRepository = new ThothMeRepository($firstThothClient, $this->getContextId());
        $secondRepository = new ThothMeRepository($secondThothClient, $this->getContextId());

        $this->assertEquals($firstMe, $firstRepository->get());
        $this->assertEquals($secondMe, $secondRepository->get());
    }

    private function getContextId(): int
    {
        return random_int(100000, PHP_INT_MAX);
    }
}
