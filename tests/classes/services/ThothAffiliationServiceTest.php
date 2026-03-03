<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothAffiliationServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAffiliationServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothAffiliationService
 *
 * @brief Test class for the ThothAffiliationService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

use PKP\tests\PKPTestCase;
use PKP\affiliation\Affiliation;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Institution as ThothInstitution;
use APP\plugins\generic\thoth\classes\container\ThothContainer;
use APP\plugins\generic\thoth\classes\repositories\ThothAffiliationRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothInstitutionRepository;
use APP\plugins\generic\thoth\classes\services\ThothAffiliationService;

class ThothAffiliationServiceTest extends PKPTestCase
{
    protected mixed $backup = null;
    public function setUp(): void
    {
        parent::setUp();
        $this->backup = ThothContainer::getInstance()->backup('institutionRepository');
    }

    protected function tearDown(): void
    {
        ThothContainer::getInstance()->set('institutionRepository', $this->backup);
        parent::tearDown();
    }

    private function createAffiliationMock(?string $ror = null): Affiliation
    {
        $mockAffiliation = $this->getMockBuilder(Affiliation::class)
            ->onlyMethods(['getRor'])
            ->getMock();
        $mockAffiliation->method('getRor')
            ->willReturn($ror);
        return $mockAffiliation;
    }

    public function testRegisterAffiliationWithExistingRorInstitution()
    {
        $mockInstitutionRepository = $this->getMockBuilder(ThothInstitutionRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['find'])
            ->getMock();
        $mockInstitutionRepository->expects($this->once())
            ->method('find')
            ->with('https://ror.org/00101234')
            ->willReturn(new ThothInstitution([
                'institutionId' => 'f5ae4d0e-1234-5678-9abc-def012345678'
            ]));

        ThothContainer::getInstance()->set('institutionRepository', fn () => $mockInstitutionRepository);

        $mockRepository = $this->getMockBuilder(ThothAffiliationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->willReturn('43f98edb-ac8c-45b4-9faa-2941a05c133c');

        $affiliation = $this->createAffiliationMock('https://ror.org/00101234');

        $thothContributionId = '7315563c-e5c3-40b2-8558-8d1f9cede901';

        $service = new ThothAffiliationService($mockRepository);
        $thothAffiliationId = $service->register($affiliation, $thothContributionId, 1);

        $this->assertSame('43f98edb-ac8c-45b4-9faa-2941a05c133c', $thothAffiliationId);
    }

    public function testRegisterAffiliationWithRorNotFoundInThothReturnsNull()
    {
        $mockInstitutionRepository = $this->getMockBuilder(ThothInstitutionRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['find'])
            ->getMock();
        $mockInstitutionRepository->expects($this->once())
            ->method('find')
            ->with('https://ror.org/00101234')
            ->willReturn(null);

        ThothContainer::getInstance()->set('institutionRepository', fn () => $mockInstitutionRepository);

        $mockRepository = $this->getMockBuilder(ThothAffiliationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->never())
            ->method('add');

        $affiliation = $this->createAffiliationMock('https://ror.org/00101234');

        $thothContributionId = '7315563c-e5c3-40b2-8558-8d1f9cede901';

        $service = new ThothAffiliationService($mockRepository);
        $result = $service->register($affiliation, $thothContributionId, 1);

        $this->assertNull($result);
    }

    public function testRegisterAffiliationWithoutRorReturnsNull()
    {
        $mockRepository = $this->getMockBuilder(ThothAffiliationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->never())
            ->method('add');

        $affiliation = $this->createAffiliationMock(null);

        $thothContributionId = '7315563c-e5c3-40b2-8558-8d1f9cede901';

        $service = new ThothAffiliationService($mockRepository);
        $result = $service->register($affiliation, $thothContributionId, 1);

        $this->assertNull($result);
    }
}
