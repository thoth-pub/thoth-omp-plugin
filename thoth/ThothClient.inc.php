<?php

/**
 * @file plugins/generic/thoth/thoth/ThothClient.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothClient
 * @ingroup plugins_generic_thoth
 *
 * @brief Client for Thoth's API
 */

import('plugins.generic.thoth.thoth.ThothAuthenticator');
import('plugins.generic.thoth.thoth.ThothGraphQL');
import('plugins.generic.thoth.thoth.ThothMutation');
import('plugins.generic.thoth.thoth.ThothQuery');

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
        return $this->mutation('createWork', $work);
    }

    public function createContributor($contributor)
    {
        return $this->mutation('createContributor', $contributor);
    }

    public function createContribution($contribution)
    {
        return $this->mutation('createContribution', $contribution);
    }

    public function createWorkRelation($workRelation)
    {
        return $this->mutation('createWorkRelation', $workRelation);
    }

    public function createPublication($publication)
    {
        return $this->mutation('createPublication', $publication);
    }

    public function createLocation($location)
    {
        return $this->mutation('createLocation', $location);
    }

    public function createSubject($subject)
    {
        return $this->mutation('createSubject', $subject);
    }

    public function createLanguage($language)
    {
        return $this->mutation('createLanguage', $language);
    }

    public function createReference($reference)
    {
        return $this->mutation('createReference', $reference);
    }

    public function contribution($contributionId)
    {
        $this->addParameter($params, 'contributionId', $contributionId, true);

        return $this->query('contribution', $params, ThothContribution::class);
    }

    public function contributions($limit = 100, $offset = 0, $order = [], $publishers = [], $contributionTypes = [])
    {
        $this->addParameter($params, 'limit', $limit);
        $this->addParameter($params, 'offset', $offset);
        $this->addParameter($params, 'order', $order);
        $this->addParameter($params, 'publishers', $publishers, true);
        $this->addParameter($params, 'contributionTypes', $contributionTypes, true);

        return $this->query('contributions', $params, ThothContributor::class);
    }

    public function contributor($contributorId)
    {
        $this->addParameter($params, 'contributorId', $contributorId, true);

        return $this->query('contributor', $params, ThothContributor::class);
    }

    public function contributors($limit = 100, $offset = 0, $filter = '', $order = [])
    {
        $this->addParameter($params, 'limit', $limit);
        $this->addParameter($params, 'offset', $offset);
        $this->addParameter($params, 'filter', $filter, true);
        $this->addParameter($params, 'order', $order);

        return $this->query('contributors', $params, ThothContributor::class);
    }

    public function institution($institutionId)
    {
        $this->addParameter($params, 'institutionId', $institutionId, true);

        return $this->query('institution', $params, ThothInstitution::class);
    }

    public function institutions($limit = 100, $offset = 0, $filter = '', $order = [])
    {
        $this->addParameter($params, 'limit', $limit);
        $this->addParameter($params, 'offset', $offset);
        $this->addParameter($params, 'filter', $filter, true);
        $this->addParameter($params, 'order', $order);

        return $this->query('institutions', $params, ThothInstitution::class);
    }

    public function imprint($imprintId)
    {
        $this->addParameter($params, 'imprintId', $imprintId, true);

        return $this->query('imprint', $params, ThothImprint::class);
    }

    private function mutation($name, $data)
    {
        $mutation = new ThothMutation($name, $data);
        $graphql = new ThothGraphQL($this->endpoint, $this->httpClient, $this->token);
        return $mutation->run($graphql);
    }

    private function query($name, $params, $queryClass)
    {
        $query = new ThothQuery($name, $params, $queryClass);
        $graphql = new ThothGraphQL($this->endpoint, $this->httpClient);
        return $query->run($graphql);
    }

    private function addParameter(&$params, $key, $value, $enclosed = false)
    {
        if ($value == '' || (is_array($value) && empty($value))) {
            return;
        }

        $params = $params ?? [];

        if (is_array($value)) {
            $params[] = (array_values($value) !== $value) ?
                sprintf(
                    '%s:{%s}',
                    $key,
                    implode(',', array_map(function ($subKey, $subValue) {
                        return sprintf('%s:%s', $subKey, $subValue);
                    }, array_keys($value), array_values($value)))
                ) :
                sprintf(
                    '%s:[%s]',
                    $key,
                    implode(',', $enclosed ? array_map([$this, 'encloseValue'], $value) : $value)
                );
            return;
        }

        $params[] = sprintf('%s:%s', $key, $enclosed ? $this->encloseValue($value) : $value);
        return;
    }

    private function encloseValue($value)
    {
        return json_encode($value);
    }
}
