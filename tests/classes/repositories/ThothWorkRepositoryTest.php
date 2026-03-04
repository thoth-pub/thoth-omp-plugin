<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothWorkRepositoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothWorkRepository
 *
 * @brief Test class for the ThothWorkRepository class
 */

namespace APP\plugins\generic\thoth\tests\classes\repositories;

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Work as ThothWork;
use APP\plugins\generic\thoth\classes\repositories\ThothWorkRepository;

class ThothWorkRepositoryTest extends PKPTestCase
{
    public function testNewThothWork()
    {
        $data = [
            'imprintId' => '5eaef26f-adf6-4d68-b938-d61bdc389ebd',
            'workType' => ThothWork::WORK_TYPE_EDITED_BOOK,
            'workStatus' => ThothWork::WORK_TYPE_EDITED_BOOK,
            'fullTitle' => 'My book title',
            'title' => 'My book title',
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothWorkRepository($mockThothClient);

        $thothWork = $repository->new($data);

        $this->assertInstanceOf(ThothWork::class, $thothWork);
        $this->assertSame($data, $thothWork->getAllData());
    }

    public function testGetWork()
    {
        $expectedThothWork = new ThothWork([
            'workId' => '35a27dc3-8117-4381-9a8f-54ef5def6f0b',
            'imprintId' => '5eaef26f-adf6-4d68-b938-d61bdc389ebd',
            'workType' => ThothWork::WORK_TYPE_EDITED_BOOK,
            'workStatus' => ThothWork::WORK_TYPE_EDITED_BOOK,
            'fullTitle' => 'My book title',
            'title' => 'My book title',
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['work'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('work')
            ->willReturn($expectedThothWork);

        $repository = new ThothWorkRepository($mockThothClient);

        $thothWork = $repository->get('35a27dc3-8117-4381-9a8f-54ef5def6f0b');

        $this->assertEquals($expectedThothWork, $thothWork);
    }

    public function testAddWork()
    {
        $thothWork = new ThothWork([
            'imprintId' => '5eaef26f-adf6-4d68-b938-d61bdc389ebd',
            'workType' => ThothWork::WORK_TYPE_EDITED_BOOK,
            'workStatus' => ThothWork::WORK_TYPE_EDITED_BOOK,
            'fullTitle' => 'My book title',
            'title' => 'My book title',
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['createWork'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createWork')
            ->willReturn('4c64863b-ce51-4cf5-bedf-0dd911147f6d');

        $repository = new ThothWorkRepository($mockThothClient);

        $thothWorkId = $repository->add($thothWork);

        $this->assertEquals('4c64863b-ce51-4cf5-bedf-0dd911147f6d', $thothWorkId);
    }

    public function testEditWork()
    {
        $thothPatchWork = new ThothWork([
            'workId' => '8ea11ca6-a2e2-4da7-8f4e-7738e9dcaac9',
            'imprintId' => '5eaef26f-adf6-4d68-b938-d61bdc389ebd',
            'workType' => ThothWork::WORK_TYPE_EDITED_BOOK,
            'workStatus' => ThothWork::WORK_TYPE_EDITED_BOOK,
            'fullTitle' => 'My edited book title',
            'title' => 'My edited book title',
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['updateWork'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updateWork')
            ->willReturn('8ea11ca6-a2e2-4da7-8f4e-7738e9dcaac9');

        $repository = new ThothWorkRepository($mockThothClient);

        $thothWorkId = $repository->edit($thothPatchWork);

        $this->assertEquals('8ea11ca6-a2e2-4da7-8f4e-7738e9dcaac9', $thothWorkId);
    }

    public function testDeleteWork()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['deleteWork'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deleteWork')
            ->willReturn('8de75f0d-36c3-4ab7-8c84-bc53dfc0c3a4');

        $repository = new ThothWorkRepository($mockThothClient);

        $thothWorkId = $repository->delete('8de75f0d-36c3-4ab7-8c84-bc53dfc0c3a4');

        $this->assertEquals('8de75f0d-36c3-4ab7-8c84-bc53dfc0c3a4', $thothWorkId);
    }
}
