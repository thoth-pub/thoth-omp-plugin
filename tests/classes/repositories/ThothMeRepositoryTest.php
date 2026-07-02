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

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->addMethods(['me'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('me')
            ->with(self::ME_SELECTION)
            ->willReturn($expectedMe);

        $repository = new ThothMeRepository($mockThothClient, $this->getContextId());

        $this->assertEquals($expectedMe, $repository->get());
    }

    public function testGetMeUsesCache()
    {
        $expectedMe = new Me([
            'userId' => 'user-id',
            'email' => 'user@example.com',
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->addMethods(['me'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('me')
            ->with(self::ME_SELECTION)
            ->willReturn($expectedMe);

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

        $firstThothClient = $this->getMockBuilder(ThothClient::class)
            ->addMethods(['me'])
            ->getMock();
        $firstThothClient->expects($this->once())
            ->method('me')
            ->with(self::ME_SELECTION)
            ->willReturn($firstMe);

        $secondThothClient = $this->getMockBuilder(ThothClient::class)
            ->addMethods(['me'])
            ->getMock();
        $secondThothClient->expects($this->once())
            ->method('me')
            ->with(self::ME_SELECTION)
            ->willReturn($secondMe);

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
