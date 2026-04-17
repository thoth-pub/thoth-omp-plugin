<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothBiographyRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBiographyRepositoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothBiographyRepository
 *
 * @brief Test class for the ThothBiographyRepository class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Biography as ThothBiography;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothBiographyRepository');

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

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createBiography'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createBiography')
            ->will($this->returnValue('0ee25017-980c-44ab-a18b-164b1bd31b8d'));

        $repository = new ThothBiographyRepository($mockThothClient);

        $thothBiographyId = $repository->add($thothBiography);

        $this->assertEquals('0ee25017-980c-44ab-a18b-164b1bd31b8d', $thothBiographyId);
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

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['updateBiography'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updateBiography')
            ->will($this->returnValue('0ee25017-980c-44ab-a18b-164b1bd31b8d'));

        $repository = new ThothBiographyRepository($mockThothClient);

        $thothBiographyId = $repository->edit($thothPatchBiography);

        $this->assertEquals('0ee25017-980c-44ab-a18b-164b1bd31b8d', $thothBiographyId);
    }

    public function testDeleteBiography()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['deleteBiography'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deleteBiography')
            ->will($this->returnValue('0ee25017-980c-44ab-a18b-164b1bd31b8d'));

        $repository = new ThothBiographyRepository($mockThothClient);

        $thothBiographyId = $repository->delete('0ee25017-980c-44ab-a18b-164b1bd31b8d');

        $this->assertEquals('0ee25017-980c-44ab-a18b-164b1bd31b8d', $thothBiographyId);
    }
}
