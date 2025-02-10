<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothAffiliationRepositoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAffiliationRepositoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothAffiliationRepository
 *
 * @brief Test class for the ThothAffiliationRepository class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Affiliation as ThothAffiliation;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothAffiliationRepository');

class ThothAffiliationRepositoryTest extends PKPTestCase
{
    public function testNewThothAffiliation()
    {
        $data = [
            'affiliationId' => '8b308be4-34e0-4053-b818-0508430c1918',
            'contributionId' => '92d3b32c-7a04-4534-a23f-6047b18b78b5',
            'institutionId' => 'e214357b-a901-43f2-b9ff-d59789f61dc7',
            'affiliationOrdinal' => 1,
            'position' => 'Professor'
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothAffiliationRepository($mockThothClient);

        $thothAffiliation = $repository->new($data);

        $this->assertInstanceOf(ThothAffiliation::class, $thothAffiliation);
        $this->assertSame($data, $thothAffiliation->getAllData());
    }

    public function testGetAffiliation()
    {
        $expectedThothAffiliation = new ThothAffiliation([
            'affiliationId' => '8b308be4-34e0-4053-b818-0508430c1918',
            'contributionId' => '92d3b32c-7a04-4534-a23f-6047b18b78b5',
            'institutionId' => 'e214357b-a901-43f2-b9ff-d59789f61dc7',
            'affiliationOrdinal' => 1,
            'position' => 'Professor'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['affiliation'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('affiliation')
            ->will($this->returnValue($expectedThothAffiliation));

        $repository = new ThothAffiliationRepository($mockThothClient);

        $thothAffiliation = $repository->get('8b308be4-34e0-4053-b818-0508430c1918');

        $this->assertEquals($expectedThothAffiliation, $thothAffiliation);
    }

    public function testAddAffiliation()
    {
        $thothAffiliation = new ThothAffiliation([
            'contributionId' => '92d3b32c-7a04-4534-a23f-6047b18b78b5',
            'institutionId' => 'e214357b-a901-43f2-b9ff-d59789f61dc7',
            'affiliationOrdinal' => 1,
            'position' => 'Professor'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createAffiliation'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createAffiliation')
            ->will($this->returnValue('f68d406c-c50d-49ec-b803-1cfbb5e9d0b1'));

        $repository = new ThothAffiliationRepository($mockThothClient);

        $thothAffiliationId = $repository->add($thothAffiliation);

        $this->assertEquals('f68d406c-c50d-49ec-b803-1cfbb5e9d0b1', $thothAffiliationId);
    }

    public function testEditAffiliation()
    {
        $thothPatchAffiliation = new ThothAffiliation([
            'affiliationId' => 'f68d406c-c50d-49ec-b803-1cfbb5e9d0b1',
            'contributionId' => '92d3b32c-7a04-4534-a23f-6047b18b78b5',
            'institutionId' => 'e214357b-a901-43f2-b9ff-d59789f61dc7',
            'affiliationOrdinal' => 1,
            'position' => 'Research'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['updateAffiliation'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updateAffiliation')
            ->will($this->returnValue('f68d406c-c50d-49ec-b803-1cfbb5e9d0b1'));

        $repository = new ThothAffiliationRepository($mockThothClient);

        $thothAffiliationId = $repository->edit($thothPatchAffiliation);

        $this->assertEquals('f68d406c-c50d-49ec-b803-1cfbb5e9d0b1', $thothAffiliationId);
    }

    public function testDeleteAffiliation()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['deleteAffiliation'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deleteAffiliation')
            ->will($this->returnValue('f68d406c-c50d-49ec-b803-1cfbb5e9d0b1'));

        $repository = new ThothAffiliationRepository($mockThothClient);

        $thothAffiliationId = $repository->delete('f68d406c-c50d-49ec-b803-1cfbb5e9d0b1');

        $this->assertEquals('f68d406c-c50d-49ec-b803-1cfbb5e9d0b1', $thothAffiliationId);
    }
}
