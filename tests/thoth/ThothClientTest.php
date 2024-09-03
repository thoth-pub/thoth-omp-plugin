<?php

/**
 * @file plugins/generic/thoth/tests/thoth/ThothClientTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothClientTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothClient
 *
 * @brief Test class for the ThothClient class
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.models.ThothAffiliation');
import('plugins.generic.thoth.thoth.models.ThothContribution');
import('plugins.generic.thoth.thoth.models.ThothContributor');
import('plugins.generic.thoth.thoth.models.ThothImprint');
import('plugins.generic.thoth.thoth.models.ThothInstitution');
import('plugins.generic.thoth.thoth.models.ThothLanguage');
import('plugins.generic.thoth.thoth.models.ThothLocation');
import('plugins.generic.thoth.thoth.models.ThothPublication');
import('plugins.generic.thoth.thoth.models.ThothPublisher');
import('plugins.generic.thoth.thoth.models.ThothReference');
import('plugins.generic.thoth.thoth.models.ThothSubject');
import('plugins.generic.thoth.thoth.models.ThothWork');
import('plugins.generic.thoth.thoth.models.ThothWorkRelation');
import('plugins.generic.thoth.thoth.ThothClient');

class ThothClientTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesPath = __DIR__ . '/../fixtures/';
    }

    protected function tearDown(): void
    {
        unset($this->fixturesPath);
        parent::tearDown();
    }

    public function testLoginWithInvalidCredentials()
    {
        $mockHandler = new MockHandler([
            new RequestException(
                'Client error',
                new Request('POST', 'https://api.thoth.test.pub/account/login'),
                new Response(
                    401,
                    [],
                    'Invalid credentials.'
                )
            )
        ]);
        $guzzleClient = new Client(['handler' => $mockHandler]);

        $this->expectException(ThothException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('Failed to send the request to Thoth: Invalid credentials.');

        $thothClient = new ThothClient(true, $guzzleClient);
        $thothClient->login('user72581@mailinator.com', 'uys9ag9s');
    }

    public function testGetLinkedPublishers()
    {
        $expectedLinkedPublishers = [
            [
                'publisherId' => '7e0435ab-a0a6-4dca-8503-ffc1dcaa9f9d',
                'isAdmin' => true
            ]
        ];

        $mock = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents($this->fixturesPath . 'accountDetails.json')
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $linkedPublishers = $client->linkedPublishers();

        $this->assertEquals($expectedLinkedPublishers, $linkedPublishers);
    }

    public function testCreateAffiliation()
    {
        $thothAffiliation = new ThothAffiliation();

        $mock = new MockHandler([
            new Response(
                200,
                [],
                '{"data":{"createAffiliation":{"affiliationId":"1c6e252c-9fb3-404e-ac0a-1e03cd66aa70"}}}'
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $thothAffiliationId = $client->createAffiliation($thothAffiliation);

        $this->assertEquals('1c6e252c-9fb3-404e-ac0a-1e03cd66aa70', $thothAffiliationId);
    }

    public function testWorkCreation()
    {
        $work = new ThothWork();

        $mock = new MockHandler([
            new Response(
                200,
                [],
                '{"data":{"createWork":{"workId":"62933c17-7f40-46af-84ab-b563ac4ac448"}}}'
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $workId = $client->createWork($work);

        $this->assertEquals('62933c17-7f40-46af-84ab-b563ac4ac448', $workId);
    }

    public function testContributorCreation()
    {
        $contributor = new ThothContributor();

        $mock = new MockHandler([
            new Response(
                200,
                [],
                '{"data":{"createContributor":{"contributorId":"454d55ec-6c4c-42b9-bbf9-fa08b70d7f1d"}}}'
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $contributorId = $client->createContributor($contributor);

        $this->assertEquals('454d55ec-6c4c-42b9-bbf9-fa08b70d7f1d', $contributorId);
    }

    public function testContributionCreation()
    {
        $contribution = new ThothContribution();

        $mock = new MockHandler([
            new Response(
                200,
                [],
                '{"data":{"createContribution":{"contributionId":"bd59feee-53bd-403d-a1a9-db01c0edf10b"}}}'
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $contributionId = $client->createContribution($contribution);

        $this->assertEquals('bd59feee-53bd-403d-a1a9-db01c0edf10b', $contributionId);
    }

    public function testRelationCreation()
    {
        $workRelation = new ThothWorkRelation();

        $mock = new MockHandler([
            new Response(
                200,
                [],
                '{"data":{"createWorkRelation":{"workRelationId":"07253e79-e02f-4350-b4b5-e5fd27866ee2"}}}'
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $workRelationId = $client->createWorkRelation($workRelation);

        $this->assertEquals('07253e79-e02f-4350-b4b5-e5fd27866ee2', $workRelationId);
    }

    public function testPublicationCreation()
    {
        $publication = new ThothPublication();

        $mock = new MockHandler([
            new Response(
                200,
                [],
                '{"data":{"createPublication":{"publicationId":"4f51514b-5d45-42fc-a757-185cd5cee7b1"}}}'
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $publicationId = $client->createPublication($publication);

        $this->assertEquals('4f51514b-5d45-42fc-a757-185cd5cee7b1', $publicationId);
    }

    public function testLocationCreation()
    {
        $location = new ThothLocation();

        $mock = new MockHandler([
            new Response(
                200,
                [],
                '{"data":{"createLocation":{"locationId":"03b0367d-bba3-4e26-846a-4c36d3920db2"}}}'
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $locationId = $client->createLocation($location);

        $this->assertEquals('03b0367d-bba3-4e26-846a-4c36d3920db2', $locationId);
    }

    public function testSubjectCreation()
    {
        $subject = new ThothSubject();

        $mock = new MockHandler([
            new Response(
                200,
                [],
                '{"data":{"createSubject":{"subjectId":"279b9910-38bf-4742-a7ae-cfd9eeb10bf8"}}}'
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $subjectId = $client->createSubject($subject);

        $this->assertEquals('279b9910-38bf-4742-a7ae-cfd9eeb10bf8', $subjectId);
    }

    public function testLanguageCreation()
    {
        $language = new ThothLanguage();

        $mock = new MockHandler([
            new Response(
                200,
                [],
                '{"data":{"createLanguage":{"languageId":"4cfdf70d-cd8c-41a5-a5e2-356a2ff2f37f"}}}'
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $languageId = $client->createLanguage($language);

        $this->assertEquals('4cfdf70d-cd8c-41a5-a5e2-356a2ff2f37f', $languageId);
    }

    public function testReferenceCreation()
    {
        $reference = new ThothReference();

        $mock = new MockHandler([
            new Response(
                200,
                [],
                '{"data":{"createReference":{"referenceId":"56338ed3-d2a9-4ef4-9afc-303d63be719f"}}}'
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $referenceId = $client->createReference($reference);

        $this->assertEquals('56338ed3-d2a9-4ef4-9afc-303d63be719f', $referenceId);
    }

    public function testGetContribution()
    {
        $contributionId = 'f6b4b1ba-6849-42f0-b43e-fff5c6693738';

        $expectedContribution = [
            'contributionId' => $contributionId,
            'contributorId' => '70a8a8c1-06f7-4adf-bfec-9421a6a70813',
            'workId' => '473fcddc-23ee-46a4-8ffa-afa5020ac540',
            'contributionType' => 'AUTHOR',
            'mainContribution' => true,
            'biography' => 'Paula Bialski is junior professor of digital sociality at Leuphana University Lüneburg. ' .
                'She is an ethnographer of new media in everyday life and the author of Becoming Intimately Mobile.',
            'firstName' => 'Paula',
            'lastName' => 'Bialski',
            'fullName' => 'Paula Bialski',
            'contributionOrdinal' => 1
        ];

        $mock = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents($this->fixturesPath . 'contribution.json')
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $contribution = $client->contribution($contributionId);

        $this->assertEquals($expectedContribution, $contribution);
    }

    public function testGetContributions()
    {
        $contributionId = '6a132206-62f9-43ca-a05b-32e534eaf11a';

        $expectedContributions = [
            [
                'contributionId' => $contributionId,
                'contributorId' => 'c165d8d5-f8e1-43a3-ad46-9c999841ab12',
                'workId' => '2566ed3d-3df1-4e56-a7dc-455ecf7d3a4b',
                'contributionType' => 'AUTHOR',
                'mainContribution' => true,
                'biography' => '(7 December 1937 - 30 June 2021) was a British historian, senior research fellow at ' .
                    'the Institute of English Studies, School of Advanced Study, University of London.',
                'firstName' => 'William',
                'lastName' => 'St Clair',
                'fullName' => 'William St Clair',
                'contributionOrdinal' => 1
            ]
        ];

        $mock = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents($this->fixturesPath . 'contributions.json')
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $contributions = $client->contributions();

        $this->assertEquals($expectedContributions, $contributions);
    }

    public function testGetContributor()
    {
        $contributorId = 'e8def8cf-0dfe-4da9-b7fa-f77e7aec7524';

        $expectedContributor = [
            'contributorId' => $contributorId,
            'firstName' => 'Martin Paul',
            'lastName' => 'Eve',
            'fullName' => 'Martin Paul Eve',
            'orcid' => 'https://orcid.org/0000-0002-5589-8511',
            'website' => 'https://eve.gd/'
        ];

        $mock = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents($this->fixturesPath . 'contributor.json')
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $contributor = $client->contributor($contributorId);

        $this->assertEquals($expectedContributor, $contributor);
    }

    public function testGetContributors()
    {
        $expectedContributors = [
            [
                'contributorId' => 'fd1ea3ac-bb47-4a19-a743-5c2c38a400bc',
                'firstName' => 'Ádám',
                'lastName' => 'Bethlenfalvy',
                'fullName' => 'Ádám Bethlenfalvy',
                'orcid' => 'https://orcid.org/0000-0002-4251-8161',
                'website' => 'https://www.linkedin.com/in/adam-bethlenfalvy-31b18489/'
            ]
        ];

        $mock = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents($this->fixturesPath . 'contributors.json')
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $contributors = $client->contributors();

        $this->assertEquals($expectedContributors, $contributors);
    }

    public function testGetInstitution()
    {
        $institutionId = '6e451aef-e496-4730-ac86-9f60d8ef4c55';

        $expectedInstitution = [
            'institutionId' => $institutionId,
            'institutionName' => 'National Science Foundation',
            'institutionDoi' => 'https://doi.org/10.13039/100000001',
            'countryCode' => 'USA',
            'ror' => 'https://ror.org/021nxhr62'
        ];

        $mock = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents($this->fixturesPath . 'institution.json')
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $institution = $client->institution($institutionId);

        $this->assertEquals($expectedInstitution, $institution);
    }

    public function testGetInstitutions()
    {
        $expectedInstitutions = [
            [
                'institutionId' => '6302c2bb-8e89-4d9a-801a-16b2329fd493',
                'institutionName' => 'Arctic Sciences',
                'institutionDoi' => 'https://doi.org/10.13039/100000163',
                'countryCode' => 'USA',
                'ror' => 'https://ror.org/02trddg58'
            ]
        ];

        $mock = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents($this->fixturesPath . 'institutions.json')
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $institutions = $client->institutions();

        $this->assertEquals($expectedInstitutions, $institutions);
    }

    public function testGetImprint()
    {
        $imprintId = '5078b33c-5b3f-48bf-bf37-ced6b02beb7c';

        $expectedImprint = [
            'imprintId' => $imprintId,
            'publisherId' => '4ab3bec2-c491-46d4-8731-47a5d9b33cc5',
            'imprintName' => 'mediastudies.press',
            'imprintUrl' => 'https://www.mediastudies.press/',
            'crossmarkDoi' => 'https://doi.org/10.33333/87654321'
        ];

        $mock = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents($this->fixturesPath . 'imprint.json')
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $imprint = $client->imprint($imprintId);

        $this->assertEquals($expectedImprint, $imprint);
    }

    public function testGetImprints()
    {
        $expectedImprints = [
            [
                'imprintId' => '8bf133ee-e6d0-4a5f-981b-fda73bcc389c',
                'publisherId' => '7ec3811c-667b-419e-b96c-a726acac610c',
                'imprintName' => 'Edinburgh Diamond',
                'imprintUrl' => 'https://books.ed.ac.uk/edinburgh-diamond/',
                'crossmarkDoi' => 'https://doi.org/10.12345/11122233'
            ]
        ];

        $mock = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents($this->fixturesPath . 'imprints.json')
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $imprints = $client->imprints();

        $this->assertEquals($expectedImprints, $imprints);
    }

    public function testGetPublisher()
    {
        $publisherId = 'd2459c17-ae6c-4179-a0ec-9aebd4c2d0be';

        $expectedPublisher = [
            'publisherId' => $publisherId,
            'publisherName' => 'Editorial Universidad del Rosario',
            'publisherShortname' => 'Editorial UR',
            'publisherUrl' => 'https://editorial.urosario.edu.co/'
        ];

        $mock = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents($this->fixturesPath . 'publisher.json')
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $publisher = $client->publisher($publisherId);

        $this->assertEquals($expectedPublisher, $publisher);
    }

    public function testGetPublishers()
    {
        $expectedPublishers = [
            [
                'publisherId' => 'f2229e70-e973-4e89-b60f-1055fa3d7505',
                'publisherName' => 'University of Westminster Press',
                'publisherShortname' => 'UWP',
                'publisherUrl' => 'https://www.uwestminsterpress.co.uk/'
            ]
        ];

        $mock = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents($this->fixturesPath . 'publishers.json')
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $publishers = $client->publishers();

        $this->assertEquals($expectedPublishers, $publishers);
    }

    public function testGetWork()
    {
        $workId = 'e0f748b2-984f-45cc-8b9e-13989c31dda4';

        $expectedWork = [
            'workId' => '743ada7a-1d19-4968-b5e4-6a7656d48f02',
            'workType' => ThothWork::WORK_TYPE_MONOGRAPH,
            'workStatus' => ThothWork::WORK_STATUS_ACTIVE,
            'fullTitle' => '10necessárias falas: cidade, arquitetura e urbanismo',
            'title' => '10necessárias falas',
            'subtitle' => 'cidade, arquitetura e urbanismo',
            'edition' => 1,
            'imprintId' => '5cf0b304-6ee5-45c7-a89d-53cd135d8d2b',
            'doi' => 'https://doi.org/10.7476/9788523211516',
            'publicationDate' => '2010-01-01',
            'pageCount' => 252,
            'license' => 'https://creativecommons.org/licenses/by/4.0/',
            'copyrightHolder' => null,
            'landingPage' => 'https://books.scielo.org/id/zhjcx',
            'longAbstract' => '10necessárias falas: Cidade, Arquitetura e Urbanismo consiste em uma coletânea de ensaios acerca de planejamento, arquitetura e urbanismo, estabelecendo relações de totalidade e fragmentos, expondo ideias e percepções do autor, algumas ainda provisórias e abertas ao diálogo com os leitores.',
            'coverUrl' => 'https://books.scielo.org/id/zhjcx/cover/cover.jpeg'
        ];

        $mock = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents($this->fixturesPath . 'work.json')
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $work = $client->work($workId);

        $this->assertEquals($expectedWork, $work);
    }

    public function testUpdateWork()
    {
        $thothWork = new ThothWork();

        $mock = new MockHandler([
            new Response(
                200,
                [],
                '{"data":{"updateWork":{"workId":"ad3b25d6-44f7-4419-9460-4e170c4ec64f"}}}'
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $thothWorkId = $client->updateWork($thothWork);

        $this->assertEquals('ad3b25d6-44f7-4419-9460-4e170c4ec64f', $thothWorkId);
    }

    public function testDeleteContribution()
    {
        $mock = new MockHandler([
            new Response(
                200,
                [],
                '{"data":{"deleteContribution":{"contributionId":"819d8d49-6252-49d0-8f87-6b7487a0eecc"}}}'
            )
        ]);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new ThothClient(true, $httpClient);
        $thothContributionId = $client->deleteContribution('819d8d49-6252-49d0-8f87-6b7487a0eecc');

        $this->assertEquals('819d8d49-6252-49d0-8f87-6b7487a0eecc', $thothContributionId);
    }
}
