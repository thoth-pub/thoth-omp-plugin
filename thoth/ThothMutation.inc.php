<?php

/**
 * @file plugins/generic/thoth/thoth/ThothMutation.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothMutation
 * @ingroup plugins_generic_thoth
 *
 * @brief Prepares and performs Thoth mutations
 */

class ThothMutation
{
    private $name;

    private $returnValue;

    private $mutation;

    public function __construct($name, $data, $returnValue, $enumeratedValues = [], $nested = true)
    {
        $this->name = $name;
        $this->returnValue = $returnValue;
        $this->mutation = $this->prepare($data, $enumeratedValues, $nested);
    }

    private function prepare($data, $enumeratedValues, $nested)
    {
        $mutationQuery = $nested ? 'mutation{%s(data:{%s}){%s}}' : 'mutation{%s(%s){%s}}';
        $fields = [];
        foreach ($data as $attribute => $value) {
            $fields[] = sprintf(
                '%s: %s',
                $attribute,
                $this->sanitize($attribute, $value, $enumeratedValues)
            );
        }

        $mutation = sprintf(
            $mutationQuery,
            $this->name,
            implode(',', $fields),
            $this->returnValue
        );

        return $mutation;
    }

    private function sanitize($attribute, $value, $enumeratedValues)
    {
        return in_array($attribute, $enumeratedValues) ? $value : json_encode($value);
    }

    public function run($graphQlClient)
    {
        $result = $graphQlClient->execute($this->mutation);
        return $result[$this->name][$this->returnValue];
    }
}
