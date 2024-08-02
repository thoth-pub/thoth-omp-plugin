<?php

/**
 * @file plugins/generic/thoth/thoth/ThothQuery.inc.php
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

    private $fields;

    private $params;

    public function __construct($queryName, $fields, $params)
    {
        $this->queryName = $queryName;
        $this->fields = implode(',', $fields);
        $this->params = $this->prepareParams($params);
    }

    private function prepareParams($params)
    {
        return implode(
            ',',
            array_map(
                fn ($key, $value) => is_array($value) ?
                    sprintf(
                        '%s:{%s}',
                        $key,
                        implode(',', array_map(
                            fn ($a, $b) => sprintf('%s:%s', $a, $b),
                            array_keys($value),
                            array_values($value)
                        ))
                    ) :
                    sprintf('%s:%s', $key, ($key == 'filter') ? json_encode($value) : $value),
                array_keys($params),
                array_values($params)
            )
        );
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
