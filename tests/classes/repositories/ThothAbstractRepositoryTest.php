<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothAbstractRepositoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAbstractRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothAbstractRepository
 *
 * @brief Test class for the ThothAbstractRepository class
 */

namespace APP\plugins\generic\thoth\tests\classes\repositories;

use APP\plugins\generic\thoth\classes\repositories\ThothAbstractRepository;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Inputs\PatchAbstract as ThothAbstract;

class ThothAbstractRepositoryTest extends PKPTestCase
{
    public function testNewThothAbstract()
    {
        $data = [
            'workId' => 'bb90ddc8-1cfe-4a7e-9805-640db9ae26fd',
            'localeCode' => 'EN',
            'content' => 'This is my abstract',
            'abstractType' => 'LONG',
            'canonical' => true,
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothAbstractRepository($mockThothClient);

        $thothAbstract = $repository->new($data);

        $this->assertInstanceOf(ThothAbstract::class, $thothAbstract);
        $this->assertSame($data, $thothAbstract->getAllData());
    }

    public function testAddAbstract()
    {
        $thothAbstract = new ThothAbstract([
            'workId' => 'bb90ddc8-1cfe-4a7e-9805-640db9ae26fd',
            'localeCode' => 'EN',
            'content' => 'This is my abstract',
            'abstractType' => 'LONG',
            'canonical' => true,
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->addMethods(['createAbstract'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createAbstract')
            ->willReturn('6975a02d-4c2f-49cb-b988-c8cf32db3e0e');

        $repository = new ThothAbstractRepository($mockThothClient);

        $thothAbstractId = $repository->add($thothAbstract);

        $this->assertEquals('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $thothAbstractId);
    }

    public function testEditAbstract()
    {
        $thothPatchAbstract = new ThothAbstract([
            'abstractId' => '6975a02d-4c2f-49cb-b988-c8cf32db3e0e',
            'workId' => 'bb90ddc8-1cfe-4a7e-9805-640db9ae26fd',
            'localeCode' => 'EN',
            'content' => 'This is my updated abstract',
            'abstractType' => 'LONG',
            'canonical' => true,
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->addMethods(['updateAbstract'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updateAbstract')
            ->willReturn('6975a02d-4c2f-49cb-b988-c8cf32db3e0e');

        $repository = new ThothAbstractRepository($mockThothClient);

        $thothAbstractId = $repository->edit($thothPatchAbstract);

        $this->assertEquals('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $thothAbstractId);
    }

    public function testDeleteAbstract()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->addMethods(['deleteAbstract'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deleteAbstract')
            ->willReturn('6975a02d-4c2f-49cb-b988-c8cf32db3e0e');

        $repository = new ThothAbstractRepository($mockThothClient);

        $thothAbstractId = $repository->delete('6975a02d-4c2f-49cb-b988-c8cf32db3e0e');

        $this->assertEquals('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $thothAbstractId);
    }
}
