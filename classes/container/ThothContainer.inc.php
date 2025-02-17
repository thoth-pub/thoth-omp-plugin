<?php

/**
 * @file plugins/generic/thoth/tests/classes/container/ThothContainer.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContainer
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Singleton implementation for dependency injection container
 */

import('plugins.generic.thoth.classes.container.Container');
import('plugins.generic.thoth.classes.container.providers.ThothRepositoryProvider');

class ThothContainer extends Container
{
    private static $instance = null;

    private function __construct()
    {
        $this->register(new ThothRepositoryProvider());
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
