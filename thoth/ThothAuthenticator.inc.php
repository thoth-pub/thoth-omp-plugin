<?php

/**
 * @file plugins/generic/thoth/thoth/ThothAuthenticator.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAuthenticator
 * @ingroup plugins_generic_thoth
 *
 * @brief Authenticate with Thoth API
 */

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
        try {
            $response = $this->httpClient->request('POST', $this->thothAuthUrl, ['json' => $this->json]);
        } catch (ClientException $e) {
            if ($e->getCode() == 401) {
                throw new Exception('Invalid credentials', 401);
            }
        }

        return json_decode($response->getBody())->token;
    }
}
