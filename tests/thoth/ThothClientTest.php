<?php

/**
 * @file plugins/generic/thoth/tests/thoth/ThothClientTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
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
import('plugins.generic.thoth.thoth.models.Work');
import('plugins.generic.thoth.thoth.models.Contributor');
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
        $work = new Work();
        $work->setWorkType(Work::WORK_TYPE_MONOGRAPH);
        $work->setWorkStatus(Work::WORK_STATUS_ACTIVE);
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
        $contributor = new Contributor();
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
}
