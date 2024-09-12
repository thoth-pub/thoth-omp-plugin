<?php

/**
 * @file plugins/generic/thoth/tests/thoth/ThothQueryTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothQueryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothQuery
 *
 * @brief Test class for the ThothQuery class
 */

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.lib.thothAPI.ThothGraphQL');
import('plugins.generic.thoth.lib.thothAPI.ThothQuery');
import('plugins.generic.thoth.lib.thothAPI.ThothQueryFields');
import('plugins.generic.thoth.lib.thothAPI.models.ThothContributor');

class ThothQueryTest extends PKPTestCase
{
    public function testPrepareQuery()
    {
        $expectedQuery = 'query{' .
            'contributors(' .
                'limit:100' .
                ',offset:0,' .
                'filter:"John",' .
                'order:{' .
                    'field:FIRST_NAME,' .
                    'direction:ASC' .
                '}' .
            '){' .
                'contributorId,' .
                'firstName,' .
                'lastName,' .
                'fullName,' .
                'orcid,' .
                'website' .
            '}' .
        '}';

        $params = [
            'limit:100',
            'offset:0',
            'filter:"John"',
            'order:{field:FIRST_NAME,direction:ASC}'
        ];

        $contributor = new ThothContributor();

        $thothQuery = new ThothQuery('contributors', $params, ThothQueryFields::contributor());
        $query = $thothQuery->prepare();

        $this->assertEquals($expectedQuery, $query);
    }

    public function testExecuteQuery()
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

        $params = [
            'limit:100',
            'offset:0'
        ];

        $contributor = new ThothContributor();
        $thothQuery = new ThothQuery('contributors', $params, ThothQueryFields::contributor());

        $body = file_get_contents(__DIR__ . '/../fixtures/contributors.json');
        $mock = new MockHandler([
            new Response(200, [], $body)
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $graphql = new ThothGraphQl('https://api.thoth.test.pub/', $httpClient);
        $contributors = $thothQuery->run($graphql);

        $this->assertEquals($expectedContributors, $contributors);
    }
}
