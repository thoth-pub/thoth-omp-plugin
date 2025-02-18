<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothAccountRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
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

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;

import('plugins.generic.thoth.classes.repositories.ThothAccountRepository');

class ThothAccountRepositoryTest extends PKPTestCase
{
    public function testGetLinkedPublishers()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['accountDetails'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('accountDetails')
            ->will($this->returnValue([
                'resourceAccess' => [
                    'linkedPublishers' => [
                        [
                            'publisherId' => 'c1db6141-7af1-4f6a-97c4-2dc1065281ef'
                        ]
                    ]
                ]
            ]));

        $repository = new ThothAccountRepository($mockThothClient);

        $expectedPublishers = [
            ['publisherId' => 'c1db6141-7af1-4f6a-97c4-2dc1065281ef']
        ];
        $publishers = $repository->getLinkedPublishers();

        $this->assertEquals($expectedPublishers, $publishers);
    }
}
