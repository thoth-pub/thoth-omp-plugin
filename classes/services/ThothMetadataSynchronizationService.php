<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothMetadataSynchronizationService.php
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

namespace APP\plugins\generic\thoth\classes\services;

class ThothMetadataSynchronizationService
{
    private ThothBookService $bookService;
    private ThothContributionService $contributionService;
    private ThothPublicationService $publicationService;
    private ThothLanguageService $languageService;

    public function __construct(
        ThothBookService $bookService,
        ThothContributionService $contributionService,
        ThothPublicationService $publicationService,
        ThothLanguageService $languageService
    ) {
        $this->bookService = $bookService;
        $this->contributionService = $contributionService;
        $this->publicationService = $publicationService;
        $this->languageService = $languageService;
    }

    public function synchronize($publication, string $thothWorkId): array
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
        return $warnings;
    }
}
