<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothContributionService.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth contributions
 */

use APP\facades\Repo;
use PKP\db\DAORegistry;

class ThothContributionService
{
    public $factory;
    public $repository;
    public $contributorRepository;
    public $contributorService;
    public $biographyService;
    public $affiliationService;

    public function __construct(
        $factory,
        $repository,
        $contributorRepository,
        $contributorService,
        $biographyService,
        $affiliationService
    ) {
        $this->factory = $factory;
        $this->repository = $repository;
        $this->contributorRepository = $contributorRepository;
        $this->contributorService = $contributorService;
        $this->biographyService = $biographyService;
        $this->affiliationService = $affiliationService;
    }

    public function register($author, $seq, $thothWorkId, $primaryContactId = null)
    {
        $thothContribution = $this->factory->createFromAuthor($author, $seq, $primaryContactId);
        $thothContribution->setWorkId($thothWorkId);

        $filter = empty($author->getOrcid()) ? $author->getFullName(false) : $author->getOrcid();
        $thothContributor = $this->contributorRepository->find($filter);

        if ($thothContributor === null) {
            $thothContributorId = $this->contributorService->register($author);
            $thothContribution->setContributorId($thothContributorId);
        } else {
            $thothContribution->setContributorId($thothContributor->getContributorId());
        }

        $thothContributionId = $this->repository->add($thothContribution);
        $this->biographyService->registerByAuthor($author, $thothContributionId, $author->getData('locale'));

        if ($rorId = $author->getData('rorId')) {
            $this->affiliationService->register($rorId, $thothContributionId);
        }

        return $thothContributionId;
    }

    public function registerByPublication($publication)
    {
        $authors = Repo::author()->getCollector()
            ->filterByPublicationIds([$publication->getId()])
            ->getMany()
            ->toArray();
        $primaryContactId = $publication->getData('primaryContactId');

        $chapterDao = DAORegistry::getDAO('ChapterDAO');
        $chapters = $chapterDao->getByPublicationId($publication->getId())->toArray();

        $chapterAuthorIds = [];
        foreach ($chapters as $chapter) {
            $chapterAuthorIds = array_merge($chapterAuthorIds, (array) Repo::author()->getCollector()
                ->filterByChapterId($chapter->getId())
                ->filterByPublicationIds([$publication->getId()])
                ->getIds()
                ->toArray());
        }
        $chapterAuthorIds = array_unique($chapterAuthorIds);

        $authors = array_filter($authors, function ($author) use ($chapterAuthorIds, $primaryContactId) {
            return $author->getId() === $primaryContactId || !in_array($author->getId(), $chapterAuthorIds);
        });

        $seq = 0;
        $thothBookId = $publication->getData('thothBookId');
        foreach ($authors as $author) {
            $this->register($author, $seq, $thothBookId, $primaryContactId);
            $seq++;
        }
    }

    public function registerByChapter($chapter)
    {
        $seq = 0;
        $thothChapterId = $chapter->getData('thothChapterId');
        $authors = $chapter->getAuthors()->toArray();
        foreach ($authors as $author) {
            $this->register($author, $seq, $thothChapterId);
            $seq++;
        }
    }
}
