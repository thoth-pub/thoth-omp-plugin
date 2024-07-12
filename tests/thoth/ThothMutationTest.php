<?php

/**
 * @file plugins/generic/thoth/tests/thoth/ThothMutationTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothMutationTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothMutation
 *
 * @brief Test class for the ThothMutation class
 */

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.models.Contributor');
import('plugins.generic.thoth.thoth.ThothAuthenticator');
import('plugins.generic.thoth.thoth.ThothGraphQL');
import('plugins.generic.thoth.thoth.ThothMutation');

class ThothMutationTest extends PKPTestCase
{
    public function testExecuteMutation()
    {
        $contributor = new Contributor();
        $contributor->setFirstName('Basem');
        $contributor->setLastName('Adi');
        $contributor->setFullName('Basem Adi');
        $contributor->setWebsite('https://sites.google.com/site/basemadi');

        $thothMutation = new ThothMutation('createContributor', $contributor);

        $body = '{"data":{"createContributor":{"contributorId":"abcd1234-e5f6-g7h8-i9j0-a1b2c3d4e5f6"}}}';
        $mock = new MockHandler([
            new Response(200, [], $body)
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $graphQl = new ThothGraphQl('https://api.thoth.test.pub/', $httpClient, 'secret_token');

        $contributorId = $thothMutation->run($graphQl);

        $this->assertEquals(json_decode($body)->data->createContributor->contributorId, $contributorId);
    }
}
