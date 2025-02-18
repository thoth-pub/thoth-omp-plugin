<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothSubjectRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSubjectRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothSubjectRepository
 *
 * @brief Test class for the ThothSubjectRepository class
 */

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Subject as ThothSubject;

import('plugins.generic.thoth.classes.repositories.ThothSubjectRepository');

class ThothSubjectRepositoryTest extends PKPTestCase
{
    public function testNewThothSubject()
    {
        $data = [
            'workId' => '2e38b2af-90fc-4610-8e57-d365fd8e00e2',
            'subjectType' => ThothSubject::SUBJECT_TYPE_KEYWORD,
            'subjectCode' => 'Psychology',
            'subjectOrdinal' => 1
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothSubjectRepository($mockThothClient);

        $thothSubject = $repository->new($data);

        $this->assertInstanceOf(ThothSubject::class, $thothSubject);
        $this->assertSame($data, $thothSubject->getAllData());
    }

    public function testGetSubject()
    {
        $expectedThothSubject = new ThothSubject([
            'subjectId' => '7250f980-3a2b-4922-b2a9-559c946ffc29',
            'workId' => '2e38b2af-90fc-4610-8e57-d365fd8e00e2',
            'subjectType' => ThothSubject::SUBJECT_TYPE_KEYWORD,
            'subjectCode' => 'Psychology',
            'subjectOrdinal' => 1
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['subject'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('subject')
            ->will($this->returnValue($expectedThothSubject));

        $repository = new ThothSubjectRepository($mockThothClient);

        $thothSubject = $repository->get('7250f980-3a2b-4922-b2a9-559c946ffc29');

        $this->assertEquals($expectedThothSubject, $thothSubject);
    }

    public function testAddSubject()
    {
        $thothSubject = new ThothSubject([
            'workId' => '2e38b2af-90fc-4610-8e57-d365fd8e00e2',
            'subjectType' => ThothSubject::SUBJECT_TYPE_KEYWORD,
            'subjectCode' => 'Psychology',
            'subjectOrdinal' => 1
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createSubject'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createSubject')
            ->will($this->returnValue('bded83ea-19f9-4c6d-a249-682d6c5bad5d'));

        $repository = new ThothSubjectRepository($mockThothClient);

        $thothSubjectId = $repository->add($thothSubject);

        $this->assertEquals('bded83ea-19f9-4c6d-a249-682d6c5bad5d', $thothSubjectId);
    }

    public function testEditSubject()
    {
        $thothPatchSubject = new ThothSubject([
            'subjectId' => '8f9e7255-010c-4c6f-a6df-035a501513a9',
            'workId' => '2e38b2af-90fc-4610-8e57-d365fd8e00e2',
            'subjectType' => ThothSubject::SUBJECT_TYPE_BISAC,
            'subjectCode' => '1D',
            'subjectOrdinal' => 1
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['updateSubject'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updateSubject')
            ->will($this->returnValue('8f9e7255-010c-4c6f-a6df-035a501513a9'));

        $repository = new ThothSubjectRepository($mockThothClient);

        $thothSubjectId = $repository->edit($thothPatchSubject);

        $this->assertEquals('8f9e7255-010c-4c6f-a6df-035a501513a9', $thothSubjectId);
    }

    public function testDeleteSubject()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['deleteSubject'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deleteSubject')
            ->will($this->returnValue('cc51bb07-772c-4f3d-8192-cd0983065a90'));

        $repository = new ThothSubjectRepository($mockThothClient);

        $thothSubjectId = $repository->delete('cc51bb07-772c-4f3d-8192-cd0983065a90');

        $this->assertEquals('cc51bb07-772c-4f3d-8192-cd0983065a90', $thothSubjectId);
    }
}
