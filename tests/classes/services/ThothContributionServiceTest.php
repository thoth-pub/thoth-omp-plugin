<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothContributionServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothContributionService
 *
 * @brief Test class for the ThothContributionService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

use APP\plugins\generic\thoth\classes\factories\ThothContributionFactory;
use APP\plugins\generic\thoth\classes\repositories\ThothContributionRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothContributorRepository;
use APP\plugins\generic\thoth\classes\services\ThothAffiliationService;
use APP\plugins\generic\thoth\classes\services\ThothBiographyService;
use APP\plugins\generic\thoth\classes\services\ThothContributionService;
use APP\plugins\generic\thoth\classes\services\ThothContributorService;
use PKP\author\Author;
use PKP\facades\Locale;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\ContributionType;
use ThothApi\GraphQL\Inputs\PatchContribution as ThothContribution;
use ThothApi\GraphQL\Inputs\PatchContributor as ThothContributor;

require_once __DIR__ . '/../../../vendor/autoload.php';

class ThothContributionServiceTest extends PKPTestCase
{
    public function testRegisterContribution()
    {
        $mockBiographyService = $this->createMock(ThothBiographyService::class);
        $mockBiographyService->expects($this->once())->method('registerByAuthor');
        $mockContributorRepository = $this->getMockBuilder(ThothContributorRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['find'])
            ->getMock();
        $mockContributorRepository->expects($this->once())
            ->method('find')
            ->willReturn(new ThothContributor(['contributorId' => 'existing-contributor-id']));

        $mockContributorService = $this->createMock(ThothContributorService::class);
        $mockContributorService->expects($this->never())->method('register');
        $mockContributorService->expects($this->once())
            ->method('update')
            ->with($this->anything(), 'existing-contributor-id');

        $mockAffiliationService = $this->createMock(ThothAffiliationService::class);
        $mockAffiliationService->expects($this->never())->method('register');
        $mockAffiliationService->expects($this->once())
            ->method('registerByAuthor')
            ->with($this->anything(), 'e2d8dc3b-a5d9-4941-8ebd-52f0a70515bd');

        $mockFactory = $this->getMockBuilder(ThothContributionFactory::class)
            ->onlyMethods(['createFromAuthor'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromAuthor')
            ->willReturn(new ThothContribution());

        $mockRepository = $this->getMockBuilder(ThothContributionRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->willReturn('e2d8dc3b-a5d9-4941-8ebd-52f0a70515bd');

        $mockAuthor = $this->createAuthor('John Doe');
        $mockAuthor->setData('biography', [
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

    public function testUpdateReconcilesContributionsByContributorAndType(): void
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
            ->setConstructorArgs([$this->createMock(ThothClient::class)])
            ->onlyMethods(['find'])
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
        $mockAffiliationService->expects($this->once())->method('registerByAuthor');
        $mockAffiliationService->expects($this->once())
            ->method('updateByAuthor')
            ->with(
                $this->anything(),
                'jane-contribution-id',
                [[
                    'affiliationId' => 'jane-affiliation-id',
                    'institutionId' => 'jane-institution-id',
                    'affiliationOrdinal' => 1,
                ]]
            );

        $mockFactory = $this->getMockBuilder(ThothContributionFactory::class)
            ->onlyMethods(['createFromAuthor'])
            ->getMock();
        $mockFactory->expects($this->exactly(2))
            ->method('createFromAuthor')
            ->willReturnCallback(function ($author) {
                return $this->createThothContribution([
                    'contributionType' => ContributionType::AUTHOR,
                    'fullName' => $author->getFullName(false),
                ]);
            });

        $mockRepository = $this->getMockBuilder(ThothContributionRepository::class)
            ->setConstructorArgs([$this->createMock(ThothClient::class)])
            ->onlyMethods(['add', 'edit', 'delete'])
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
            $this->createAuthor('Jane Doe', 'https://orcid.org/0000-0001-2345-6789'),
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

    private function createAuthor(string $fullName, ?string $orcid = null): Author
    {
        [$givenName, $familyName] = array_pad(explode(' ', $fullName, 2), 2, '');
        $author = new Author();
        $author->setData('locale', 'en_US');
        $author->setData('submissionLocale', 'en_US');
        $author->setGivenName($givenName, Locale::getLocale());
        $author->setFamilyName($familyName, Locale::getLocale());
        $author->setOrcid($orcid);
        $author->setAffiliations([]);

        return $author;
    }

    private function createThothContributor(?string $contributorId = null)
    {
        return new ThothContributor(['contributorId' => $contributorId]);
    }

    private function createThothContribution(array $data = [])
    {
        return new ThothContribution($data);
    }
}
