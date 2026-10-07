<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothWorkLinkService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkLinkService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Check whether a locally linked Work still exists in Thoth
 */

namespace APP\plugins\generic\thoth\classes\services;

use APP\plugins\generic\thoth\classes\repositories\ThothWorkRepository;

class ThothWorkLinkService
{
    public function __construct(private ThothWorkRepository $repository)
    {
    }

    public function getStatus(string $thothWorkId): ?string
    {
        return $this->repository->findById($thothWorkId)?->getWorkStatus();
    }
}
