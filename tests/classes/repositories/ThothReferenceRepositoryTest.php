<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothReferenceRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothReferenceRepositoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothReferenceRepository
 *
 * @brief Test class for the ThothReferenceRepository class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Reference as ThothReference;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothReferenceRepository');

class ThothReferenceRepositoryTest extends PKPTestCase
{
    public function testNewThothReference()
    {
        $data = [
            'workId' => '6569997e-ac11-4070-994e-3393641f12a8',
            'referenceOrdinal' => 1,
            'unstructuredCitation' => 'Roe, Richard. (2019). A reference used in my book. University of Harvard.'
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothReferenceRepository($mockThothClient);

        $thothReference = $repository->new($data);

        $this->assertInstanceOf(ThothReference::class, $thothReference);
        $this->assertSame($data, $thothReference->getAllData());
    }

    public function testGetReference()
    {
        $expectedThothReference = new ThothReference([
            'referenceId' => '071ace7c-b65b-4bb8-b883-fb2d695d1ad9',
            'workId' => '6569997e-ac11-4070-994e-3393641f12a8',
            'referenceOrdinal' => 1,
            'unstructuredCitation' => 'Roe, Richard. (2019). A reference used in my book. University of Harvard.'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['reference'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('reference')
            ->will($this->returnValue($expectedThothReference));

        $repository = new ThothReferenceRepository($mockThothClient);

        $thothReference = $repository->get('071ace7c-b65b-4bb8-b883-fb2d695d1ad9');

        $this->assertEquals($expectedThothReference, $thothReference);
    }

    public function testAddReference()
    {
        $thothReference = new ThothReference([
            'workId' => '6569997e-ac11-4070-994e-3393641f12a8',
            'referenceOrdinal' => 1,
            'unstructuredCitation' => 'Roe, Richard. (2019). A reference used in my book. University of Harvard.'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createReference'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createReference')
            ->will($this->returnValue('730cf906-472c-4aee-9ebd-67b41e590161'));

        $repository = new ThothReferenceRepository($mockThothClient);

        $thothReferenceId = $repository->add($thothReference);

        $this->assertEquals('730cf906-472c-4aee-9ebd-67b41e590161', $thothReferenceId);
    }

    public function testEditReference()
    {
        $thothPatchReference = new ThothReference([
            'referenceId' => '071ace7c-b65b-4bb8-b883-fb2d695d1ad9',
            'workId' => '6569997e-ac11-4070-994e-3393641f12a8',
            'referenceOrdinal' => 1,
            'unstructuredCitation' => 'Doe, John. (2022). A reference used in my book. Harvard University.'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['updateReference'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updateReference')
            ->will($this->returnValue('071ace7c-b65b-4bb8-b883-fb2d695d1ad9'));

        $repository = new ThothReferenceRepository($mockThothClient);

        $thothReferenceId = $repository->edit($thothPatchReference);

        $this->assertEquals('071ace7c-b65b-4bb8-b883-fb2d695d1ad9', $thothReferenceId);
    }

    public function testDeleteReference()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['deleteReference'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deleteReference')
            ->will($this->returnValue('45896198-2823-4260-95b9-1ff5f4898b7c'));

        $repository = new ThothReferenceRepository($mockThothClient);

        $thothReferenceId = $repository->delete('45896198-2823-4260-95b9-1ff5f4898b7c');

        $this->assertEquals('45896198-2823-4260-95b9-1ff5f4898b7c', $thothReferenceId);
    }
}
