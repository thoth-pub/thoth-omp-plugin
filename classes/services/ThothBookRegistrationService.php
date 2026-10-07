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

use APP\plugins\generic\thoth\classes\exceptions\ThothRegistrationException;
use APP\plugins\generic\thoth\classes\factories\ThothBookFactory;
use APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource;
use APP\plugins\generic\thoth\classes\repositories\ThothBookRepository;
use APP\publication\Publication;
use APP\submission\Repository as SubmissionRepository;
use APP\submission\Submission;
use Illuminate\Database\ConnectionInterface;
use ThothApi\GraphQL\Enums\WorkStatus;
use Throwable;

class ThothBookRegistrationService
{
    private ThothBookFactory $factory;
    private ThothBookRepository $repository;
    private ThothAbstractService $abstractService;
    private ThothContributionService $contributionService;
    private ThothLanguageService $languageService;
    private ThothPublicationService $publicationService;
    private ThothReferenceService $referenceService;
    private ThothSubjectService $subjectService;
    private ThothTitleService $titleService;
    private ThothWorkRelationService $workRelationService;
    private ThothFrontcoverService $frontcoverService;

    public function __construct(
        ThothBookFactory $factory,
        ThothBookRepository $repository,
        ThothAbstractService $abstractService,
        ThothContributionService $contributionService,
        ThothLanguageService $languageService,
        ThothPublicationService $publicationService,
        ThothReferenceService $referenceService,
        ThothSubjectService $subjectService,
        ThothTitleService $titleService,
        ThothWorkRelationService $workRelationService,
        private OmpMetadataSource $metadataSource,
        ThothFrontcoverService $frontcoverService,
        private SubmissionRepository $submissionRepository,
        private ConnectionInterface $connection
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

    public function register(
        Publication $publication,
        string $thothImprintId,
        Submission $submission,
        ?string $workType = null
    ): ThothBookRegistrationResult {
        $thothBook = $this->factory->createFromPublication(
            $publication,
            $this->metadataSource->getBookContext($publication),
            $workType
        );
        $thothBook->setImprintId($thothImprintId);

        $bookToActivate = null;
        if ($thothBook->getWorkStatus() === WorkStatus::ACTIVE) {
            $bookToActivate = clone $thothBook;
            $thothBook->setWorkStatus(WorkStatus::FORTHCOMING);
        }

        $previousBookId = $publication->getData('thothBookId');
        $thothBookId = $this->repository->add($thothBook);
        $publication->setData('thothBookId', $thothBookId);

        try {
            $this->registerMetadata($publication, $thothBookId);

            $this->contributionService->registerByPublication($publication);
            $this->publicationService->registerByPublication($publication);
            $this->languageService->registerByPublication($publication);
            $this->subjectService->registerByPublication($publication);
            $this->referenceService->registerByPublication($publication);
            $this->workRelationService->registerByPublication($publication, $thothImprintId);
            $warning = $this->frontcoverService->sync($publication, $thothBookId);
            if ($bookToActivate !== null) {
                $bookToActivate->setWorkId($thothBookId);
                $this->repository->edit($bookToActivate);
            }
            $this->connection->transaction(function () use ($submission, $thothBookId): void {
                $this->submissionRepository->edit($submission, ['thothWorkId' => $thothBookId]);
            });
        } catch (Throwable $e) {
            $publication->setData('thothBookId', $previousBookId);
            try {
                $this->repository->delete($thothBookId);
            } catch (Throwable $compensationFailure) {
                throw new ThothRegistrationException($thothBookId, $e, $compensationFailure);
            }
            throw $e;
        }

        return new ThothBookRegistrationResult($thothBookId, $warning === null ? [] : [$warning]);
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
