<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothBookRegistrationService.inc.php
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

use ThothApi\Exception\QueryException;
use ThothApi\GraphQL\Enums\WorkStatus;

import('plugins.generic.thoth.classes.services.ThothBookRegistrationResult');

class ThothBookRegistrationService
{
    private $factory;
    private $repository;
    private $abstractService;
    private $contributionService;
    private $languageService;
    private $publicationService;
    private $referenceService;
    private $subjectService;
    private $titleService;
    private $workRelationService;

    public function __construct(
        $factory,
        $repository,
        $abstractService,
        $contributionService,
        $languageService,
        $publicationService,
        $referenceService,
        $subjectService,
        $titleService,
        $workRelationService
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
        } catch (QueryException $e) {
            $this->deleteRegisteredEntry($registrationResult);
            throw $e;
        }

        return $registrationResult;
    }

    public function deleteRegisteredEntry($registrationResult)
    {
        $this->repository->delete($registrationResult->getWorkId());
    }

    public function setActive($registrationResult)
    {
        if (!$registrationResult->shouldActivate()) {
            return;
        }

        $thothBook = $registrationResult->getBookToActivate();
        $thothBook->setWorkId($registrationResult->getWorkId());
        $thothBook->setWorkStatus(WorkStatus::ACTIVE);
        $this->repository->edit($thothBook);
    }

    private function registerMetadata($publication, $thothBookId)
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
