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

require_once __DIR__ . '/../../../vendor/autoload.php';

use APP\plugins\generic\thoth\classes\repositories\ThothAbstractRepository;
use Mockery;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\MarkupFormat;
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

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('createAbstract')
            ->zeroOrMoreTimes()
            ->andReturn('6975a02d-4c2f-49cb-b988-c8cf32db3e0e');
        $repository = new ThothAbstractRepository($mockThothClient);

        $thothAbstractId = $repository->add($thothAbstract);

        $this->assertEquals('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $thothAbstractId);
    }

    public function testAddPlainTextAbstractUsesPlainTextMarkupFormat(): void
    {
        $thothAbstract = new ThothAbstract([
            'workId' => 'bb90ddc8-1cfe-4a7e-9805-640db9ae26fd',
            'localeCode' => 'EN',
            'content' => 'This is my abstract',
            'abstractType' => 'LONG',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('createAbstract')
            ->once()
            ->with(MarkupFormat::PLAIN_TEXT, $thothAbstract)
            ->andReturn('6975a02d-4c2f-49cb-b988-c8cf32db3e0e');
        $repository = new ThothAbstractRepository($mockThothClient);

        $this->assertSame('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $repository->add($thothAbstract));
    }

    public function testAddHtmlAbstractUsesHtmlMarkupFormat(): void
    {
        $thothAbstract = new ThothAbstract([
            'workId' => 'bb90ddc8-1cfe-4a7e-9805-640db9ae26fd',
            'localeCode' => 'EN',
            'content' => '<p>This is my abstract</p>',
            'abstractType' => 'LONG',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('createAbstract')
            ->once()
            ->with(MarkupFormat::HTML, $thothAbstract)
            ->andReturn('6975a02d-4c2f-49cb-b988-c8cf32db3e0e');
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

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('updateAbstract')
            ->zeroOrMoreTimes()
            ->andReturn('6975a02d-4c2f-49cb-b988-c8cf32db3e0e');
        $repository = new ThothAbstractRepository($mockThothClient);

        $thothAbstractId = $repository->edit($thothPatchAbstract);

        $this->assertEquals('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $thothAbstractId);
    }

    public function testEditPlainTextAbstractUsesPlainTextMarkupFormat(): void
    {
        $thothPatchAbstract = new ThothAbstract([
            'abstractId' => '6975a02d-4c2f-49cb-b988-c8cf32db3e0e',
            'workId' => 'bb90ddc8-1cfe-4a7e-9805-640db9ae26fd',
            'localeCode' => 'EN',
            'content' => 'This is my updated abstract',
            'abstractType' => 'LONG',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('updateAbstract')
            ->once()
            ->with(MarkupFormat::PLAIN_TEXT, $thothPatchAbstract)
            ->andReturn('6975a02d-4c2f-49cb-b988-c8cf32db3e0e');
        $repository = new ThothAbstractRepository($mockThothClient);

        $this->assertSame('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $repository->edit($thothPatchAbstract));
    }

    public function testEditHtmlAbstractUsesHtmlMarkupFormat(): void
    {
        $thothPatchAbstract = new ThothAbstract([
            'abstractId' => '6975a02d-4c2f-49cb-b988-c8cf32db3e0e',
            'workId' => 'bb90ddc8-1cfe-4a7e-9805-640db9ae26fd',
            'localeCode' => 'EN',
            'content' => '<p>This is my updated abstract</p>',
            'abstractType' => 'LONG',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('updateAbstract')
            ->once()
            ->with(MarkupFormat::HTML, $thothPatchAbstract)
            ->andReturn('6975a02d-4c2f-49cb-b988-c8cf32db3e0e');
        $repository = new ThothAbstractRepository($mockThothClient);

        $this->assertSame('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $repository->edit($thothPatchAbstract));
    }

    public function testDeleteAbstract()
    {
        $mockThothClient = Mockery::mock(ThothClient::class);

        $mockThothClient->shouldReceive('deleteAbstract')
            ->zeroOrMoreTimes()
            ->andReturn('6975a02d-4c2f-49cb-b988-c8cf32db3e0e');
        $repository = new ThothAbstractRepository($mockThothClient);

        $thothAbstractId = $repository->delete('6975a02d-4c2f-49cb-b988-c8cf32db3e0e');

        $this->assertEquals('6975a02d-4c2f-49cb-b988-c8cf32db3e0e', $thothAbstractId);
    }
}
