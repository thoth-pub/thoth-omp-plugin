<?php


namespace APP\plugins\generic\thoth\tests\classes\repositories;
/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothLocationRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothLocationRepository
 *
 * @brief Test class for the ThothLocationRepository class
 */

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Location as ThothLocation;
use APP\plugins\generic\thoth\classes\repositories\ThothLocationRepository;

class ThothLocationRepositoryTest extends PKPTestCase
{
    public function testNewThothLocation()
    {
        $data = [
            'landingPage' => 'https://omp.publicknowledgeproject.org/press/catalog/book/12',
            'fullTextUrl' => 'https://omp.publicknowledgeproject.org/press/catalog/book/12/view',
            'locationPlatform' => ThothLocation::LOCATION_PLATFORM_OTHER,
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothLocationRepository($mockThothClient);

        $thothLocation = $repository->new($data);

        $this->assertInstanceOf(ThothLocation::class, $thothLocation);
        $this->assertSame($data, $thothLocation->getAllData());
    }

    public function testGetLocation()
    {
        $expectedThothLocation = new ThothLocation([
            'locationId' => 'e2c57d05-b0e4-460e-a4c5-72c91b383b75',
            'landingPage' => 'https://omp.publicknowledgeproject.org/press/catalog/book/12',
            'fullTextUrl' => 'https://omp.publicknowledgeproject.org/press/catalog/book/12/view',
            'locationPlatform' => ThothLocation::LOCATION_PLATFORM_OTHER,
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['location'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('location')
            ->willReturn($expectedThothLocation);

        $repository = new ThothLocationRepository($mockThothClient);

        $thothLocation = $repository->get('e2c57d05-b0e4-460e-a4c5-72c91b383b75');

        $this->assertEquals($expectedThothLocation, $thothLocation);
    }

    public function testHasCanonicalLocation()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['rawQuery'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('rawQuery')
            ->willReturn([
                'publication' => [
                    'locations' => [
                        [
                            'canonical' => false
                        ],
                        [
                            'canonical' => true
                        ],
                    ]
                ]
            ]);

        $repository = new ThothLocationRepository($mockThothClient);

        $thothPublicationId = 'dd239ee6-90d6-4487-ac7f-43ec18391c48';
        $hasCanonical = $repository->hasCanonical($thothPublicationId);

        $this->assertTrue($hasCanonical);
    }

    public function testAddLocation()
    {
        $thothLocation = new ThothLocation([
            'landingPage' => 'https://omp.publicknowledgeproject.org/press/catalog/book/12',
            'fullTextUrl' => 'https://omp.publicknowledgeproject.org/press/catalog/book/12/view',
            'locationPlatform' => ThothLocation::LOCATION_PLATFORM_OTHER,
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['createLocation'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createLocation')
            ->willReturn('2a3771b7-df83-4346-b43e-c2a3ca03aadf');

        $repository = new ThothLocationRepository($mockThothClient);

        $thothLocationId = $repository->add($thothLocation);

        $this->assertEquals('2a3771b7-df83-4346-b43e-c2a3ca03aadf', $thothLocationId);
    }

    public function testEditLocation()
    {
        $thothPatchLocation = new ThothLocation([
            'locationId' => 'a8a397a0-ee70-466b-948a-41d7b1e3069b',
            'landingPage' => 'https://omp.publicknowledgeproject.org/my_press/catalog/book/12',
            'fullTextUrl' => 'https://omp.publicknowledgeproject.org/my_press/catalog/book/12/view',
            'locationPlatform' => ThothLocation::LOCATION_PLATFORM_OTHER,
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['updateLocation'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updateLocation')
            ->willReturn('a8a397a0-ee70-466b-948a-41d7b1e3069b');

        $repository = new ThothLocationRepository($mockThothClient);

        $thothLocationId = $repository->edit($thothPatchLocation);

        $this->assertEquals('a8a397a0-ee70-466b-948a-41d7b1e3069b', $thothLocationId);
    }

    public function testDeleteLocation()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->onlyMethods(['deleteLocation'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deleteLocation')
            ->willReturn('5a636395-2540-456b-9cfa-f2e0fa20e032');

        $repository = new ThothLocationRepository($mockThothClient);

        $thothLocationId = $repository->delete('5a636395-2540-456b-9cfa-f2e0fa20e032');

        $this->assertEquals('5a636395-2540-456b-9cfa-f2e0fa20e032', $thothLocationId);
    }
}
