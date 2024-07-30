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
import('plugins.generic.thoth.thoth.models.ThothWork');
import('plugins.generic.thoth.thoth.models.ThothWorkRelation');
import('plugins.generic.thoth.thoth.ThothClient');

class ThothClientTest extends PKPTestCase
{
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
        $work->setWorkType(ThothWork::WORK_TYPE_MONOGRAPH);
        $work->setWorkStatus(ThothWork::WORK_STATUS_ACTIVE);
        $work->setFullTitle('Feliks Volkhovskii');
        $work->setTitle('Feliks Volkhovskii');
        $work->setEdition(1);
        $work->setImprintId('e3cb2206-c2b6-4835-9f35-24bfa1572643');

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
        $contributor->setFirstName('Adriana Laura');
        $contributor->setLastName('Massidda');
        $contributor->setFullName('Adriana Laura Massidda');
        $contributor->setOrcid('https://orcid.org/0000-0001-8735-7990');
        $contributor->setWebsite('https://sites.google.com/site/adrianamassidda');

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
        $contribution->setWorkId('e763a10c-1e2b-4b10-84c4-ac3f95236a97');
        $contribution->setContributorId('e1de541c-e84b-4092-941f-dab9b5dac865');
        $contribution->setContributionType(ThothContribution::CONTRIBUTION_TYPE_EDITOR);
        $contribution->setMainContribution(false);
        $contribution->setContributionOrdinal(1);
        $contribution->setFirstName('Thomas');
        $contribution->setLastName('Pringle');
        $contribution->setFullName('Thomas Patrick Pringle');
        $contribution->setBiography(
            'Thomas Pringle is an SSHRC doctoral and presidential fellow at Brown University, ' .
            'where he is a PhD candidate in the Department of Modern Culture and Media.'
        );

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
        $workRelation->setId('3e587b61-58f1-4064-bf80-e40e5c924d27');
        $workRelation->setRelatorWorkId('991f1070-67fa-4e6e-8519-114006043492');
        $workRelation->setRelatedWorkId('7d861db5-22f6-4ef8-abbb-b56ab8397624');
        $workRelation->setRelationType(ThothWorkRelation::RELATION_TYPE_IS_CHILD_OF);
        $workRelation->setRelationOrdinal(1);

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
        $publication->setWorkId('991f1070-67fa-4e6e-8519-114006043492');
        $publication->setPublicationType(ThothPublication::PUBLICATION_TYPE_EPUB);
        $publication->setIsbn('978-1-78374-032-1');

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
        $location->setPublicationId('8ac3e585-c32a-42d7-bd36-ef42ee397e6e');

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
        $subject->setWorkId('7fbd3c3e-1e37-4352-9211-82665cc25ce1');

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
}
