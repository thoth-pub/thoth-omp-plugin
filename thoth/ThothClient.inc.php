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

class ThothClient
{
    private $token;

    private $endpoint;

    public const THOTH_ENDPOINT = 'https://api.thoth.pub/';

    public function __construct($endpoint = self::THOTH_ENDPOINT)
    {
        $this->endpoint = $endpoint;
    }

    public function login($email, $password)
    {
        $authenticator = new ThothAuthenticator($this->endpoint, $email, $password);
        $this->token = $authenticator->getToken();
    }

    public function createContributor($contributor)
    {
        $mutation = new ThothMutation('createContributor', $contributor);
        $graphQl = new ThothGraphQL($this->endpoint, $this->token);
        return $mutation->run($graphQl);
    }
}
