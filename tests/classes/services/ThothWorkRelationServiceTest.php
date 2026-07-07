<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/services/ThothWorkRelationServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkRelationServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothWorkRelationService
 *
 * @brief Test class for the ThothWorkRelationService class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Enums\WorkType;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;
use ThothApi\GraphQL\Enums\RelationType;
use ThothApi\GraphQL\Inputs\PatchWorkRelation as ThothWorkRelation;

import('classes.monograph.Chapter');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothChapterFactory');
import('plugins.generic.thoth.classes.repositories.ThothWorkRelationRepository');
import('plugins.generic.thoth.classes.repositories.ThothChapterRepository');
import('plugins.generic.thoth.classes.services.ThothChapterService');
import('plugins.generic.thoth.classes.services.ThothWorkRelationService');

class ThothWorkRelationServiceTest extends PKPTestCase
{
    public function testRegisterWorkRelation()
    {
        $mockChapterService = $this->createMock(ThothChapterService::class);
        $mockChapterService->expects($this->once())
            ->method('register')
            ->will($this->returnValue('dccd9dfd-fee2-4e85-b1f8-0440f9b43ce8'));

        $mockRepository = $this->getMockBuilder(ThothWorkRelationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('91966e15-0203-4eb8-b7e7-02b72c57cedc'));

        $mockChapter = $this->getMockBuilder(Chapter::class)->getMock();
        $thothRelatedWorkId = '813e0519-05ca-455b-b330-af623456dace';
        $thothImprintId = '41b6a2a4-c3e1-4045-882c-c0f31386dee5';

        $service = new ThothWorkRelationService($mockRepository, $mockChapterService);
        $thothWorkRelationId = $service->register($mockChapter, $thothRelatedWorkId, $thothImprintId);

        $this->assertSame('91966e15-0203-4eb8-b7e7-02b72c57cedc', $thothWorkRelationId);
    }
}
