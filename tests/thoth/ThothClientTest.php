<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.thoth.ThothClient');

class ThothClientTest extends PKPTestCase
{
    public function testLoginWithInvalidCredentials()
    {
        $mockHandler = new MockHandler([
            new ClientException(
                'Error Communicating with Server',
                new Request('POST', 'https://api.thoth.pub/account/login'),
                new Response(401, [], 'Invalid credentials')
            )
        ]);
        $guzzleClient = new Client(['handler' => $mockHandler]);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('Invalid credentials');

        $thothClient = new ThothClient($guzzleClient);
        $thothClient->login('user72581@mailinator.com', 'uys9ag9s');
    }
}
