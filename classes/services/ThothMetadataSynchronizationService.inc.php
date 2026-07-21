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

    public function __construct($bookService)
    {
        $this->bookService = $bookService;
    }

    public function synchronize($publication, $thothWorkId)
    {
        return $this->bookService->update($publication, $thothWorkId);
    }
}
