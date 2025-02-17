<?php

/**
 * @file plugins/generic/thoth/tests/classes/container/Container.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Container
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Simple dependency injection container implementation
 */

class Container
{
    private $bindings = [];

    public function set($id, $factory)
    {
        $this->bindings[$id] = $factory;
    }

    public function get($id)
    {
        if (!isset($this->bindings[$id])) {
            throw new Exception("Target binding \"{$id}\" does not exist.");
        }

        $factory = $this->bindings[$id];

        return $factory($this);
    }

    public function backup($id)
    {
        return $this->bindings[$id];
    }

    public function register($containerProvider)
    {
        $containerProvider->register($this);
    }
}
