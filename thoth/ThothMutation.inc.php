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
 * @brief Prepares and performs Thoth mutations
 */

class ThothMutation
{
    private $mutationName;

    private $data;

    private $enumeratedValues;

    private $returnValue;

    public function __construct($mutationName, $mutationObject)
    {
        $this->mutationName = $mutationName;
        $this->data = $mutationObject->getData();
        $this->enumeratedValues = $mutationObject->getEnumeratedValues();
        $this->returnValue = $mutationObject->getReturnValue();
    }

    private function prepare()
    {
        $fields = [];
        foreach ($this->data as $attribute => $value) {
            $fields[] = sprintf(
                '%s: %s',
                $attribute,
                $this->sanitize($attribute, $value)
            );
        }

        $mutation = sprintf(
            'mutation {
                %s(
                    data: {%s}
                ) {
                    %s
                }
            }',
            $this->mutationName,
            implode("\n", $fields),
            $this->returnValue
        );

        return $mutation;
    }

    private function sanitize($attribute, $value)
    {
        return in_array($attribute, $this->enumeratedValues) ? $value : json_encode($value);
    }

    public function run($graphQlClient)
    {
        $mutation = $this->prepare();
        $result = $graphQlClient->execute($mutation);
        return $result[$this->mutationName][$this->returnValue];
    }
}
