<?php

/**
 * @file plugins/generic/thoth/tests/classes/container/Container.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Container
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Simple dependency injection container implementation
 */

namespace APP\plugins\generic\thoth\classes\container;

use Exception;

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

    public function singletonClass($id, $className, array $dependencies = [])
    {
        $this->singleton($id, function ($container) use ($className, $dependencies) {
            return $container->make($className, $dependencies);
        });
    }

    public function make($className, array $dependencies = [])
    {
        $resolvedDependencies = [];

        foreach ($dependencies as $dependency) {
            if (is_string($dependency) && isset($this->bindings[$dependency])) {
                $resolvedDependencies[] = $this->get($dependency);
                continue;
            }

            if ($dependency instanceof \Closure) {
                $resolvedDependencies[] = $dependency($this);
                continue;
            }

            $resolvedDependencies[] = $dependency;
        }

        return new $className(...$resolvedDependencies);
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
