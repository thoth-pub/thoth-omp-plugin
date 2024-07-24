<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic Thoth API interactions
 */

import('plugins.generic.thoth.classes.services.WorkService');
import('plugins.generic.thoth.classes.services.ContributorService');
import('plugins.generic.thoth.classes.services.ContributionService');
import('plugins.generic.thoth.classes.services.ThothPublicationService');
import('plugins.generic.thoth.lib.APIKeyEncryption.APIKeyEncryption');
import('plugins.generic.thoth.thoth.ThothClient');
import('plugins.generic.thoth.thoth.models.WorkRelation');

class ThothService
{
    private $plugin;

    private $contextId;

    public function __construct($plugin, $contextId)
    {
        $this->plugin = $plugin;
        $this->contextId = $contextId;
    }

    public function getThothClient()
    {
        $endpoint = $this->plugin->getSetting($this->contextId, 'apiUrl');
        $email = $this->plugin->getSetting($this->contextId, 'email');
        $password = $this->plugin->getSetting($this->contextId, 'password');

        if (!$email || !$password) {
            throw new Exception('Thoth credentials not configured.');
        }

        $password = APIKeyEncryption::decryptString($password);

        $client = new ThothClient($endpoint);
        $client->login($email, $password);

        return $client;
    }

    public function registerBook($submission)
    {
        $workService = new WorkService();
        $bookProps = $workService->getPropertiesBySubmission($submission);

        $book = $workService->new($bookProps);
        $book->setImprintId($this->plugin->getSetting($this->contextId, 'imprintId'));

        $bookId = $this->getThothClient()->createWork($book);
        $book->setId($bookId);

        $authors = DAORegistry::getDAO('AuthorDAO')
            ->getByPublicationId($submission->getData('currentPublicationId'));
        foreach ($authors as $author) {
            $this->registerContribution($author, $bookId);
        }

        $chapters = DAORegistry::getDAO('ChapterDAO')
            ->getByPublicationId($submission->getData('currentPublicationId'))
            ->toArray();
        foreach ($chapters as $chapter) {
            $this->registerRelation($chapter, $bookId);
        }

        $publicationFormats = Application::getRepresentationDao()
            ->getByPublicationId($submission->getData('currentPublicationId'))
            ->toArray();
        foreach ($publicationFormats as $publicationFormat) {
            $this->registerPublication($publicationFormat, $bookId);
        }

        return $book;
    }

    public function registerContributor($author)
    {
        $contributorService = new contributorService();
        $contributorProps = $contributorService->getPropertiesByAuthor($author);

        $contributor = $contributorService->new($contributorProps);

        $contributorId = $this->getThothClient()->createContributor($contributor);
        $contributor->setId($contributorId);

        return $contributor;
    }

    public function registerContribution($author, $workId)
    {
        $contributionService = new ContributionService();
        $contributionProps = $contributionService->getPropertiesByAuthor($author);

        $contribution = $contributionService->new($contributionProps);
        $contribution->setWorkId($workId);

        $contributor = $this->registerContributor($author);
        $contribution->setContributorId($contributor->getId());

        $contributionId = $this->getThothClient()->createContribution($contribution);
        $contribution->setId($contributionId);

        return $contribution;
    }

    public function registerChapter($chapter)
    {
        $workService = new WorkService();
        $thothChapterProps = $workService->getPropertiesByChapter($chapter);

        $thothChapter = $workService->new($thothChapterProps);
        $thothChapter->setImprintId($this->plugin->getSetting($this->contextId, 'imprintId'));

        $chapterId = $this->getThothClient()->createWork($thothChapter);
        $thothChapter->setId($chapterId);

        $authors = $chapter->getAuthors()->toArray();
        foreach ($authors as $author) {
            $this->registerContribution($author, $chapterId);
        }

        return $thothChapter;
    }

    public function registerRelation($chapter, $relatedWorkId)
    {
        $thothChapter = $this->registerChapter($chapter);

        $relation = new WorkRelation();
        $relation->setRelatorWorkId($thothChapter->getId());
        $relation->setRelatedWorkId($relatedWorkId);
        $relation->setRelationType(WorkRelation::RELATION_TYPE_IS_CHILD_OF);
        $relation->setRelationOrdinal($chapter->getSequence() + 1);

        $relationId = $this->getThothClient()->createWorkRelation($relation);
        $relation->setId($relationId);

        return $relation;
    }

    public function registerPublication($publicationFormat, $workId)
    {
        $publicationService = new ThothPublicationService();
        $publicationProps = $publicationService->getPropertiesByPublicationFormat($publicationFormat);

        $publication = $publicationService->new($publicationProps);
        $publication->setWorkId($workId);

        $publicationId = $this->getThothClient()->createPublication($publication);
        $publication->setId($publicationId);

        return $publication;
    }
}
