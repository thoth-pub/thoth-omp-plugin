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
    private $singletons = [];
    private $instances = [];

    public function set($id, $factory)
    {
        $this->bindings[$id] = $factory;
        unset($this->singletons[$id], $this->instances[$id]);
    }

    public function singleton($id, $factory)
    {
        $this->bindings[$id] = $factory;
        $this->singletons[$id] = true;
        unset($this->instances[$id]);
    }

    public function get($id)
    {
        if (!isset($this->bindings[$id])) {
            throw new Exception("Target binding \"{$id}\" does not exist.");
        }

        $factory = $this->bindings[$id];

        if (!isset($this->singletons[$id])) {
            return $factory($this);
        }

        if (!array_key_exists($id, $this->instances)) {
            $this->instances[$id] = $factory($this);
        }

        return $this->instances[$id];
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
