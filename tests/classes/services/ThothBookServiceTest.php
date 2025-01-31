<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothBookServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothBookService
 *
 * @brief Test class for the ThothBookService class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Work as ThothWork;

import('classes.submission.Submission');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothBookFactory');
import('plugins.generic.thoth.classes.repositories.ThothWorkRepository');
import('plugins.generic.thoth.classes.services.ThothBookService');

class ThothBookServiceTest extends PKPTestCase
{
    public function testRegisterBook()
    {
        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->setMethods(['createFromSubmission'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromSubmission')
            ->will($this->returnValue(new ThothWork()));

        $mockRepository = $this->getMockBuilder(ThothWorkRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3'));

        $mockSubmission = $this->getMockBuilder(Submission::class)->getMock();
        $mockRequest = $this->getMockBuilder(PKPRequest::class)->getMock();
        $thothImprintId = 'f740cf4e-16d1-487c-9a92-615882a591e9';

        $service = new ThothBookService($mockFactory, $mockRepository);
        $thothBookId = $service->register($mockSubmission, $mockRequest, $thothImprintId);

        $this->assertSame('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3', $thothBookId);
    }
}
