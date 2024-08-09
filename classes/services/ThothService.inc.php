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

import('plugins.generic.thoth.classes.services.ThothWorkService');
import('plugins.generic.thoth.classes.services.ThothContributorService');
import('plugins.generic.thoth.classes.services.ThothContributionService');
import('plugins.generic.thoth.classes.services.ThothLocationService');
import('plugins.generic.thoth.classes.services.ThothPublicationService');
import('plugins.generic.thoth.lib.APIKeyEncryption.APIKeyEncryption');
import('plugins.generic.thoth.thoth.ThothClient');
import('plugins.generic.thoth.thoth.models.ThothLanguage');
import('plugins.generic.thoth.thoth.models.ThothSubject');
import('plugins.generic.thoth.thoth.models.ThothReference');
import('plugins.generic.thoth.thoth.models.ThothWorkRelation');

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
        $workService = new ThothWorkService();
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
            ->getApprovedByPublicationId($submission->getData('currentPublicationId'))
            ->toArray();
        foreach ($publicationFormats as $publicationFormat) {
            if ($publicationFormat->getIsAvailable()) {
                $this->registerPublication($publicationFormat, $bookId);
            }
        }

        $submissionKeywords = DAORegistry::getDAO('SubmissionKeywordDAO')
            ->getKeywords($submission->getData('currentPublicationId'));
        foreach ($submissionKeywords[$submission->getLocale()] as $seq => $submissionKeyword) {
            $this->registerKeyword($submissionKeyword, $bookId, $seq + 1);
        }

        $submissionLocale = $submission->getData('locale');
        $this->registerLanguage($submissionLocale, $bookId);

        $citations = DAORegistry::getDAO('CitationDAO')
            ->getByPublicationId($submission->getData('currentPublicationId'))
            ->toArray();
        foreach ($citations as $citation) {
            $this->registerReference($citation, $bookId);
        }

        return $book;
    }

    public function registerChapter($chapter)
    {
        $workService = new ThothWorkService();
        $thothChapterProps = $workService->getPropertiesByChapter($chapter);

        $thothChapter = $workService->new($thothChapterProps);
        $thothChapter->setImprintId($this->plugin->getSetting($this->contextId, 'imprintId'));

        $chapterId = $this->getThothClient()->createWork($thothChapter);
        $thothChapter->setId($chapterId);

        $authors = $chapter->getAuthors()->toArray();
        foreach ($authors as $author) {
            $this->registerContribution($author, $chapterId);
        }

        $publication = Services::get('publication')->get($chapter->getData('publicationId'));
        $files = array_filter(
            iterator_to_array(Services::get('submissionFile')->getMany([
                'assocTypes' => [ASSOC_TYPE_PUBLICATION_FORMAT],
                'submissionIds' => [$publication->getData('submissionId')],
            ])),
            function ($a) use ($chapter) {
                return $a->getData('chapterId') == $chapter->getId();
            }
        );
        $publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');
        foreach ($files as $file) {
            $publicationFormat = $publicationFormatDao->getById($file->getData('assocId'));
            if ($publicationFormat->getIsAvailable()) {
                $this->registerPublication($publicationFormat, $chapterId, $chapter->getId());
            }
        }

        return $thothChapter;
    }

    public function registerRelation($chapter, $relatedWorkId)
    {
        $thothChapter = $this->registerChapter($chapter);

        $relation = new ThothWorkRelation();
        $relation->setRelatorWorkId($thothChapter->getId());
        $relation->setRelatedWorkId($relatedWorkId);
        $relation->setRelationType(ThothWorkRelation::RELATION_TYPE_IS_CHILD_OF);
        $relation->setRelationOrdinal($chapter->getSequence() + 1);

        $relationId = $this->getThothClient()->createWorkRelation($relation);
        $relation->setId($relationId);

        return $relation;
    }

    public function registerKeyword($submissionKeyword, $workId, $seq = 1)
    {
        $thothKeyword = new ThothSubject();
        $thothKeyword->setWorkId($workId);
        $thothKeyword->setSubjectType(ThothSubject::SUBJECT_TYPE_KEYWORD);
        $thothKeyword->setSubjectCode($submissionKeyword);
        $thothKeyword->setSubjectOrdinal($seq);

        $thothKeywordId = $this->getThothClient()->createSubject($thothKeyword);
        $thothKeyword->setId($thothKeywordId);

        return $thothKeyword;
    }
}
