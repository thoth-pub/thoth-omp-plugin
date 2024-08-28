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

    public const THOTH_SANDBOX_ENDPOINT = 'http://localhost:8000/';

    public function __construct($sandbox = false, $httpClient = null)
    {
        $this->endpoint = $sandbox ? self::THOTH_SANDBOX_ENDPOINT : self::THOTH_ENDPOINT;
        $this->httpClient = $httpClient ?? Application::get()->getHttpClient();
    }

    public function login($email, $password)
    {
        $authenticator = new ThothAuthenticator($this->endpoint, $this->httpClient, $email, $password);
        $this->token = $authenticator->getToken();
    }

    public function mutation($name, $data, $returnValue = null, $enumeratedFields = [], $nested = true)
    {
        if ($data instanceof ThothModel) {
            $enumeratedFields = $data->getEnumeratedValues();
            $returnValue = $data->getReturnValue();
            $data = $data->getData();
        }
        $mutation = new ThothMutation($name, $data, $returnValue, $enumeratedFields, $nested);
        $graphql = new ThothGraphQL($this->endpoint, $this->httpClient, $this->token);
        return $mutation->run($graphql);
    }

    public function query($name, $params, $fields)
    {
        $query = new ThothQuery($name, $params, $fields);
        $graphql = new ThothGraphQL($this->endpoint, $this->httpClient);
        return $query->run($graphql);
    }

    public function createAffiliation($affiliation)
    {
        return $this->mutation('createAffiliation', $affiliation);
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

    public function updateWork($work)
    {
        return $this->mutation('updateWork', $work);
    }

    public function deleteWork($workId)
    {
        $data = ['workId' => $workId];
        return $this->mutation('deleteWork', $data, 'workId', [], false);
    }

    public function deleteContribution($contributionId)
    {
        $data = ['contributionId' => $contributionId];
        return $this->mutation('deleteContribution', $data, 'contributionId', [], false);
    }

    public function deleteSubject($subjectId)
    {
        $data = ['subjectId' => $subjectId];
        return $this->mutation('deleteSubject', $data, 'subjectId', [], false);
    }

    public function contribution($contributionId)
    {
        $this->addParameter($params, 'contributionId', $contributionId, true);
        $fields = $this->getFields(ThothContribution::class);

        return $this->query('contribution', $params, $fields);
    }

    public function contributions($limit = 100, $offset = 0, $order = [], $publishers = [], $contributionTypes = [])
    {
        $this->addParameter($params, 'limit', $limit);
        $this->addParameter($params, 'offset', $offset);
        $this->addParameter($params, 'order', $order);
        $this->addParameter($params, 'publishers', $publishers, true);
        $this->addParameter($params, 'contributionTypes', $contributionTypes, true);
        $fields = $this->getFields(ThothContribution::class);

        return $this->query('contributions', $params, $fields);
    }

    public function contributor($contributorId)
    {
        $this->addParameter($params, 'contributorId', $contributorId, true);
        $fields = $this->getFields(ThothContributor::class);

        return $this->query('contributor', $params, $fields);
    }

    public function contributors($limit = 100, $offset = 0, $filter = '', $order = [])
    {
        $this->addParameter($params, 'limit', $limit);
        $this->addParameter($params, 'offset', $offset);
        $this->addParameter($params, 'filter', $filter, true);
        $this->addParameter($params, 'order', $order);
        $fields = $this->getFields(ThothContributor::class);

        return $this->query('contributors', $params, $fields);
    }

    public function institution($institutionId)
    {
        $this->addParameter($params, 'institutionId', $institutionId, true);
        $fields = $this->getFields(ThothInstitution::class);

        return $this->query('institution', $params, $fields);
    }

    public function institutions($limit = 100, $offset = 0, $filter = '', $order = [])
    {
        $this->addParameter($params, 'limit', $limit);
        $this->addParameter($params, 'offset', $offset);
        $this->addParameter($params, 'filter', $filter, true);
        $this->addParameter($params, 'order', $order);
        $fields = $this->getFields(ThothInstitution::class);

        return $this->query('institutions', $params, $fields);
    }

    public function imprint($imprintId)
    {
        $this->addParameter($params, 'imprintId', $imprintId, true);
        $fields = $this->getFields(ThothImprint::class);

        return $this->query('imprint', $params, $fields);
    }

    public function imprints($limit = 100, $offset = 0, $filter = '', $order = [], $publishers = [])
    {
        $this->addParameter($params, 'limit', $limit);
        $this->addParameter($params, 'offset', $offset);
        $this->addParameter($params, 'filter', $filter, true);
        $this->addParameter($params, 'order', $order);
        $this->addParameter($params, 'publishers', $publishers, true);
        $fields = $this->getFields(ThothImprint::class);

        return $this->query('imprints', $params, $fields);
    }

    public function publisher($publisherId)
    {
        $this->addParameter($params, 'publisherId', $publisherId, true);
        $fields = $this->getFields(ThothPublisher::class);

        return $this->query('publisher', $params, $fields);
    }

    public function publishers($limit = 100, $offset = 0, $filter = '', $order = [], $publishers = [])
    {
        $this->addParameter($params, 'limit', $limit);
        $this->addParameter($params, 'offset', $offset);
        $this->addParameter($params, 'filter', $filter, true);
        $this->addParameter($params, 'order', $order);
        $this->addParameter($params, 'publishers', $publishers, true);
        $fields = $this->getFields(ThothPublisher::class);

        return $this->query('publishers', $params, $fields);
    }

    public function work($workId)
    {
        $this->addParameter($params, 'workId', $workId, true);
        $fields = $this->getFields(ThothWork::class);

        return $this->query('work', $params, $fields);
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

    private function getFields($className)
    {
        $object = new $className();
        return $object->getProperties();
    }
}
