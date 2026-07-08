<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothBiographyRepositoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBiographyRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothBiographyRepository
 *
 * @brief Test class for the ThothBiographyRepository class
 */

namespace APP\plugins\generic\thoth\tests\classes\repositories;

require_once __DIR__ . '/../../../vendor/autoload.php';

use APP\plugins\generic\thoth\classes\repositories\ThothBiographyRepository;
use Mockery;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\MarkupFormat;
use ThothApi\GraphQL\Inputs\PatchBiography as ThothBiography;

class ThothBiographyRepositoryTest extends PKPTestCase
{
    public function testNewThothBiography()
    {
        $data = [
            'contributionId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'content' => 'My biography',
            'canonical' => true,
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothBiographyRepository($mockThothClient);

        $thothBiography = $repository->new($data);

        $this->assertInstanceOf(ThothBiography::class, $thothBiography);
        $this->assertSame($data, $thothBiography->getAllData());
    }

    public function testAddBiography()
    {
        $thothBiography = new ThothBiography([
            'contributionId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'content' => 'My biography',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('createBiography')
            ->zeroOrMoreTimes()
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothBiographyRepository($mockThothClient);

        $thothBiographyId = $repository->add($thothBiography);

        $this->assertEquals('0ee25017-980c-44ab-a18b-164b1bd31b8d', $thothBiographyId);
    }

    public function testAddPlainTextBiographyUsesPlainTextMarkupFormat(): void
    {
        $thothBiography = new ThothBiography([
            'contributionId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'content' => 'My biography',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('createBiography')
            ->once()
            ->with(MarkupFormat::PLAIN_TEXT, $thothBiography)
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothBiographyRepository($mockThothClient);

        $this->assertSame('0ee25017-980c-44ab-a18b-164b1bd31b8d', $repository->add($thothBiography));
    }

    public function testAddHtmlBiographyUsesHtmlMarkupFormat(): void
    {
        $thothBiography = new ThothBiography([
            'contributionId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'content' => '<p>My biography</p>',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('createBiography')
            ->once()
            ->with(MarkupFormat::HTML, $thothBiography)
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothBiographyRepository($mockThothClient);

        $this->assertSame('0ee25017-980c-44ab-a18b-164b1bd31b8d', $repository->add($thothBiography));
    }

    public function testEditBiography()
    {
        $thothPatchBiography = new ThothBiography([
            'biographyId' => '0ee25017-980c-44ab-a18b-164b1bd31b8d',
            'contributionId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'content' => 'Updated biography',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('updateBiography')
            ->zeroOrMoreTimes()
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothBiographyRepository($mockThothClient);

        $thothBiographyId = $repository->edit($thothPatchBiography);

        $this->assertEquals('0ee25017-980c-44ab-a18b-164b1bd31b8d', $thothBiographyId);
    }

    public function testEditPlainTextBiographyUsesPlainTextMarkupFormat(): void
    {
        $thothPatchBiography = new ThothBiography([
            'biographyId' => '0ee25017-980c-44ab-a18b-164b1bd31b8d',
            'contributionId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'content' => 'Updated biography',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('updateBiography')
            ->once()
            ->with(MarkupFormat::PLAIN_TEXT, $thothPatchBiography)
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothBiographyRepository($mockThothClient);

        $this->assertSame('0ee25017-980c-44ab-a18b-164b1bd31b8d', $repository->edit($thothPatchBiography));
    }

    public function testEditHtmlBiographyUsesHtmlMarkupFormat(): void
    {
        $thothPatchBiography = new ThothBiography([
            'biographyId' => '0ee25017-980c-44ab-a18b-164b1bd31b8d',
            'contributionId' => 'e4b7d5af-1b5c-47cc-9382-c5b3f67972a8',
            'localeCode' => 'EN',
            'content' => '<p>Updated biography</p>',
            'canonical' => true,
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('updateBiography')
            ->once()
            ->with(MarkupFormat::HTML, $thothPatchBiography)
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothBiographyRepository($mockThothClient);

        $this->assertSame('0ee25017-980c-44ab-a18b-164b1bd31b8d', $repository->edit($thothPatchBiography));
    }

    public function testDeleteBiography()
    {
        $mockThothClient = Mockery::mock(ThothClient::class);

        $mockThothClient->shouldReceive('deleteBiography')
            ->zeroOrMoreTimes()
            ->andReturn('0ee25017-980c-44ab-a18b-164b1bd31b8d');
        $repository = new ThothBiographyRepository($mockThothClient);

        $thothBiographyId = $repository->delete('0ee25017-980c-44ab-a18b-164b1bd31b8d');

        $this->assertEquals('0ee25017-980c-44ab-a18b-164b1bd31b8d', $thothBiographyId);
    }
}
