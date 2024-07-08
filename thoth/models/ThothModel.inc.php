<?php

/**
 * @file plugins/generic/thoth/thoth/models/ThothModel.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothModel
 * @ingroup plugins_generic_thoth
 *
 * @brief Abstract class for Thoth models.
 */

abstract class ThothModel
{
    abstract public function getId();

    abstract public function setId($id);

    abstract public function getReturnValue();

    public function getData()
    {
        $reflector = new ReflectionClass($this);
        $properties = $reflector->getProperties(ReflectionProperty::IS_PRIVATE);

        $attributes = [];

        foreach ($properties as $property) {
            $property->setAccessible(true);
            if (!$value = $property->getValue($this)) {
                continue;
            }
            $attributes[$property->getName()] = $value;
        }

        return $attributes;
    }
}
