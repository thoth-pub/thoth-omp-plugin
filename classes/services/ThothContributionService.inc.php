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
        return $this->registerContribution($author, $thothContribution, $thothWorkId);
    }

    private function registerContribution($author, $thothContribution, string $thothWorkId)
    {
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
        $primaryContactId = $publication->getData('primaryContactId');
        $authors = $this->getPublicationAuthors($publication, $primaryContactId);

        $seq = 0;
        $thothBookId = $publication->getData('thothBookId');
        foreach ($authors as $author) {
            $this->register($author, $seq, $thothBookId, $primaryContactId);
            $seq++;
        }
    }

    public function synchronizeByPublication($publication, string $thothWorkId): void
    {
        $this->updateByPublication($publication, $thothWorkId, $this->repository->getByWorkId($thothWorkId));
    }

    public function updateByPublication(
        $publication,
        string $thothWorkId,
        array $existingContributions = []
    ): void {
        $primaryContactId = $publication->getData('primaryContactId');
        $this->update(
            $this->getPublicationAuthors($publication, $primaryContactId),
            $thothWorkId,
            $existingContributions,
            $primaryContactId
        );
    }

    public function update(
        array $authors,
        string $thothWorkId,
        array $existingContributions,
        $primaryContactId = null
    ): void {
        $remainingContributions = $existingContributions;

        foreach (array_values($authors) as $seq => $author) {
            $thothContribution = $this->factory->createFromAuthor($author, $seq, $primaryContactId);
            $existingKey = $this->findMatchingContributionKey(
                $author,
                $thothContribution->getContributionType(),
                $remainingContributions
            );
            if ($existingKey === null) {
                $this->registerContribution($author, $thothContribution, $thothWorkId);
                continue;
            }

            $existingContribution = $remainingContributions[$existingKey];
            $thothContribution->setWorkId($thothWorkId);
            $thothContribution->setContributionId($existingContribution['contributionId']);
            if ($contributorId = $this->getExistingContributorId($existingContribution)) {
                $thothContribution->setContributorId($contributorId);
            }

            $this->repository->edit($thothContribution);
            unset($remainingContributions[$existingKey]);
        }

        foreach ($remainingContributions as $existingContribution) {
            if (isset($existingContribution['contributionId'])) {
                $this->repository->delete($existingContribution['contributionId']);
            }
        }
    }

    private function getPublicationAuthors($publication, $primaryContactId): array
    {
        $authors = Repo::author()->getCollector()
            ->filterByPublicationIds([$publication->getId()])
            ->getMany()
            ->toArray();

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

        return array_filter($authors, function ($author) use ($chapterAuthorIds, $primaryContactId) {
            return $author->getId() === $primaryContactId || !in_array($author->getId(), $chapterAuthorIds);
        });
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

    private function findMatchingContributionKey(
        $author,
        string $contributionType,
        array $existingContributions
    ): ?int {
        foreach ($existingContributions as $key => $existingContribution) {
            if (
                ($existingContribution['contributionType'] ?? null) === $contributionType
                && $this->isSameAuthor($author, $existingContribution)
            ) {
                return $key;
            }
        }

        return null;
    }

    private function isSameAuthor($author, array $existingContribution): bool
    {
        $authorOrcid = $this->normalizeOrcid($author->getOrcid());
        $existingOrcid = $this->normalizeOrcid($this->getExistingContributorData($existingContribution, 'orcid'));

        if ($authorOrcid !== null && $existingOrcid !== null) {
            return $authorOrcid === $existingOrcid;
        }

        return $this->normalizeName($author->getFullName(false)) === $this->normalizeName(
            $this->getExistingFullName($existingContribution)
        );
    }

    private function getExistingContributorId(array $existingContribution): ?string
    {
        return $existingContribution['contributorId']
            ?? $this->getExistingContributorData($existingContribution, 'contributorId');
    }

    private function getExistingFullName(array $existingContribution): ?string
    {
        return $this->getExistingContributorData($existingContribution, 'fullName')
            ?? ($existingContribution['fullName'] ?? null);
    }

    private function getExistingContributorData(array $existingContribution, string $key)
    {
        return $existingContribution['contributor'][$key] ?? null;
    }

    private function normalizeOrcid(?string $orcid): ?string
    {
        if (empty($orcid)) {
            return null;
        }

        return strtolower(preg_replace('#^https?://(?:www\.)?orcid\.org/#i', '', trim($orcid)));
    }

    private function normalizeName(?string $name): string
    {
        return preg_replace('/\s+/', ' ', strtolower(trim((string) $name)));
    }
}
