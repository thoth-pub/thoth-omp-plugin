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

        if (!$this->plugin->getEnabled() || $page !== 'thoth') {
            return false;
        }

        if ($op === 'register') {
            $this->plugin->import('controllers/modal/RegisterHandler');
            define('HANDLER_CLASS', 'RegisterHandler');
            return true;
        }

        if ($op === 'index') {
            $this->plugin->import('pages/thoth/ThothHandler');
            define('HANDLER_CLASS', 'ThothHandler');
            return true;
        }

        return false;
    }
}
