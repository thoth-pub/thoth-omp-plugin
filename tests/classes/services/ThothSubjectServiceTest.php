<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothSubjectServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSubjectServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothSubjectService
 *
 * @brief Test class for the ThothSubjectService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

use APP\plugins\generic\thoth\classes\repositories\ThothSubjectRepository;
use APP\plugins\generic\thoth\classes\services\ThothSubjectService;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;

class ThothSubjectServiceTest extends PKPTestCase
{
    public function testRegisterSubject()
    {
        $mockRepository = $this->getMockBuilder(ThothSubjectRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->willReturn('ebad8694-0dbe-48cf-a704-5d7e1f54b63d');

        $keyword = 'Education';
        $sequence = 1;
        $thothWorkId = '114b96c3-6a51-45e6-a18a-f925128cb597';

        $service = new ThothSubjectService($mockRepository);
        $thothSubjectId = $service->register($keyword, $sequence, $thothWorkId);

        $this->assertSame('ebad8694-0dbe-48cf-a704-5d7e1f54b63d', $thothSubjectId);
    }
}
