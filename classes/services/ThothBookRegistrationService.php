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

use ThothApi\Exception\QueryException;
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
    private ?ThothFrontcoverService $frontcoverService;

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
        ThothWorkRelationService $workRelationService,
        ?ThothFrontcoverService $frontcoverService = null
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
        $this->frontcoverService = $frontcoverService;
    }

    public function register($publication, $thothImprintId): ThothBookRegistrationResult
    {
        $thothBook = $this->factory->createFromPublication($publication);
        $thothBook->setImprintId($thothImprintId);

        $bookToActivate = null;
        if ($thothBook->getWorkStatus() === WorkStatus::ACTIVE) {
            $bookToActivate = clone $thothBook;
            $thothBook->setWorkStatus(WorkStatus::FORTHCOMING);
        }

        $thothBookId = $this->repository->add($thothBook);
        $publication->setData('thothBookId', $thothBookId);
        $registrationResult = new ThothBookRegistrationResult($thothBookId, $bookToActivate);

        try {
            $this->registerMetadata($publication, $thothBookId);

            $this->contributionService->registerByPublication($publication);
            $this->publicationService->registerByPublication($publication);
            $this->languageService->registerByPublication($publication);
            $this->subjectService->registerByPublication($publication);
            $this->referenceService->registerByPublication($publication);
            $this->workRelationService->registerByPublication($publication, $thothImprintId);
            $registrationResult->setWarning($this->frontcoverService?->sync($publication, $thothBookId));
        } catch (QueryException $e) {
            $this->deleteRegisteredEntry($registrationResult);
            throw $e;
        }

        return $registrationResult;
    }

    public function deleteRegisteredEntry(ThothBookRegistrationResult $registrationResult): void
    {
        $this->repository->delete($registrationResult->getWorkId());
    }

    public function setActive(ThothBookRegistrationResult $registrationResult): void
    {
        if (!$registrationResult->shouldActivate()) {
            return;
        }

        $thothBook = $registrationResult->getBookToActivate();
        $thothBook->setWorkId($registrationResult->getWorkId());
        $thothBook->setWorkStatus(WorkStatus::ACTIVE);
        $this->repository->edit($thothBook);
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
