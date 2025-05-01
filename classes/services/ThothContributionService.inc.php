<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothContributionService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothContributionService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth contributions
 */

import('plugins.generic.thoth.classes.facades.ThothService');
import('plugins.generic.thoth.classes.facades.ThothRepo');

class ThothContributionService
{
    public $factory;
    public $repository;

    public function __construct($factory, $repository)
    {
        $this->factory = $factory;
        $this->repository = $repository;
    }

    public function register($author, $thothWorkId, $primaryContactId = null)
    {
        $thothContribution = $this->factory->createFromAuthor($author, $primaryContactId);
        $thothContribution->setWorkId($thothWorkId);

        $filter = empty($author->getOrcid()) ? $author->getFullName(false) : $author->getOrcid();
        $thothContributor = ThothRepo::contributor()->find($filter);

        if ($thothContributor === null) {
            $thothContributorId = ThothService::contributor()->register($author);
            $thothContribution->setContributorId($thothContributorId);
        } else {
            $thothContribution->setContributorId($thothContributor->getContributorId());
        }

        $thothContributionId = $this->repository->add($thothContribution);

        if ($rorId = $author->getData('rorId')) {
            ThothService::affiliation()->register($rorId, $thothContributionId);
        }

        return $thothContributionId;
    }

    public function registerByPublication($publication)
    {
        $authors = DAORegistry::getDAO('AuthorDAO')->getByPublicationId($publication->getId());
        $primaryContactId = $publication->getData('primaryContactId');

        $chapterAuthorDao = DAORegistry::getDAO('ChapterAuthorDAO');
        $chapterAuthors = $chapterAuthorDao->getAuthors($publication->getId())->toArray();
        $chapterAuthorIds = array_map(fn ($chapterAuthor) => $chapterAuthor->getId(), $chapterAuthors);

        $authors = array_filter($authors, function ($author) use ($chapterAuthorIds, $primaryContactId) {
            return $author->getId() === $primaryContactId || !in_array($author->getId(), $chapterAuthorIds);
        });

        $thothBookId = $publication->getData('thothBookId');
        foreach ($authors as $author) {
            $this->register($author, $thothBookId, $primaryContactId);
        }
    }

    public function registerByChapter($chapter)
    {
        $thothChapterId = $chapter->getData('thothChapterId');
        $authors = $chapter->getAuthors()->toArray();
        foreach ($authors as $author) {
            $this->register($author, $thothChapterId);
        }
    }
}
