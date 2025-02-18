<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothSubjectServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSubjectServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothSubjectService
 *
 * @brief Test class for the ThothSubjectService class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Subject as ThothSubject;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothSubjectRepository');
import('plugins.generic.thoth.classes.services.ThothSubjectService');

class ThothSubjectServiceTest extends PKPTestCase
{
    public function testRegisterSubject()
    {
        $mockRepository = $this->getMockBuilder(ThothSubjectRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('ebad8694-0dbe-48cf-a704-5d7e1f54b63d'));

        $keyword = 'Psychology';
        $sequence = 1;
        $thothWorkId = '114b96c3-6a51-45e6-a18a-f925128cb597';

        $service = new ThothSubjectService($mockRepository);
        $thothSubjectId = $service->register($keyword, $sequence, $thothWorkId);

        $this->assertSame('ebad8694-0dbe-48cf-a704-5d7e1f54b63d', $thothSubjectId);
    }
}
