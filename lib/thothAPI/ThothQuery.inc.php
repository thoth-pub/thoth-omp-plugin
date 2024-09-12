<?php

/**
 * @file plugins/generic/thoth/lib/thothAPI/ThothQuery.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothQuery
 * @ingroup plugins_generic_thoth
 *
 * @brief Prepares and performs Thoth queries
 */

class ThothQuery
{
    private $queryName;

    private $params;

    private $fields;

    public function __construct($queryName, $params, $fields)
    {
        $this->queryName = $queryName;
        $this->params = implode(',', $params);
        $this->fields = $this->formatFields($fields);
    }

    private function formatFields($fields)
    {
        return implode(',', array_map(function ($key, $field) {
            return is_array($field) ? sprintf('%s{%s}', $key, $this->formatFields($field)) : $field;
        }, array_keys($fields), array_values($fields)));
    }

    public function prepare()
    {
        $query = sprintf(
            'query{%s(%s){%s}}',
            $this->queryName,
            $this->params,
            $this->fields
        );
        return $query;
    }

    public function run($graphqlClient)
    {
        $query = $this->prepare();
        $result = $graphqlClient->execute($query);
        return $result[$this->queryName];
    }
}
