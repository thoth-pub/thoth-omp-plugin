<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothAffiliationServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
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

use APP\author\Author;
use APP\plugins\generic\thoth\classes\container\ThothContainer;
use APP\plugins\generic\thoth\classes\repositories\ThothAffiliationRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothInstitutionRepository;
use APP\plugins\generic\thoth\classes\services\ThothAffiliationService;
use PKP\affiliation\Affiliation;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Inputs\PatchAffiliation as ThothAffiliation;
use ThothApi\GraphQL\Inputs\PatchInstitution as ThothInstitution;

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

    private function createAffiliation(?string $ror = null): Affiliation
    {
        $affiliation = new Affiliation();
        $affiliation->setRor($ror);
        return $affiliation;
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

        $mockRepository = $this->getMockBuilder(ThothAffiliationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->willReturn('43f98edb-ac8c-45b4-9faa-2941a05c133c');

        $affiliation = $this->createAffiliation('https://ror.org/00101234');

        $thothContributionId = '7315563c-e5c3-40b2-8558-8d1f9cede901';

        $service = new ThothAffiliationService($mockRepository, $mockInstitutionRepository);
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

        $mockRepository = $this->getMockBuilder(ThothAffiliationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->never())
            ->method('add');

        $affiliation = $this->createAffiliation('https://ror.org/00101234');

        $thothContributionId = '7315563c-e5c3-40b2-8558-8d1f9cede901';

        $service = new ThothAffiliationService($mockRepository, $mockInstitutionRepository);
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

        $mockInstitutionRepository = $this->getMockBuilder(ThothInstitutionRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['find'])
            ->getMock();
        $mockInstitutionRepository->expects($this->never())
            ->method('find');

        $affiliation = $this->createAffiliation(null);

        $thothContributionId = '7315563c-e5c3-40b2-8558-8d1f9cede901';

        $service = new ThothAffiliationService($mockRepository, $mockInstitutionRepository);
        $result = $service->register($affiliation, $thothContributionId, 1);

        $this->assertNull($result);
    }

    public function testUpdateByAuthorReconcilesAffiliations(): void
    {
        $firstInstitution = new ThothInstitution(['institutionId' => 'first-institution-id']);
        $newInstitution = new ThothInstitution(['institutionId' => 'new-institution-id']);
        $mockInstitutionRepository = $this->getMockBuilder(ThothInstitutionRepository::class)
            ->setConstructorArgs([$this->createMock(ThothClient::class)])
            ->onlyMethods(['find'])
            ->getMock();
        $mockInstitutionRepository->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                ['https://ror.org/first', $firstInstitution],
                ['https://ror.org/new', $newInstitution],
            ]);

        $mockRepository = $this->getMockBuilder(ThothAffiliationRepository::class)
            ->setConstructorArgs([$this->createMock(ThothClient::class)])
            ->onlyMethods(['add', 'edit', 'delete'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function (ThothAffiliation $affiliation): bool {
                return $affiliation->getAffiliationId() === 'existing-affiliation-id'
                    && $affiliation->getInstitutionId() === 'first-institution-id'
                    && $affiliation->getAffiliationOrdinal() === 1;
            }));
        $mockRepository->expects($this->once())
            ->method('add')
            ->with($this->callback(function (ThothAffiliation $affiliation): bool {
                return $affiliation->getInstitutionId() === 'new-institution-id'
                    && $affiliation->getAffiliationOrdinal() === 2;
            }));
        $mockRepository->expects($this->once())
            ->method('delete')
            ->with('removed-affiliation-id');

        $author = new Author();
        $author->setAffiliations([
            $this->createAffiliation('https://ror.org/first'),
            $this->createAffiliation('https://ror.org/new'),
        ]);

        $service = new ThothAffiliationService($mockRepository, $mockInstitutionRepository);
        $service->updateByAuthor($author, 'contribution-id', [
            [
                'affiliationId' => 'existing-affiliation-id',
                'institutionId' => 'first-institution-id',
                'affiliationOrdinal' => 2,
            ],
            [
                'affiliationId' => 'removed-affiliation-id',
                'institutionId' => 'removed-institution-id',
                'affiliationOrdinal' => 1,
            ],
        ]);
    }
}
