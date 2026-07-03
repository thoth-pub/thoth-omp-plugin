<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothMeServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothMeServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothMeService
 *
 * @brief Test class for the ThothMeService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\repositories\ThothMeRepository;
use APP\plugins\generic\thoth\classes\services\ThothMeService;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Schemas\Me;

class ThothMeServiceTest extends PKPTestCase
{
    public function testGetMeImprints()
    {
        $contextId = 322;

        $mockRepository = $this->getMockBuilder(ThothMeRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock(), $contextId])
            ->onlyMethods(['get'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('get')
            ->willReturn(new Me([
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
            ]));

        $locale = 'en_US';
        $thothWorkId = 'fdd9321f-84e3-4d19-a914-24289e8aec09';

        $service = new ThothMeService($mockRepository);
        $imprints = $service->getImprints($locale, $thothWorkId);

        $this->assertCount(1, $imprints);
        $this->assertSame('imprint-id', $imprints[0]->getImprintId());
        $this->assertSame('Test Imprint', $imprints[0]->getImprintName());
    }

    public function testHasCdnWritePermission()
    {
        $contextId = 322;

        $mockRepository = $this->getMockBuilder(ThothMeRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock(), $contextId])
            ->onlyMethods(['get'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('get')
            ->willReturn(new Me([
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

        $service = new ThothMeService($mockRepository);

        $this->assertTrue($service->hasCdnWritePermission());
    }

    public function testHasCdnWritePermissionReturnsFalseWithoutCdnWrite()
    {
        $contextId = 322;

        $mockRepository = $this->getMockBuilder(ThothMeRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock(), $contextId])
            ->onlyMethods(['get'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('get')
            ->willReturn(new Me([
                'publisherContexts' => [
                    [
                        'permissions' => [
                            'cdnWrite' => false,
                        ],
                    ],
                ],
            ]));

        $service = new ThothMeService($mockRepository);

        $this->assertFalse($service->hasCdnWritePermission());
    }
}
