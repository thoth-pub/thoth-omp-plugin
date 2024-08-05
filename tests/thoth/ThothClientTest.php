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
import('plugins.generic.thoth.thoth.models.ThothContribution');
import('plugins.generic.thoth.thoth.models.ThothContributor');
import('plugins.generic.thoth.thoth.models.ThothLanguage');
import('plugins.generic.thoth.thoth.models.ThothLocation');
import('plugins.generic.thoth.thoth.models.ThothPublication');
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

        $thothClient = new ThothClient('https://api.thoth.test.pub/', $guzzleClient);
        $thothClient->login('user72581@mailinator.com', 'uys9ag9s');
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

        $client = new ThothClient('https://api.thoth.test.pub/', $httpClient);
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

        $client = new ThothClient('https://api.thoth.test.pub/', $httpClient);
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

        $client = new ThothClient('https://api.thoth.test.pub/', $httpClient);
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

        $client = new ThothClient('https://api.thoth.test.pub/', $httpClient);
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

        $client = new ThothClient('https://api.thoth.test.pub/', $httpClient);
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

        $client = new ThothClient('https://api.thoth.test.pub/', $httpClient);
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

        $client = new ThothClient('https://api.thoth.test.pub/', $httpClient);
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

        $client = new ThothClient('https://api.thoth.test.pub/', $httpClient);
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

        $client = new ThothClient('https://api.thoth.test.pub/', $httpClient);
        $referenceId = $client->createReference($reference);

        $this->assertEquals('56338ed3-d2a9-4ef4-9afc-303d63be719f', $referenceId);
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

        $client = new ThothClient('https://api.thoth.test.pub/', $httpClient);
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

        $client = new ThothClient('https://api.thoth.test.pub/', $httpClient);
        $contributors = $client->contributors();

        $this->assertEquals($expectedContributors, $contributors);
    }
}
