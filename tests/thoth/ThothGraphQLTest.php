<?php

/**
 * @file plugins/generic/thoth/tests/thoth/ThothGraphQLTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothGraphQLTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothGraphQL
 *
 * @brief Test class for the ThothGraphQL class
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.ThothAuthenticator');
import('plugins.generic.thoth.thoth.ThothGraphQL');

class ThothGraphQLTest extends PKPTestCase
{
    public function testSendRequest()
    {
        $body = '{"data":{"createContributor":{"contributorId":"abcd1234-e5f6-g7h8-i9j0-a1b2c3d4e5f6"}}}';
        $mock = new MockHandler([
            new Response(200, [], $body)
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $query = '
            mutation {
                createContributor(
                    data: {
                        firstName: "Abdullah",
                        lastName: "Mirzoev",
                        fullName: "Abdullah Mirzoev"
                    }
                ) {
                    contributorId
                }
            }
        ';

        $thothGraphQL = new ThothGraphQL('https://api.thoth.test.pub/', $httpClient, 'secret_token');
        $returnValue = $thothGraphQL->execute($query);
        $this->assertEquals(json_decode($body, true)['data'], $returnValue);
    }

    public function testRequestWithError()
    {
        $mockHandler = new MockHandler([
            new RequestException(
                'Client error',
                new Request('POST', 'https://api.thoth.test.pub/graphql'),
                new Response(
                    400,
                    [],
                    '{"errors":[{
                        "message":"Invalid value for argument \"data\", expected type \"NewContributor!\"",
                        "locations":[{"line":4,"column":21}]
                    }]}'
                )
            )
        ]);
        $guzzleClient = new Client(['handler' => $mockHandler]);

        $this->expectException(ThothException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Failed to send the request to Thoth: Invalid value for argument "data", expected type "NewContributor!"');

        $query = '
            mutation {
                createContributor(
                    data: {
                        givenName: "Abdullah",
                        "lastName": "Mirzoev",
                        "fullName": "Abdullah Mirzoev"
                    }
                ) {
                    contributorId
                }
            }
        ';

        $thothGraphQL = new ThothGraphQL('https://api.thoth.test.pub/', $guzzleClient, 'secret_token');
        $returnValue = $thothGraphQL->execute($query);
    }

    public function testResponseWithError()
    {
        $mockHandler = new MockHandler([
            new RequestException(
                'Client error',
                new Request('POST', 'https://api.thoth.test.pub/graphql'),
                new Response(
                    200,
                    [],
                    '{
                        "data":null,
                        "errors":[
                            {"message":"Invalid ORCID ID.",
                            "locations":[{"line":2,"column":17}],
                            "path":["createContributor"]}
                        ]
                    }'
                )
            )
        ]);
        $guzzleClient = new Client(['handler' => $mockHandler]);

        $this->expectException(ThothException::class);
        $this->expectExceptionCode(200);
        $this->expectExceptionMessage('Failed to send the request to Thoth: Invalid ORCID ID.');

        $query = '
            mutation {
                createContributor(
                    data: {
                        givenName: "Abdullah",
                        lastName: "Mirzoev",
                        fullName: "Abdullah Mirzoev",
                        orcid: "0000-0000-1245-5678"
                    }
                ) {
                    contributorId
                }
            }
        ';

        $thothGraphQL = new ThothGraphQL('https://api.thoth.test.pub/', $guzzleClient, 'secret_token');
        $returnValue = $thothGraphQL->execute($query);
    }
}
