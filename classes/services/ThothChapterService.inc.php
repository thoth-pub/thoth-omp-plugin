<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothChapterService.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothChapterService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth chapters
 */

class ThothChapterService
{
    public $factory;
    public $repository;
    public $contributionService;
    public $publicationService;
    public $titleService;
    public $abstractService;

    public function __construct(
        $factory,
        $repository,
        $contributionService,
        $publicationService,
        $titleService,
        $abstractService
    ) {
        $this->factory = $factory;
        $this->repository = $repository;
        $this->contributionService = $contributionService;
        $this->publicationService = $publicationService;
        $this->titleService = $titleService;
        $this->abstractService = $abstractService;
    }

    public function register($chapter, $thothImprintId)
    {
        $thothChapter = $this->factory->createFromChapter($chapter);
        $thothChapter->setImprintId($thothImprintId);

        $thothChapterId = $this->repository->add($thothChapter);
        $chapter->setData('thothChapterId', $thothChapterId);
        $this->registerMetadata($chapter, $thothChapterId);

        $this->contributionService->registerByChapter($chapter);
        $this->publicationService->registerByChapter($chapter);

        return $thothChapterId;
    }

    private function registerMetadata($chapter, $thothChapterId)
    {
        $publication = DAORegistry::getDAO('PublicationDAO')->getById($chapter->getData('publicationId'));
        $this->titleService->registerByChapter($chapter, $thothChapterId, $publication->getData('locale'));
        $this->abstractService->registerByChapter($chapter, $thothChapterId, $publication->getData('locale'));
    }
}
