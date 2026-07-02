<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothAccountRepositoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAccountRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothAccountRepository
 *
 * @brief Test class for the ThothAccountRepository class
 */

namespace APP\plugins\generic\thoth\tests\classes\repositories;

use APP\plugins\generic\thoth\classes\repositories\ThothAccountRepository;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Schemas\Me;

class ThothAccountRepositoryTest extends PKPTestCase
{
    private const PROFILE_SELECTION = [
        'userId',
        'email',
        'firstName',
        'lastName',
        'isSuperuser',
        'publisherContexts' => [
            'publisher' => ['publisherId', 'publisherName'],
            'permissions' => ['publisherAdmin', 'workLifecycle', 'cdnWrite'],
        ],
    ];

    public function testGetProfileReturnsNormalizedMeSchema()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->addMethods(['me'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('me')
            ->with(self::PROFILE_SELECTION)
            ->willReturn(
                new Me([
                    'userId' => 'user-id',
                    'email' => 'user@example.com',
                    'publisherContexts' => [
                        [
                            'publisher' => [
                                'publisherId' => 'c1db6141-7af1-4f6a-97c4-2dc1065281ef',
                                'publisherName' => 'Test Publisher',
                            ],
                            'permissions' => [
                                'publisherAdmin' => false,
                                'workLifecycle' => true,
                                'cdnWrite' => true,
                            ],
                        ],
                    ],
                ])
            );

        $repository = new ThothAccountRepository($mockThothClient);

        $this->assertEquals(
            [
                'userId' => 'user-id',
                'email' => 'user@example.com',
                'publisherContexts' => [
                    [
                        'publisher' => [
                            'publisherId' => 'c1db6141-7af1-4f6a-97c4-2dc1065281ef',
                            'publisherName' => 'Test Publisher',
                        ],
                        'permissions' => [
                            'publisherAdmin' => false,
                            'workLifecycle' => true,
                            'cdnWrite' => true,
                        ],
                    ],
                ],
                'linkedPublishers' => [
                    [
                        'publisherId' => 'c1db6141-7af1-4f6a-97c4-2dc1065281ef',
                        'publisherName' => 'Test Publisher',
                    ],
                ],
            ],
            $repository->getProfile()
        );
    }

    public function testHasCdnWritePermission()
    {
        $repository = new ThothAccountRepository(null);

        $this->assertTrue($repository->hasCdnWritePermission([
            'publisherContexts' => [
                [
                    'permissions' => [
                        'cdnWrite' => false,
                    ],
                ],
                [
                    'permissions' => [
                        'cdnWrite' => true,
                    ],
                ],
            ],
        ]));
    }

    public function testHasCdnWritePermissionReturnsFalseWithoutCdnWrite()
    {
        $repository = new ThothAccountRepository(null);

        $this->assertFalse($repository->hasCdnWritePermission([
            'publisherContexts' => [
                [
                    'permissions' => [
                        'cdnWrite' => false,
                    ],
                ],
            ],
        ]));
    }

    public function testGetLinkedPublishers()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->addMethods(['me'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('me')
            ->with(self::PROFILE_SELECTION)
            ->willReturn(
                new Me([
                    'publisherContexts' => [
                        [
                            'permissions' => [
                                'publisherAdmin' => true
                            ],
                        ],
                        [
                            'publisher' => [
                                'publisherId' => 'c1db6141-7af1-4f6a-97c4-2dc1065281ef'
                            ],
                        ]
                    ]
                ])
            );

        $repository = new ThothAccountRepository($mockThothClient);

        $expectedPublishers = [
            ['publisherId' => 'c1db6141-7af1-4f6a-97c4-2dc1065281ef']
        ];
        $publishers = $repository->getLinkedPublishers();

        $this->assertEquals($expectedPublishers, $publishers);
    }

    public function testGetLinkedPublishersUsesConfiguredClient()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['me'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('me')
            ->with(self::PROFILE_SELECTION)
            ->willReturn(
                new Me([
                    'publisherContexts' => [
                        [
                            'publisher' => [
                                'publisherId' => 'c1db6141-7af1-4f6a-97c4-2dc1065281ef',
                                'publisherName' => 'Test Publisher',
                            ],
                            'permissions' => [
                                'publisherAdmin' => true,
                            ],
                        ],
                    ],
                ])
            );

        $repository = new ThothAccountRepository($mockThothClient);
        $expectedPublishers = [
            [
                'publisherId' => 'c1db6141-7af1-4f6a-97c4-2dc1065281ef',
                'publisherName' => 'Test Publisher',
            ],
        ];

        $this->assertEquals($expectedPublishers, $repository->getLinkedPublishers());
    }
}
