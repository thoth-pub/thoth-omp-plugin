<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothAffiliationServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAffiliationServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothAffiliationService
 *
 * @brief Test class for the ThothAffiliationService class
 */

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.services.ThothAffiliationService');
import('plugins.generic.thoth.lib.thothAPI.models.ThothAffiliation');
import('plugins.generic.thoth.lib.thothAPI.ThothClient');

class ThothAffiliationServiceTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->affiliationService = new ThothAffiliationService();
    }

    protected function tearDown(): void
    {
        unset($this->affiliationService);
        parent::tearDown();
    }

    public function testeCreateNewAffiliation()
    {
        $expectedThothAffiliation = new ThothAffiliation();
        $expectedThothAffiliation->setId('42d407e2-fd07-4c45-853d-74ddfc0a02a8');
        $expectedThothAffiliation->setContributionId('8b4a7128-c483-459c-bb5d-89bf553ddf21');
        $expectedThothAffiliation->setInstitutionId('918ab03e-248b-4cc8-8bf6-1f0c1166d98d');
        $expectedThothAffiliation->setAffiliationOrdinal(1);

        $params = [
            'affiliationId' => '42d407e2-fd07-4c45-853d-74ddfc0a02a8',
            'contributionId' => '8b4a7128-c483-459c-bb5d-89bf553ddf21',
            'institutionId' => '918ab03e-248b-4cc8-8bf6-1f0c1166d98d',
            'affiliationOrdinal' => 1,
        ];

        $thothAffiliation  = $this->affiliationService->new($params);

        $this->assertEquals($expectedThothAffiliation, $thothAffiliation);
    }

    public function testRegisterInstitution()
    {
        $expectedThothAffiliation = new ThothAffiliation();
        $expectedThothAffiliation->setId('0e721ddc-4ea5-453a-8590-e236a5f2db9b');
        $expectedThothAffiliation->setContributionId('5c0a1fcb-2785-407e-8671-95c662bea559');
        $expectedThothAffiliation->setInstitutionId('8ae6f820-b1ef-400c-852a-729c942bf8f2');
        $expectedThothAffiliation->setAffiliationOrdinal(1);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createAffiliation','institutions'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createAffiliation')
            ->will($this->returnValue('0e721ddc-4ea5-453a-8590-e236a5f2db9b'));
        $mockThothClient->expects($this->any())
            ->method('institutions')
            ->will($this->returnValue([
                [
                    'institutionId' => '8ae6f820-b1ef-400c-852a-729c942bf8f2',
                    'institutionName' => 'Aalborg University',
                    'institutionDoi' => 'https://doi.org/10.13039/501100002702',
                    'countryCode' => 'DNK',
                    'ror' => 'https://ror.org/04m5j1k67'
                ]
            ]));

        $thothAffiliation = $this->affiliationService->register(
            $mockThothClient,
            'Aalborg University',
            '5c0a1fcb-2785-407e-8671-95c662bea559'
        );
        $this->assertEquals($expectedThothAffiliation, $thothAffiliation);
    }
}
