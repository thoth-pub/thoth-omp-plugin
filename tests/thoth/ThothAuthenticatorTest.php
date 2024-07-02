<?php

/**
 * @file plugins/generic/thoth/tests/thoth/ThothAuthenticatorTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAuthenticatorTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothAuthenticator
 *
 * @brief Test class for the ThothAuthenticator class
 */

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.ThothAuthenticator');

class ThothAuthenticatorTest extends PKPTestCase
{
    public function testGetTokenWithValidCredentials()
    {
        $mockHandler = new MockHandler([
            new Response(200, [], '{"token": "xxxxx.yyyyy.zzzzz"}'),
        ]);
        $guzzleClient = new Client(['handler' => $mockHandler]);

        $authenticator = new ThothAuthenticator(
            $guzzleClient,
            'https://api.thoth.test.pub',
            'johndoe@mailinator.com',
            'secret123'
        );
        $token = $authenticator->getToken();

        $this->assertEquals('xxxxx.yyyyy.zzzzz', $token);
    }
}
