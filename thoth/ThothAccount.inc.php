<?php

/**
 * @file plugins/generic/thoth/thoth/ThothAccount.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAccount
 * @ingroup plugins_generic_thoth
 *
 * @brief Thoth account features
 */

use GuzzleHttp\Exception\RequestException;

import('plugins.generic.thoth.thoth.exceptions.ThothException');

class ThothAccount
{
    private $httpClient;

    private $authEndpoint;

    private $accountEndpoint;

    public const THOTH_ACCOUNT_ENDPOINT = 'account';

    public const THOTH_AUTH_ENDPOINT = 'account/login';

    public function __construct($thothEndpoint, $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->accountEndpoint = $thothEndpoint . self::THOTH_ACCOUNT_ENDPOINT;
        $this->authEndpoint = $thothEndpoint . self::THOTH_AUTH_ENDPOINT;
    }

    public function getToken($email, $password)
    {
        $payload = ['email' => $email, 'password' => $password];

        try {
            $response = $this->httpClient->post($this->authEndpoint, ['json' => $payload]);
        } catch (RequestException $e) {
            $returnMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $returnMessage = $e->getResponse()->getBody()->getContents();
            }
            throw new ThothException($returnMessage, $e->getCode());
        }

        return json_decode($response->getBody())->token;
    }

    public function getDetails($token)
    {
        try {
            $response = $this->httpClient->get(
                $this->accountEndpoint,
                ['headers' => ['Authorization' => 'Bearer ' . $token]]
            );
        } catch (RequestException $e) {
            $returnMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $returnMessage = $e->getResponse()->getBody()->getContents();
            }
            throw new ThothException($returnMessage, $e->getCode());
        }

        return json_decode($response->getBody(), true);
    }
}
