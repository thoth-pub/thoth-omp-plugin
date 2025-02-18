<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothReferenceServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothReferenceServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothReferenceService
 *
 * @brief Test class for the ThothReferenceService class
 */

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;

import('plugins.generic.thoth.classes.repositories.ThothReferenceRepository');
import('plugins.generic.thoth.classes.services.ThothReferenceService');

class ThothReferenceServiceTest extends PKPTestCase
{
    public function testRegisterReference()
    {
        $mockRepository = $this->getMockBuilder(ThothReferenceRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('d667cd9c-27a8-44f8-b976-a0e867c0f607'));

        $mockCitation = $this->getMockBuilder(Citation::class)
            ->setMethods(['getSequence', 'getRawCitation'])
            ->getMock();
        $mockCitation->expects($this->once())
            ->method('getSequence')
            ->will($this->returnValue(1));
        $mockCitation->expects($this->once())
            ->method('getRawCitation')
            ->will($this->returnValue('Roe, Richard. (2019). A reference used in my book. Harvard University.'));

        $thothWorkId = '5e613aee-c27d-4ac8-b87e-cae5deb11771';

        $service = new ThothReferenceService($mockRepository);
        $thothReferenceId = $service->register($mockCitation, $thothWorkId);

        $this->assertSame('d667cd9c-27a8-44f8-b976-a0e867c0f607', $thothReferenceId);
    }
}
