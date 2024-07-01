<?php

import('plugins.generic.thoth.thoth.ThothAuthenticator');

class ThothClient
{
    private $httpClient;

    private $token;

    public const THOTH_API_URL = 'https://api.thoth.pub';

    public function __construct($httpClient = null)
    {
        $this->httpClient = $httpClient ?? Application::get()->getHttpClient();
    }

    public function login($email, $password)
    {
        $authenticator = new ThothAuthenticator($this->httpClient, self::THOTH_API_URL, $email, $password);
        $this->token = $authenticator->getToken();
    }
}
