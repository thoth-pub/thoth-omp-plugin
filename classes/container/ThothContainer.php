<?php

/**
 * @file plugins/generic/thoth/classes/container/ThothContainer.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContainer
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Dependency injection container scoped to an OMP context
 */

namespace APP\plugins\generic\thoth\classes\container;

use APP\core\Application;
use APP\plugins\generic\thoth\classes\container\providers\ThothRepositoryProvider;
use APP\plugins\generic\thoth\classes\container\providers\ThothServiceProvider;

class ThothContainer extends Container
{
    private static array $instancesByContext = [];

    private function __construct(int $contextId)
    {
        $this->register(new ThothRepositoryProvider($contextId));
        $this->register(new ThothServiceProvider());
    }

    public static function getInstance(?int $contextId = null): self
    {
        $contextId ??= (int) Application::get()->getRequest()->getContext()?->getId();
        return self::$instancesByContext[$contextId] ??= new self($contextId);
    }
}
