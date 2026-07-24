<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/services/ThothContributionServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothContributionService
 *
 * @brief Test class for the ThothContributionService class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\ContributionType;
use ThothApi\GraphQL\Inputs\PatchContribution as ThothContribution;
use ThothApi\GraphQL\Inputs\PatchContributor as ThothContributor;

import('classes.monograph.Author');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothContributionFactory');
import('plugins.generic.thoth.classes.repositories.ThothContributionRepository');
import('plugins.generic.thoth.classes.repositories.ThothContributorRepository');
import('plugins.generic.thoth.classes.services.ThothAffiliationService');
import('plugins.generic.thoth.classes.services.ThothBiographyService');
import('plugins.generic.thoth.classes.services.ThothContributionService');
import('plugins.generic.thoth.classes.services.ThothContributorService');

class ThothContributionServiceTest extends PKPTestCase
{
    public function testRegisterContribution()
    {
        $mockBiographyService = $this->createMock(ThothBiographyService::class);
        $mockBiographyService->expects($this->once())->method('registerByAuthor');

        $mockContributorRepository = $this->getMockBuilder(ThothContributorRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['find'])
            ->getMock();
        $mockContributorRepository->expects($this->once())
            ->method('find')
            ->will($this->returnValue(new ThothContributor(['contributorId' => 'existing-contributor-id'])));

        $mockContributorService = $this->createMock(ThothContributorService::class);
        $mockContributorService->expects($this->never())->method('register');
        $mockContributorService->expects($this->once())
            ->method('update')
            ->with($this->anything(), 'existing-contributor-id');

        $mockAffiliationService = $this->createMock(ThothAffiliationService::class);
        $mockAffiliationService->expects($this->never())->method('register');

        $mockFactory = $this->getMockBuilder(ThothContributionFactory::class)
            ->setMethods(['createFromAuthor'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromAuthor')
            ->will($this->returnValue(new ThothContribution()));

        $mockRepository = $this->getMockBuilder(ThothContributionRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('e2d8dc3b-a5d9-4941-8ebd-52f0a70515bd'));

        $mockAuthor = $this->createAuthor('John Doe', null, null, [
            'en_US' => 'English biography',
            'pt_BR' => 'Biografia em portugues',
        ]);
        $thothWorkId = '97fcc25c-361b-46f9-8c4b-016bfa36fb6d';

        $service = new ThothContributionService(
            $mockFactory,
            $mockRepository,
            $mockContributorRepository,
            $mockContributorService,
            $mockBiographyService,
            $mockAffiliationService
        );
        $thothContributionId = $service->register($mockAuthor, 0, $thothWorkId);

        $this->assertSame('e2d8dc3b-a5d9-4941-8ebd-52f0a70515bd', $thothContributionId);
    }

    public function testUpdateReconcilesContributionsByContributorAndType()
    {
        $mockBiographyService = $this->createMock(ThothBiographyService::class);
        $mockBiographyService->expects($this->once())->method('registerByAuthor');
        $mockBiographyService->expects($this->once())
            ->method('updateByAuthor')
            ->with(
                $this->anything(),
                'jane-contribution-id',
                [['biographyId' => 'jane-biography-id', 'localeCode' => 'EN_US', 'canonical' => true]],
                'en_US'
            );

        $mockContributorRepository = $this->getMockBuilder(ThothContributorRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['find'])
            ->getMock();
        $mockContributorRepository->expects($this->once())
            ->method('find')
            ->with('John Doe')
            ->willReturn($this->createThothContributor('john-contributor-id'));

        $mockContributorService = $this->createMock(ThothContributorService::class);
        $mockContributorService->expects($this->never())->method('register');
        $mockContributorService->expects($this->exactly(2))->method('update');
        $mockAffiliationService = $this->createMock(ThothAffiliationService::class);
        $mockAffiliationService->expects($this->never())->method('register');
        $mockAffiliationService->expects($this->once())
            ->method('update')
            ->with(
                'https://ror.org/00101234',
                'jane-contribution-id',
                [[
                    'affiliationId' => 'jane-affiliation-id',
                    'institutionId' => 'jane-institution-id',
                    'affiliationOrdinal' => 1,
                ]]
            );

        $contributionOrdinal = 0;
        $mockFactory = $this->getMockBuilder(ThothContributionFactory::class)
            ->setMethods(['createFromAuthor'])
            ->getMock();
        $mockFactory->expects($this->exactly(2))
            ->method('createFromAuthor')
            ->willReturnCallback(function ($author) use (&$contributionOrdinal) {
                $contributionOrdinal++;
                return $this->createThothContribution([
                    'contributionType' => ContributionType::AUTHOR,
                    'contributionOrdinal' => $contributionOrdinal,
                    'fullName' => $author->getFullName(false),
                ]);
            });

        $mockRepository = $this->getMockBuilder(ThothContributionRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add', 'edit', 'delete'])
            ->getMock();
        $mockRepository->expects($this->once())->method('add')->willReturn('john-contribution-id');
        $mockRepository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function ($contribution) {
                return $contribution->getContributionId() === 'jane-contribution-id'
                    && $contribution->getContributorId() === 'jane-contributor-id';
            }));
        $mockRepository->expects($this->once())
            ->method('delete')
            ->with('removed-contribution-id');

        $service = new ThothContributionService(
            $mockFactory,
            $mockRepository,
            $mockContributorRepository,
            $mockContributorService,
            $mockBiographyService,
            $mockAffiliationService
        );
        $service->update([
            $this->createAuthor(
                'Jane Doe',
                'https://orcid.org/0000-0001-2345-6789',
                'https://ror.org/00101234'
            ),
            $this->createAuthor('John Doe'),
        ], 'work-id', [
            [
                'contributionId' => 'jane-contribution-id',
                'contributorId' => 'jane-contributor-id',
                'contributionType' => ContributionType::AUTHOR,
                'fullName' => 'Jane Doe',
                'contributor' => ['orcid' => '0000-0001-2345-6789'],
                'biographies' => [
                    ['biographyId' => 'jane-biography-id', 'localeCode' => 'EN_US', 'canonical' => true],
                ],
                'affiliations' => [[
                    'affiliationId' => 'jane-affiliation-id',
                    'institutionId' => 'jane-institution-id',
                    'affiliationOrdinal' => 1,
                ]],
            ],
            [
                'contributionId' => 'removed-contribution-id',
                'contributorId' => 'jane-contributor-id',
                'contributionType' => ContributionType::EDITOR,
                'fullName' => 'Jane Doe',
                'contributor' => ['orcid' => '0000-0001-2345-6789'],
            ],
        ]);
    }

    public function testUpdateMatchesRenamedContributionByTypeAndOrdinal()
    {
        $author = $this->createAuthor('Juliana Castanheiras');
        $mockFactory = $this->getMockBuilder(ThothContributionFactory::class)
            ->setMethods(['createFromAuthor'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromAuthor')
            ->with($author)
            ->willReturn($this->createThothContribution([
                'contributionType' => ContributionType::AUTHOR,
                'contributionOrdinal' => 4,
                'fullName' => 'Juliana Castanheiras',
            ]));

        $mockRepository = $this->getMockBuilder(ThothContributionRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add', 'edit', 'delete'])
            ->getMock();
        $mockRepository->expects($this->never())->method('add');
        $mockRepository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function ($contribution) {
                return $contribution->getContributionId() === 'contribution-id'
                    && $contribution->getContributorId() === 'contributor-id'
                    && $contribution->getFullName() === 'Juliana Castanheiras';
            }));
        $mockRepository->expects($this->never())->method('delete');

        $mockContributorRepository = $this->getMockBuilder(ThothContributorRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['find'])
            ->getMock();
        $mockContributorRepository->expects($this->never())->method('find');

        $mockContributorService = $this->createMock(ThothContributorService::class);
        $mockContributorService->expects($this->never())->method('register');
        $mockContributorService->expects($this->once())
            ->method('update')
            ->with($author, 'contributor-id');

        $mockBiographyService = $this->createMock(ThothBiographyService::class);
        $mockBiographyService->expects($this->once())
            ->method('updateByAuthor')
            ->with($author, 'contribution-id', [], 'en_US');
        $mockAffiliationService = $this->createMock(ThothAffiliationService::class);
        $mockAffiliationService->expects($this->once())
            ->method('update')
            ->with(null, 'contribution-id', []);

        $service = new ThothContributionService(
            $mockFactory,
            $mockRepository,
            $mockContributorRepository,
            $mockContributorService,
            $mockBiographyService,
            $mockAffiliationService
        );
        $service->update([$author], 'work-id', [[
            'contributionId' => 'contribution-id',
            'contributorId' => 'contributor-id',
            'contributionType' => ContributionType::AUTHOR,
            'contributionOrdinal' => 4,
            'fullName' => 'Iris Castanheiras',
            'contributor' => ['orcid' => null],
            'biographies' => [],
            'affiliations' => [],
        ]]);
    }

    private function createAuthor($fullName, $orcid = null, $rorId = null, $biography = null)
    {
        $author = $this->getMockBuilder(Author::class)
            ->setMethods(['getData', 'getOrcid', 'getFullName'])
            ->getMock();
        $author->method('getData')->willReturnCallback(function ($key) use ($rorId, $biography) {
            $values = ['locale' => 'en_US', 'rorId' => $rorId, 'biography' => $biography];
            return $values[$key] ?? null;
        });
        $author->method('getOrcid')->willReturn($orcid);
        $author->method('getFullName')->willReturn($fullName);

        return $author;
    }

    private function createThothContributor($contributorId = null)
    {
        return new ThothContributor(['contributorId' => $contributorId]);
    }

    private function createThothContribution(array $data = [])
    {
        return new ThothContribution($data);
    }
}
