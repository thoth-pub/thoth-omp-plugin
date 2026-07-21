<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothContributionRepositoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionRepositoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothContributionRepository
 *
 * @brief Test class for the ThothContributionRepository class
 */

namespace APP\plugins\generic\thoth\tests\classes\repositories;

use APP\plugins\generic\thoth\classes\repositories\ThothContributionRepository;
use Mockery;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\ContributionType;
use ThothApi\GraphQL\Inputs\PatchContribution as ThothContribution;
use ThothApi\GraphQL\Schemas\Work as ThothWork;

require_once __DIR__ . '/../../../vendor/autoload.php';

class ThothContributionRepositoryTest extends PKPTestCase
{
    public function testNewThothContribution()
    {
        $data = [
            'contributionType' => ContributionType::AUTHOR,
            'mainContribution' => true,
            'contributionOrdinal' => 1,
            'lastName' => 'John',
            'fullName' => 'John Doe'
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)->getMock();
        $repository = new ThothContributionRepository($mockThothClient);

        $thothContribution = $repository->new($data);
        $this->assertSame($data, $thothContribution->getAllData());
    }

    public function testGetContribution()
    {
        $expectedThothContribution = new ThothContribution([
            'contributionId' => '8d19d277-c42d-4bc4-b992-73174c7415e0',
            'contributionType' => ContributionType::AUTHOR,
            'mainContribution' => true,
            'contributionOrdinal' => 1,
            'lastName' => 'John',
            'fullName' => 'John Doe'
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('contribution')
            ->zeroOrMoreTimes()
            ->andReturn($expectedThothContribution);
        $repository = new ThothContributionRepository($mockThothClient);
        $thothContribution = $repository->get('8d19d277-c42d-4bc4-b992-73174c7415e0');

        $this->assertEquals($expectedThothContribution, $thothContribution);
    }

    public function testGetContributionsByWorkIdUsesDomainSelection(): void
    {
        $expectedContributions = [[
            'contributionId' => 'contribution-id',
            'contributorId' => 'contributor-id',
            'contributionType' => ContributionType::AUTHOR,
            'fullName' => 'Jane Doe',
            'contributor' => [
                'contributorId' => 'contributor-id',
                'firstName' => 'Jane',
                'lastName' => 'Doe',
                'orcid' => 'https://orcid.org/0000-0001-2345-6789',
                'fullName' => 'Jane Doe',
                'website' => 'https://example.com/jane',
            ],
            'biographies' => [[
                'biographyId' => 'biography-id',
                'contributionId' => 'contribution-id',
                'localeCode' => 'EN_US',
                'content' => '<p>Biography</p>',
                'canonical' => true,
            ]],
            'affiliations' => [[
                'affiliationId' => 'affiliation-id',
                'contributionId' => 'contribution-id',
                'institutionId' => 'institution-id',
                'affiliationOrdinal' => 1,
            ]],
        ]];
        $work = new ThothWork(['contributions' => $expectedContributions]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('work')
            ->once()
            ->with('work-id', [
                'contributions' => [
                    'contributionId',
                    'contributorId',
                    'contributionType',
                    'mainContribution',
                    'contributionOrdinal',
                    'firstName',
                    'lastName',
                    'fullName',
                    'contributor' => [
                        'contributorId',
                        'firstName',
                        'lastName',
                        'fullName',
                        'orcid',
                        'website',
                    ],
                    'biographies' => [
                        'biographyId',
                        'contributionId',
                        'localeCode',
                        'content',
                        'canonical',
                    ],
                    'affiliations' => [
                        'affiliationId',
                        'contributionId',
                        'institutionId',
                        'affiliationOrdinal',
                    ],
                ],
            ])
            ->andReturn($work);

        $repository = new ThothContributionRepository($mockThothClient);

        $this->assertSame($expectedContributions, $repository->getByWorkId('work-id'));
    }

    public function testAddContribution()
    {
        $thothContribution = new ThothContribution([
            'workId' => '66603c16-7f9f-440a-9584-09214491ec82',
            'contributorId' => '91350d60-b9e9-4083-a256-2d7acd6551e8',
            'contributionType' => ContributionType::AUTHOR,
            'mainContribution' => true,
            'contributionOrdinal' => 1,
            'lastName' => 'John',
            'fullName' => 'John Doe'
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('createContribution')
            ->zeroOrMoreTimes()
            ->andReturn('d2cea6e7-32d4-421c-9983-d4553a991efd');
        $repository = new ThothContributionRepository($mockThothClient);
        $thothContributionId = $repository->add($thothContribution);

        $this->assertEquals('d2cea6e7-32d4-421c-9983-d4553a991efd', $thothContributionId);
    }

    public function testEditContribution()
    {
        $thothPatchContribution = new ThothContribution([
            'contributionId' => 'ffc5404d-6365-434b-920b-da446cc3556e',
            'workId' => '66603c16-7f9f-440a-9584-09214491ec82',
            'contributorId' => '91350d60-b9e9-4083-a256-2d7acd6551e8',
            'contributionType' => ContributionType::AUTHOR,
            'mainContribution' => true,
            'contributionOrdinal' => 1,
            'lastName' => 'Johnathan',
            'fullName' => 'Johnathan Doe'
        ]);

        $mockThothClient = Mockery::mock(ThothClient::class);
        $mockThothClient->shouldReceive('updateContribution')
            ->zeroOrMoreTimes()
            ->andReturn('ffc5404d-6365-434b-920b-da446cc3556e');
        $repository = new ThothContributionRepository($mockThothClient);
        $thothContributionId = $repository->edit($thothPatchContribution);

        $this->assertEquals('ffc5404d-6365-434b-920b-da446cc3556e', $thothContributionId);
    }

    public function testDeleteContribution()
    {
        $mockThothClient = Mockery::mock(ThothClient::class);

        $mockThothClient->shouldReceive('deleteContribution')
            ->zeroOrMoreTimes()
            ->andReturn('ffc5404d-6365-434b-920b-da446cc3556e');
        $repository = new ThothContributionRepository($mockThothClient);
        $thothContributionId = $repository->delete('ffc5404d-6365-434b-920b-da446cc3556e');

        $this->assertEquals('ffc5404d-6365-434b-920b-da446cc3556e', $thothContributionId);
    }
}
