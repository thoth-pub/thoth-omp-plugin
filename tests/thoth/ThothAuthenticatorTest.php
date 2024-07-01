<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
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
