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
    private ThothSubjectService $subjectService;
    private ThothReferenceService $referenceService;
    private ThothWorkRelationService $workRelationService;

    public function __construct(
        ThothBookService $bookService,
        ThothContributionService $contributionService,
        ThothPublicationService $publicationService,
        ThothLanguageService $languageService,
        ThothSubjectService $subjectService,
        ThothReferenceService $referenceService,
        ThothWorkRelationService $workRelationService
    ) {
        $this->bookService = $bookService;
        $this->contributionService = $contributionService;
        $this->publicationService = $publicationService;
        $this->languageService = $languageService;
        $this->subjectService = $subjectService;
        $this->referenceService = $referenceService;
        $this->workRelationService = $workRelationService;
    }

    public function synchronize($publication, string $thothWorkId): array
    {
        $warnings = $this->bookService->update($publication, $thothWorkId, true);
        $warnings = array_merge(
            $warnings,
            $this->contributionService->synchronizeByPublication($publication, $thothWorkId),
            $this->publicationService->synchronizeByPublication($publication, $thothWorkId),
            $this->languageService->synchronizeByPublication($publication, $thothWorkId),
            $this->subjectService->synchronizeByPublication($publication, $thothWorkId),
            $this->referenceService->synchronizeByPublication($publication, $thothWorkId),
            $this->workRelationService->synchronizeByPublication($publication, $thothWorkId)
        );
        return array_values(array_unique($warnings));
    }
}
