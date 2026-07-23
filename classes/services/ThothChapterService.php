<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothChapterService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothChapterService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth Chapters
 */

namespace APP\plugins\generic\thoth\classes\services;

use APP\facades\Repo;

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

    public function getDesiredWork($chapter, string $thothImprintId)
    {
        $thothChapter = $this->factory->createFromChapter($chapter);
        $thothChapter->setImprintId($thothImprintId);

        return $thothChapter;
    }

    public function register($chapter, $thothImprintId, $thothChapter = null)
    {
        $thothChapter = $thothChapter ?? $this->getDesiredWork($chapter, $thothImprintId);
        $thothChapterId = $this->repository->add($thothChapter);
        $chapter->setData('thothChapterId', $thothChapterId);
        $this->registerMetadata($chapter, $thothChapterId);

        $this->contributionService->registerByChapter($chapter);
        $this->publicationService->registerByChapter($chapter);

        return $thothChapterId;
    }

    public function update(
        $chapter,
        array $existingChapter,
        string $thothImprintId,
        $thothChapter = null
    ): bool {
        $thothChapter = $thothChapter ?? $this->getDesiredWork($chapter, $thothImprintId);
        $thothChapterId = $existingChapter['workId'];
        $thothChapter->setWorkId($thothChapterId);
        $this->repository->edit($thothChapter);
        $chapter->setData('thothChapterId', $thothChapterId);

        $publication = Repo::publication()->get($chapter->getData('publicationId'));
        $locale = $publication->getData('locale');
        $this->titleService->updateByChapter(
            $chapter,
            $thothChapterId,
            $existingChapter['titles'] ?? [],
            $locale
        );
        $this->abstractService->updateByChapter(
            $chapter,
            $thothChapterId,
            $existingChapter['abstracts'] ?? [],
            $locale
        );
        $this->contributionService->update(
            $chapter->getAuthors()->toArray(),
            $thothChapterId,
            $existingChapter['contributions'] ?? []
        );

        return $this->publicationService->updateByChapter(
            $chapter,
            $thothChapterId,
            $existingChapter['publications'] ?? [],
            $existingChapter['workStatus'] ?? null
        );
    }

    public function delete(string $thothChapterId): void
    {
        $this->repository->delete($thothChapterId);
    }

    private function registerMetadata($chapter, string $thothChapterId): void
    {
        $publication = Repo::publication()->get($chapter->getData('publicationId'));

        $this->titleService->registerByChapter($chapter, $thothChapterId, $publication->getData('locale'));
        $this->abstractService->registerByChapter($chapter, $thothChapterId, $publication->getData('locale'));
    }
}
