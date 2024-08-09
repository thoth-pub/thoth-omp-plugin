<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothSubjectServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSubjectServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothSubjectService
 *
 * @brief Test class for the ThothSubjectService class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.services.ThothSubjectService');

class ThothSubjectServiceTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->subjectService = new ThothSubjectService();
    }

    protected function tearDown(): void
    {
        unset($this->subjectService);
        parent::tearDown();
    }

    public function testCreateNewThothSubject()
    {
        $expectedThothSubject = new ThothSubject();
        $expectedThothSubject->setId('6a9cdd5a-5877-433e-8063-9af0617eaa17');
        $expectedThothSubject->setWorkId('1ef03055-2890-429a-b870-f9671711bcc4');
        $expectedThothSubject->setSubjectType(ThothSubject::SUBJECT_TYPE_KEYWORD);
        $expectedThothSubject->setSubjectCode('Psychology');
        $expectedThothSubject->setSubjectOrdinal(1);

        $params = [
            'subjectId' => '6a9cdd5a-5877-433e-8063-9af0617eaa17',
            'workId' => '1ef03055-2890-429a-b870-f9671711bcc4',
            'subjectType' => ThothSubject::SUBJECT_TYPE_KEYWORD,
            'subjectCode' => 'Psychology',
            'subjectOrdinal' => 1
        ];

        $thothSubject = $this->subjectService->new($params);

        $this->assertEquals($expectedThothSubject, $thothSubject);
    }

    public function testRegisterKeyword()
    {
        $workId = '1ef03055-2890-429a-b870-f9671711bcc4';

        $expectedThothKeyword = new ThothSubject();
        $expectedThothKeyword->setId('6a9cdd5a-5877-433e-8063-9af0617eaa17');
        $expectedThothKeyword->setWorkId($workId);
        $expectedThothKeyword->setSubjectType(ThothSubject::SUBJECT_TYPE_KEYWORD);
        $expectedThothKeyword->setSubjectCode('Psychology');
        $expectedThothKeyword->setSubjectOrdinal(1);

        $submissionKeyword = 'Psychology';

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods([
                'createSubject',
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createSubject')
            ->will($this->returnValue('6a9cdd5a-5877-433e-8063-9af0617eaa17'));

        $thothKeyword = $this->subjectService->registerKeyword($mockThothClient, $submissionKeyword, $workId);
        $this->assertEquals($expectedThothKeyword, $thothKeyword);
    }
}
