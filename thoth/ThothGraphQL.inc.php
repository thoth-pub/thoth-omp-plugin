<?php

/**
 * @file plugins/generic/thoth/thoth/ThothGraphQL.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothGraphQL
 * @ingroup plugins_generic_thoth
 *
 * @brief Executes Thoth's GraphQL requests
 */

use GuzzleHttp\Exception\RequestException;

class ThothGraphQL
{
    private $endpoint;

    private $httpClient;

    private $token;

    public const THOTH_GRAPHQL_ENDPOINT = 'graphql';

    public function __construct($thothEndpoint, $token = null, $httpClient = null)
    {
        $this->httpClient = $httpClient ?? Application::get()->getHttpClient();
        $this->endpoint = $thothEndpoint . self::THOTH_GRAPHQL_ENDPOINT;
        $this->token = $token;
    }

    public function execute($query, $variables = null)
    {
        $payload = [
            'query' => $query,
        ];

        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'json' => $payload
        ];

        if ($this->token) {
            $options['headers']['Authorization'] = 'Bearer ' . $this->token;
        }

        try {
            $response = $this->httpClient->post($this->endpoint, $options);
        } catch (RequestException $e) {
            throw new ThothException($this->getReturnMessage($e), $e->getCode());
        }

        return json_decode($response->getBody()->getContents(), true)['data'];
    }

    private function getReturnMessage($exception)
    {
        $returnMessage = $exception->getMessage();
        if ($exception->hasResponse()) {
            $errors = json_decode($exception->getResponse()->getBody())->errors;
            $error = array_shift($errors);
            $returnMessage = $error->message;
        }
        return $returnMessage;
    }
}
