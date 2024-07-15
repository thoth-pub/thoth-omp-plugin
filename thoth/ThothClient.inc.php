<?php

/**
 * @file plugins/generic/thoth/thoth/ThothClient.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothClient
 * @ingroup plugins_generic_thoth
 *
 * @brief Client for Thoth's API
 */

import('plugins.generic.thoth.thoth.ThothAuthenticator');
import('plugins.generic.thoth.thoth.ThothMutation');
import('plugins.generic.thoth.thoth.ThothGraphQL');

class ThothClient
{
    private $token;

    private $endpoint;

    private $httpClient;

    public const THOTH_ENDPOINT = 'https://api.thoth.pub/';

    public function __construct($endpoint = self::THOTH_ENDPOINT, $httpClient = null)
    {
        $this->endpoint = $endpoint;
        $this->httpClient = $httpClient ?? Application::get()->getHttpClient();
    }

    public function login($email, $password)
    {
        $authenticator = new ThothAuthenticator($this->endpoint, $this->httpClient, $email, $password);
        $this->token = $authenticator->getToken();
    }

    public function createWork($work)
    {
        $mutation = new ThothMutation('createWork', $work);
        $graphql = new ThothGraphQL($this->endpoint, $this->httpClient, $this->token);
        return $mutation->run($graphql);
    }

    public function createContributor($contributor)
    {
        $mutation = new ThothMutation('createContributor', $contributor);
        $graphql = new ThothGraphQL($this->endpoint, $this->httpClient, $this->token);
        return $mutation->run($graphql);
    }

    public function createContribution($contribution)
    {
        $mutation = new ThothMutation('createContribution', $contribution);
        $graphql = new ThothGraphQL($this->endpoint, $this->httpClient, $this->token);
        return $mutation->run($graphql);
    }
}
