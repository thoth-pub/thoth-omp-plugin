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
    private $publicationService;
    private $languageService;
    private $subjectService;

    public function __construct($bookService, $contributionService, $publicationService, $languageService, $subjectService)
    {
        $this->bookService = $bookService;
        $this->contributionService = $contributionService;
        $this->publicationService = $publicationService;
        $this->languageService = $languageService;
        $this->subjectService = $subjectService;
    }

    public function synchronize($publication, $thothWorkId)
    {
        $warnings = [];
        $warning = $this->bookService->update($publication, $thothWorkId, true);
        if ($warning) {
            $warnings[] = $warning;
        }
        $this->contributionService->synchronizeByPublication($publication, $thothWorkId);
        if ($this->publicationService->synchronizeByPublication($publication, $thothWorkId)) {
            $warnings[] = 'plugins.generic.thoth.synchronize.activeWorkPublicationDeletionsSkipped';
        }
        $this->languageService->synchronizeByPublication($publication, $thothWorkId);
        $this->subjectService->synchronizeByPublication($publication, $thothWorkId);
        return $warnings;
    }
}
