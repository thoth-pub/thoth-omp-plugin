<?php

/**
 * @file plugins/generic/thoth/tests/thoth/ThothAccountTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAccountTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothAccount
 *
 * @brief Test class for the ThothAccount class
 */

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.lib.thothAPI.ThothAccount');

class ThothAccountTest extends PKPTestCase
{
    public function testGetTokenWithValidCredentials()
    {
        $mockHandler = new MockHandler([
            new Response(200, [], '{"token": "xxxxx.yyyyy.zzzzz"}'),
        ]);
        $guzzleClient = new Client(['handler' => $mockHandler]);

        $account = new ThothAccount(
            'https://api.thoth.test.pub',
            $guzzleClient,
        );
        $token = $account->getToken('johndoe@mailinator.com', 'secret123');

        $this->assertEquals('xxxxx.yyyyy.zzzzz', $token);
    }

    public function testGetTokenWithInvalidCredentials()
    {
        $this->expectException(ThothException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('Failed to send the request to Thoth: Invalid credentials');

        $mockHandler = new MockHandler([
            new RequestException(
                'Invalid credentials',
                new Request('POST', 'https://api.thoth.test.pub/account/login'),
                new Response(401, [], 'Invalid credentials')
            )
        ]);
        $guzzleClient = new Client(['handler' => $mockHandler]);

        $account = new ThothAccount(
            'https://api.thoth.test.pub',
            $guzzleClient,
        );
        $token = $account->getToken('botuser@mailinator.com', 'wrong_password');
    }

    public function testGetAccountDetails()
    {
        $expectedAccountDetails = [
            'accountId' => '49e98000-8bd5-4959-8d4a-0b0754afe5d4',
            'name' => 'Thoth',
            'surname' => 'Publisher',
            'email' => 'thoth_publisher@mailinator.com',
            'token' => 'xxxxxyyyyyzzzzz',
            'createdAt' => '2024-07-15T19:37:23.215914Z',
            'updatedAt' => '2024-09-03T19:04:44.266144Z',
            'resourceAccess' => [
                'isSuperuser' => true,
                'isBot' => false,
                'linkedPublishers' => [
                    [
                        'publisherId' => '7e0435ab-a0a6-4dca-8503-ffc1dcaa9f9d',
                        'isAdmin' => true
                    ]
                ]
            ]
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], file_get_contents(__DIR__ . '/../fixtures/accountDetails.json')),
        ]);
        $guzzleClient = new Client(['handler' => $mockHandler]);

        $account = new ThothAccount(
            'https://api.thoth.test.pub',
            $guzzleClient,
        );
        $details = $account->getDetails('xxxxxyyyyyzzzzz');

        $this->assertEquals($expectedAccountDetails, $details);
    }
}
