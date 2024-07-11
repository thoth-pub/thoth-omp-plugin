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

use GuzzleHttp\Exception\RequestException;

import('plugins.generic.thoth.thoth.exceptions.ThothException');

class ThothAuthenticator
{
    private $httpClient;

    private $endpoint;

    private $payload;

    public const THOTH_AUTH_ENDPOINT = 'account/login';

    public function __construct($thothEndpoint, $httpClient, $email, $password)
    {
        $this->httpClient = $httpClient;
        $this->endpoint = $thothEndpoint . self::THOTH_AUTH_ENDPOINT;
        $this->payload = ['email' => $email, 'password' => $password];
    }

    public function getToken()
    {
        try {
            $response = $this->httpClient->post($this->endpoint, ['json' => $this->payload]);
        } catch (RequestException $e) {
            $returnMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $returnMessage = $e->getResponse()->getBody()->getContents();
            }
            throw new ThothException($returnMessage, $e->getCode());
        }

        return json_decode($response->getBody())->token;
    }
}
