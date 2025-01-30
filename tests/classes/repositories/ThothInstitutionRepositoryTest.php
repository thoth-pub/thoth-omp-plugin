<?php

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Institution as ThothInstitution;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.repositories.ThothInstitutionRepository');

class ThothInstitutionRepositoryTest extends PKPTestCase
{
    public function testNewThothInstitution()
    {
        $data = [
            'institutionName' => 'My Institution',
            'institutionDoi' => 'https://doi.org/10.12345/000000001',
            'countryCode' => 'USA',
            'ror' => 'https://ror.org/123abcd45'
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothInstitutionRepository($mockThothClient);

        $thothInstitution = $repository->new($data);
        $this->assertSame($data, $thothInstitution->getAllData());
    }

    public function testGetInstitution()
    {
        $expectedThothInstitution = new ThothInstitution([
            'institutionId' => '8a3a7422-e5fb-4d2d-810d-513987735b4e',
            'institutionName' => 'My Institution',
            'institutionDoi' => 'https://doi.org/10.12345/000000001',
            'countryCode' => 'USA',
            'ror' => 'https://ror.org/123abcd45'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['institution'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('institution')
            ->will($this->returnValue($expectedThothInstitution));

        $repository = new ThothInstitutionRepository($mockThothClient);
        $thothInstitution = $repository->get('8a3a7422-e5fb-4d2d-810d-513987735b4e');

        $this->assertEquals($expectedThothInstitution, $thothInstitution);
    }

    public function testAddInstitution()
    {
        $thothInstitution = new ThothInstitution([
            'institutionName' => 'My Institution',
            'institutionDoi' => 'https://doi.org/10.12345/000000001',
            'countryCode' => 'USA',
            'ror' => 'https://ror.org/123abcd45'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createInstitution'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createInstitution')
            ->will($this->returnValue('4da12af5-7a1d-400a-a6d4-263e7ec05c2d'));

        $repository = new ThothInstitutionRepository($mockThothClient);
        $thothInstitutionId = $repository->add($thothInstitution);

        $this->assertEquals('4da12af5-7a1d-400a-a6d4-263e7ec05c2d', $thothInstitutionId);
    }

    public function testEditInstitution()
    {
        $thothPatchInstitution = new ThothInstitution([
            'institutionId' => '9083a2c0-c86d-4406-806b-b589067b5e27',
            'institutionName' => 'My Edited Institution',
            'institutionDoi' => 'https://doi.org/10.12345/000000001',
            'countryCode' => 'USA',
            'ror' => 'https://ror.org/123abcd45'
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['updateInstitution'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('updateInstitution')
            ->will($this->returnValue('9083a2c0-c86d-4406-806b-b589067b5e27'));

        $repository = new ThothInstitutionRepository($mockThothClient);
        $thothInstitutionId = $repository->edit($thothPatchInstitution);

        $this->assertEquals('9083a2c0-c86d-4406-806b-b589067b5e27', $thothInstitutionId);
    }

    public function testDeleteInstitution()
    {
        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['deleteInstitution'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('deleteInstitution')
            ->will($this->returnValue('bde559b6-ce66-4064-b7b6-8f164bbaa1eb'));

        $repository = new ThothInstitutionRepository($mockThothClient);
        $thothInstitutionId = $repository->delete('bde559b6-ce66-4064-b7b6-8f164bbaa1eb');

        $this->assertEquals('bde559b6-ce66-4064-b7b6-8f164bbaa1eb', $thothInstitutionId);
    }
}
