<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothInstitutionServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothInstitutionServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothInstitutionService
 *
 * @brief Test class for the ThothInstitutionService class
 */

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Models\Institution as ThothInstitution;

import('plugins.generic.thoth.classes.services.ThothInstitutionService');

class ThothInstitutionServiceTest extends PKPTestCase
{
    private $clientFactoryBackup;
    private $configFactoryBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientFactoryBackup = ThothContainer::getInstance()->backup('client');
        $this->institutionService = new ThothInstitutionService();
    }

    protected function tearDown(): void
    {
        unset($this->institutionService);
        ThothContainer::getInstance()->set('client', $this->clientFactoryBackup);
        parent::tearDown();
    }

    public function testCreateNewInstitution()
    {
        $expectedThothInstitution = new ThothInstitution();
        $expectedThothInstitution->setInstitutionId('6e451aef-e496-4730-ac86-9f60d8ef4c55');
        $expectedThothInstitution->setInstitutionName('National Science Foundation');
        $expectedThothInstitution->setInstitutionDoi('https://doi.org/10.13039/100000001');
        $expectedThothInstitution->setCountryCode('USA');
        $expectedThothInstitution->setRor('https://ror.org/021nxhr62');

        $params = [
            'institutionId' => '6e451aef-e496-4730-ac86-9f60d8ef4c55',
            'institutionName' => 'National Science Foundation',
            'institutionDoi' => 'https://doi.org/10.13039/100000001',
            'countryCode' => 'USA',
            'ror' => 'https://ror.org/021nxhr62'
        ];

        $thothInstitution = $this->institutionService->new($params);

        $this->assertEquals($expectedThothInstitution, $thothInstitution);
    }

    public function testGetManyInstitutions()
    {
        $expectedThothInstitutions = [];
        $expectedThothInstitutions[] = new ThothInstitution();
        $expectedThothInstitutions[0]->setInstitutionId('f014c35a-31d8-453c-b356-d4912a87e52e');
        $expectedThothInstitutions[0]->setInstitutionName('United States Department of Defense');
        $expectedThothInstitutions[0]->setInstitutionDoi('https://doi.org/10.13039/100000005');
        $expectedThothInstitutions[0]->setCountryCode('USA');
        $expectedThothInstitutions[0]->setRor('https://ror.org/0447fe631');

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods([
                'institutions',
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('institutions')
            ->will($this->returnValue([
                new ThothInstitution([
                    'institutionId' => 'f014c35a-31d8-453c-b356-d4912a87e52e',
                    'institutionName' => 'United States Department of Defense',
                    'institutionDoi' => 'https://doi.org/10.13039/100000005',
                    'countryCode' => 'USA',
                    'ror' => 'https://ror.org/0447fe631'
                ])
            ]));

        ThothContainer::getInstance()->set('client', function () use ($mockThothClient) {
            return $mockThothClient;
        });

        $thothInstitutions = $this->institutionService->getMany();

        $this->assertEquals($expectedThothInstitutions, $thothInstitutions);
    }
}
