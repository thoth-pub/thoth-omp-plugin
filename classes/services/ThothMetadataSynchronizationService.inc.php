<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothMetadataSynchronizationService.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothMetadataSynchronizationService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Coordinates the synchronization of registered metadata with Thoth
 */

class ThothMetadataSynchronizationService
{
    private $bookService;
    private $contributionService;

    public function __construct($bookService, $contributionService)
    {
        $this->bookService = $bookService;
        $this->contributionService = $contributionService;
    }

    public function synchronize($publication, $thothWorkId)
    {
        $warning = $this->bookService->update($publication, $thothWorkId, true);
        $this->contributionService->synchronizeByPublication($publication, $thothWorkId);
        return $warning;
    }
}
