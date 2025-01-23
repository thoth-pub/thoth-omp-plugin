<?php

/**
 * @file plugins/generic/thoth/tests/classes/container/ContainerProvider.inc.php
 *
 * Copyright (c) 2025 Lepidus Tecnologia
 * Copyright (c) 2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContainerProvider
 * @ingroup plugins_generic_thoth
 *
 * @brief Interface to package container bindings
 */

interface ContainerProvider
{
    public function register($container);
}
