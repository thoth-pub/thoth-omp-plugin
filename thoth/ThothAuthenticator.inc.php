<?php

use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;

class ThothAuthenticator
{
    private $httpClient;

    private $thothAuthUrl;

    private $json;

    public const THOTH_AUTH_ENDPOINT = 'account/login';

    public function __construct($httpClient, $thothApiUrl, $email, $password)
    {
        $this->httpClient = $httpClient;
        $this->thothAuthUrl = $thothApiUrl . self::THOTH_AUTH_ENDPOINT;
        $this->json = ['email' => $email, 'password' => $password];
    }

    public function getToken()
    {
        $response = $this->httpClient->request('GET', $this->thothAuthUrl, ['json' => $this->json]);

        return json_decode($response->getBody())->token;
    }
}
