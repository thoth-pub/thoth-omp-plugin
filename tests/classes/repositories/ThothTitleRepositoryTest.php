<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothTitleRepositoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothTitleRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothTitleRepository
 *
 * @brief Test class for the ThothTitleRepository class
 */

namespace APP\plugins\generic\thoth\tests\classes\repositories;

require_once __DIR__ . '/../../../vendor/autoload.php';

use APP\plugins\generic\thoth\classes\repositories\ThothTitleRepository;
use Mockery;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\MarkupFormat;
use ThothApi\GraphQL\Inputs\PatchTitle as ThothTitle;

class ThothTitleRepositoryTest extends PKPTestCase
{
    public function testNewThothTitle()
    {
        $data = [
            'workId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'fullTitle' => 'My book title: My subtitle',
            'title' => 'My book title',
            'subtitle' => 'My subtitle',
            'canonical' => true,
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothTitleRepository($mockThothClient);

        $thothTitle = $repository->new($data);

        $this->assertInstanceOf(ThothTitle::class, $thothTitle);
        $this->assertSame($data, $thothTitle->getAllData());
    }

    public function testAddTitle()
    {
        $thothTitle = new ThothTitle([
            'workId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'fullTitle' => 'My book title: My subtitle',
            'title' => 'My book title',
            'subtitle' => 'My subtitle',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('createTitle')
            ->zeroOrMoreTimes()
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothTitleRepository($mockThothClient);

        $thothTitleId = $repository->add($thothTitle);

        $this->assertEquals('0ee25017-980c-44ab-a18b-164b1bd31b8d', $thothTitleId);
    }

    public function testAddPlainTextTitleUsesPlainTextMarkupFormat(): void
    {
        $thothTitle = new ThothTitle([
            'workId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'fullTitle' => 'My book title: My subtitle',
            'title' => 'My book title',
            'subtitle' => 'My subtitle',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('createTitle')
            ->once()
            ->with(MarkupFormat::PLAIN_TEXT, $thothTitle)
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothTitleRepository($mockThothClient);

        $this->assertSame('0ee25017-980c-44ab-a18b-164b1bd31b8d', $repository->add($thothTitle));
    }

    public function testAddHtmlTitleUsesHtmlMarkupFormat(): void
    {
        $thothTitle = new ThothTitle([
            'workId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'fullTitle' => '<em>My book title</em>: My subtitle',
            'title' => '<em>My book title</em>',
            'subtitle' => 'My subtitle',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('createTitle')
            ->once()
            ->with(MarkupFormat::HTML, $thothTitle)
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothTitleRepository($mockThothClient);

        $this->assertSame('0ee25017-980c-44ab-a18b-164b1bd31b8d', $repository->add($thothTitle));
    }

    public function testEditTitle()
    {
        $thothPatchTitle = new ThothTitle([
            'titleId' => '0ee25017-980c-44ab-a18b-164b1bd31b8d',
            'workId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'fullTitle' => 'My updated title: My updated subtitle',
            'title' => 'My updated title',
            'subtitle' => 'My updated subtitle',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('updateTitle')
            ->zeroOrMoreTimes()
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothTitleRepository($mockThothClient);

        $thothTitleId = $repository->edit($thothPatchTitle);

        $this->assertEquals('0ee25017-980c-44ab-a18b-164b1bd31b8d', $thothTitleId);
    }

    public function testEditPlainTextTitleUsesPlainTextMarkupFormat(): void
    {
        $thothPatchTitle = new ThothTitle([
            'titleId' => '0ee25017-980c-44ab-a18b-164b1bd31b8d',
            'workId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'fullTitle' => 'My updated title: My updated subtitle',
            'title' => 'My updated title',
            'subtitle' => 'My updated subtitle',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('updateTitle')
            ->once()
            ->with(MarkupFormat::PLAIN_TEXT, $thothPatchTitle)
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothTitleRepository($mockThothClient);

        $this->assertSame('0ee25017-980c-44ab-a18b-164b1bd31b8d', $repository->edit($thothPatchTitle));
    }

    public function testEditHtmlTitleUsesHtmlMarkupFormat(): void
    {
        $thothPatchTitle = new ThothTitle([
            'titleId' => '0ee25017-980c-44ab-a18b-164b1bd31b8d',
            'workId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'fullTitle' => '<em>My updated title</em>: My updated subtitle',
            'title' => '<em>My updated title</em>',
            'subtitle' => 'My updated subtitle',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('updateTitle')
            ->once()
            ->with(MarkupFormat::HTML, $thothPatchTitle)
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothTitleRepository($mockThothClient);

        $this->assertSame('0ee25017-980c-44ab-a18b-164b1bd31b8d', $repository->edit($thothPatchTitle));
    }

    public function testDeleteTitle()
    {
        $mockThothClient = Mockery::mock(ThothClient::class);

        $mockThothClient->shouldReceive('deleteTitle')
            ->zeroOrMoreTimes()
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothTitleRepository($mockThothClient);

        $thothTitleId = $repository->delete('0ee25017-980c-44ab-a18b-164b1bd31b8d');

        $this->assertEquals('0ee25017-980c-44ab-a18b-164b1bd31b8d', $thothTitleId);
    }
}
