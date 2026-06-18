<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothMeRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothMeRepositoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothMeRepository
 *
 * @brief Test class for the ThothMeRepository class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Schemas\Me;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothMeRepository');

class ThothMeRepositoryTest extends PKPTestCase
{
    public function testGetLinkedPublishers()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['me'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('me')
            ->with([
                'publisherContexts' => [
                    'publisher' => ['publisherId', 'publisherName'],
                    'permissions' => ['publisherAdmin', 'workLifecycle', 'cdnWrite'],
                ],
            ])
            ->will($this->returnValue(
                new Me([
                    'publisherContexts' => [
                        [
                            'permissions' => [
                                'publisherAdmin' => true
                            ]
                        ],
                        [
                            'publisher' => [
                                'publisherId' => 'c1db6141-7af1-4f6a-97c4-2dc1065281ef'
                            ]
                        ]
                    ]
                ])
            ));

        $repository = new ThothMeRepository($mockThothClient);

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
            ->setMethods(['me'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('me')
            ->with([
                'publisherContexts' => [
                    'publisher' => ['publisherId', 'publisherName'],
                    'permissions' => ['publisherAdmin', 'workLifecycle', 'cdnWrite'],
                ],
            ])
            ->will($this->returnValue(
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
            ));

        $repository = new ThothMeRepository($mockThothClient);

        $expectedPublishers = [
            [
                'publisherId' => 'c1db6141-7af1-4f6a-97c4-2dc1065281ef',
                'publisherName' => 'Test Publisher',
            ],
        ];

        $this->assertEquals($expectedPublishers, $repository->getLinkedPublishers());
    }
}
