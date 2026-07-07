<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothBookRegistrationService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookRegistrationService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Coordinates full Thoth book registration workflows
 */

namespace APP\plugins\generic\thoth\classes\services;

use ThothApi\GraphQL\Enums\WorkStatus;

class ThothBookRegistrationService
{
    private $factory;
    private $repository;
    private ThothAbstractService $abstractService;
    private ThothContributionService $contributionService;
    private ThothLanguageService $languageService;
    private ThothPublicationService $publicationService;
    private ThothReferenceService $referenceService;
    private ThothSubjectService $subjectService;
    private ThothTitleService $titleService;
    private ThothWorkRelationService $workRelationService;
    private $originalThothBook;
    private $registeredEntryId;

    public function __construct(
        $factory,
        $repository,
        ThothAbstractService $abstractService,
        ThothContributionService $contributionService,
        ThothLanguageService $languageService,
        ThothPublicationService $publicationService,
        ThothReferenceService $referenceService,
        ThothSubjectService $subjectService,
        ThothTitleService $titleService,
        ThothWorkRelationService $workRelationService
    ) {
        $this->factory = $factory;
        $this->repository = $repository;
        $this->abstractService = $abstractService;
        $this->contributionService = $contributionService;
        $this->languageService = $languageService;
        $this->publicationService = $publicationService;
        $this->referenceService = $referenceService;
        $this->subjectService = $subjectService;
        $this->titleService = $titleService;
        $this->workRelationService = $workRelationService;
    }

    public function register($publication, $thothImprintId)
    {
        $thothBook = $this->factory->createFromPublication($publication);
        $thothBook->setImprintId($thothImprintId);

        if ($thothBook->getWorkStatus() === WorkStatus::ACTIVE) {
            $this->originalThothBook = clone $thothBook;
            $thothBook->setWorkStatus(WorkStatus::FORTHCOMING);
        }

        $thothBookId = $this->repository->add($thothBook);
        $publication->setData('thothBookId', $thothBookId);
        $this->registeredEntryId = $thothBookId;
        $this->registerMetadata($publication, $thothBookId);

        $this->contributionService->registerByPublication($publication);
        $this->publicationService->registerByPublication($publication);
        $this->languageService->registerByPublication($publication);
        $this->subjectService->registerByPublication($publication);
        $this->referenceService->registerByPublication($publication);
        $this->workRelationService->registerByPublication($publication, $thothImprintId);

        return $thothBookId;
    }

    public function deleteRegisteredEntry(): void
    {
        if ($this->registeredEntryId === null) {
            return;
        }

        $this->repository->delete($this->registeredEntryId);
        $this->registeredEntryId = null;
    }

    public function setActive(): void
    {
        if ($this->originalThothBook === null) {
            return;
        }

        $this->originalThothBook->setWorkId($this->registeredEntryId);
        $this->originalThothBook->setWorkStatus(WorkStatus::ACTIVE);
        $this->repository->edit($this->originalThothBook);
    }

    private function registerMetadata($publication, string $thothBookId): void
    {
        $this->titleService->registerByPublication(
            $publication,
            $thothBookId,
            $publication->getData('locale')
        );
        $this->abstractService->registerByPublication(
            $publication,
            $thothBookId,
            $publication->getData('locale')
        );
    }
}
