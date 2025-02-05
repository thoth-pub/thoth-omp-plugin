<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothLanguageServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLanguageServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothLanguageService
 *
 * @brief Test class for the ThothLanguageService class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Language as ThothLanguage;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothLanguageRepository');
import('plugins.generic.thoth.classes.services.ThothLanguageService');

class ThothLanguageServiceTest extends PKPTestCase
{
    public function testRegisterLanguage()
    {
        $mockRepository = $this->getMockBuilder(ThothLanguageRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('d3ddc7b3-d5f3-4394-9c34-320cd222a497'));

        $locale = 'en_US';
        $thothWorkId = 'fdd9321f-84e3-4d19-a914-24289e8aec09';

        $service = new ThothLanguageService($mockRepository);
        $thothLanguageId = $service->register($locale, $thothWorkId);

        $this->assertSame('d3ddc7b3-d5f3-4394-9c34-320cd222a497', $thothLanguageId);
    }
}
