<?php

/**
 * @file plugins/generic/thoth/tests/thoth/models/ThothSubjectTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSubjectTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothSubject
 *
 * @brief Test class for the ThothSubject class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.models.ThothSubject');

class ThothSubjectTest extends PKPTestCase
{
    public function testGettersAndSetters()
    {
        $subject = new ThothSubject();
        $subject->setId('6a9cdd5a-5877-433e-8063-9af0617eaa17');
        $subject->setWorkId('62933c17-7f40-46af-84ab-b563ac4ac448');
        $subject->setSubjectType(ThothSubject::SUBJECT_TYPE_KEYWORD);
        $subject->setSubjectCode('law');
        $subject->setSubjectOrdinal(1);

        $this->assertEquals('6a9cdd5a-5877-433e-8063-9af0617eaa17', $subject->getId());
        $this->assertEquals('62933c17-7f40-46af-84ab-b563ac4ac448', $subject->getWorkId());
        $this->assertEquals(ThothSubject::SUBJECT_TYPE_KEYWORD, $subject->getSubjectType());
        $this->assertEquals('law', $subject->getSubjectCode());
        $this->assertEquals(1, $subject->getSubjectOrdinal());
    }

    public function testGetSubjectData()
    {
        $subject = new ThothSubject();
        $subject->setId('fb3cdeec-a533-49f2-ac42-7f3860f966f3');
        $subject->setWorkId('7fbd3c3e-1e37-4352-9211-82665cc25ce1');
        $subject->setSubjectType(ThothSubject::SUBJECT_TYPE_KEYWORD);
        $subject->setSubjectCode('conservation');
        $subject->setSubjectOrdinal(5);

        $this->assertEquals([
            'subjectId' => 'fb3cdeec-a533-49f2-ac42-7f3860f966f3',
            'workId' => '7fbd3c3e-1e37-4352-9211-82665cc25ce1',
            'subjectType' => ThothSubject::SUBJECT_TYPE_KEYWORD,
            'subjectCode' => 'conservation',
            'subjectOrdinal' => 5,
        ], $subject->getData());
    }
}
