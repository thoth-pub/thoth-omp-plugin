<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothAbstractRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAbstractRepositoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothAbstractRepository
 *
 * @brief Test class for the ThothAbstractRepository class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\MarkupFormat;
use ThothApi\GraphQL\Inputs\PatchAbstract as ThothAbstract;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothAbstractRepository');

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
            ->setMethods(['createAbstract'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createAbstract')
            ->will($this->returnValue('6975a02d-4c2f-49cb-b988-c8cf32db3e0e'));

        $repository = new ThothAbstractRepository($mockThothClient);

        $thothAbstractId = $repository->add($thothAbstract);

        $this->assertEquals('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $thothAbstractId);
    }

    public function testAddPlainTextAbstractUsesPlainTextMarkupFormat()
    {
        $thothAbstract = new ThothAbstract([
            'workId' => 'bb90ddc8-1cfe-4a7e-9805-640db9ae26fd',
            'localeCode' => 'EN',
            'content' => 'This is my abstract',
            'abstractType' => 'LONG',
            'canonical' => true,
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createAbstract'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('createAbstract')
            ->with(MarkupFormat::PLAIN_TEXT, $thothAbstract)
            ->will($this->returnValue('6975a02d-4c2f-49cb-b988-c8cf32db3e0e'));
        $repository = new ThothAbstractRepository($mockThothClient);

        $this->assertSame('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $repository->add($thothAbstract));
    }

    public function testAddHtmlAbstractUsesHtmlMarkupFormat()
    {
        $thothAbstract = new ThothAbstract([
            'workId' => 'bb90ddc8-1cfe-4a7e-9805-640db9ae26fd',
            'localeCode' => 'EN',
            'content' => '<p>This is my abstract</p>',
            'abstractType' => 'LONG',
            'canonical' => true,
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createAbstract'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('createAbstract')
            ->with(MarkupFormat::HTML, $thothAbstract)
            ->will($this->returnValue('6975a02d-4c2f-49cb-b988-c8cf32db3e0e'));
        $repository = new ThothAbstractRepository($mockThothClient);

        $this->assertSame('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $repository->add($thothAbstract));
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
            ->setMethods(['updateAbstract'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updateAbstract')
            ->will($this->returnValue('6975a02d-4c2f-49cb-b988-c8cf32db3e0e'));

        $repository = new ThothAbstractRepository($mockThothClient);

        $thothAbstractId = $repository->edit($thothPatchAbstract);

        $this->assertEquals('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $thothAbstractId);
    }

    public function testEditPlainTextAbstractUsesPlainTextMarkupFormat()
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
            ->setMethods(['updateAbstract'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('updateAbstract')
            ->with(MarkupFormat::PLAIN_TEXT, $thothPatchAbstract)
            ->will($this->returnValue('6975a02d-4c2f-49cb-b988-c8cf32db3e0e'));
        $repository = new ThothAbstractRepository($mockThothClient);

        $this->assertSame('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $repository->edit($thothPatchAbstract));
    }

    public function testEditHtmlAbstractUsesHtmlMarkupFormat()
    {
        $thothPatchAbstract = new ThothAbstract([
            'abstractId' => '6975a02d-4c2f-49cb-b988-c8cf32db3e0e',
            'workId' => 'bb90ddc8-1cfe-4a7e-9805-640db9ae26fd',
            'localeCode' => 'EN',
            'content' => '<p>This is my updated abstract</p>',
            'abstractType' => 'LONG',
            'canonical' => true,
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['updateAbstract'])
            ->getMock();
        $mockThothClient->expects($this->once())
            ->method('updateAbstract')
            ->with(MarkupFormat::HTML, $thothPatchAbstract)
            ->will($this->returnValue('6975a02d-4c2f-49cb-b988-c8cf32db3e0e'));
        $repository = new ThothAbstractRepository($mockThothClient);

        $this->assertSame('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $repository->edit($thothPatchAbstract));
    }

    public function testDeleteAbstract()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['deleteAbstract'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deleteAbstract')
            ->will($this->returnValue('6975a02d-4c2f-49cb-b988-c8cf32db3e0e'));

        $repository = new ThothAbstractRepository($mockThothClient);

        $thothAbstractId = $repository->delete('6975a02d-4c2f-49cb-b988-c8cf32db3e0e');

        $this->assertEquals('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $thothAbstractId);
    }
}
