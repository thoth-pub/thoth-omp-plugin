<?php

/**
 * @file plugins/generic/thoth/classes/hooks/ThothPageHandler.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPageHandler
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Loads page handlers for Thoth plugin routes
 */

namespace APP\plugins\generic\thoth\classes\hooks;

use APP\plugins\generic\thoth\classes\handlers\modal\RegisterHandler;
use APP\plugins\generic\thoth\classes\handlers\pages\ThothHandler;
use PKP\plugins\GenericPlugin;

class ThothPageHandler
{
    private GenericPlugin $plugin;

    public function __construct(GenericPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function addHandlers($hookName, $args): bool
    {
        $page = $args[0];
        $op = $args[1];
        $handler = &$args[3];


        if (!$this->plugin->getEnabled() || $page !== 'thoth') {
            return false;
        }

        if ($op === 'register') {
            $handler = new RegisterHandler($this);
            return true;
        }

        if ($op === 'index') {
            $handler = new ThothHandler($this);
            return true;
        }

        return false;
    }
}
