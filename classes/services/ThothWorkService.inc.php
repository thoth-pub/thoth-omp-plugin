<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothWorkService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth works
 */

import('plugins.generic.thoth.classes.facades.ThothService');
import('plugins.generic.thoth.thoth.models.ThothWork');
import('plugins.generic.thoth.thoth.models.ThothWorkRelation');

class ThothWorkService
{
    public function newBySubmission($submission)
    {
        $request = Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();
        $context = $request->getContext();
        $publication = $submission->getCurrentPublication();

        $params = [];
        $params['workType'] = $this->getWorkTypeBySubmissionWorkType($submission->getData('workType'));
        $params['workStatus'] = ThothWork::WORK_STATUS_ACTIVE;
        $params['fullTitle'] = $publication->getLocalizedFullTitle();
        $params['title'] = $publication->getLocalizedTitle();
        $params['subtitle'] = $publication->getLocalizedData('subtitle');
        $params['longAbstract'] = $publication->getLocalizedData('abstract');
        $params['edition'] = $publication->getData('version');
        $params['doi'] = $publication->getStoredPubId('doi');
        $params['publicationDate'] = $publication->getData('datePublished');
        $params['license'] = $publication->getData('licenseUrl');
        $params['copyrightHolder'] = $publication->getLocalizedData('copyrightHolder');
        $params['coverUrl'] = $publication->getLocalizedCoverImageUrl($context->getId());
        $params['landingPage'] = $dispatcher->url(
            $request,
            ROUTE_PAGE,
            $context->getPath(),
            'catalog',
            'book',
            $submission->getBestId()
        );

        return $this->new($params);
    }

    public function newByChapter($chapter)
    {
        $params = [];
        $params['workType'] = ThothWork::WORK_TYPE_BOOK_CHAPTER;
        $params['workStatus'] = ThothWork::WORK_STATUS_ACTIVE;
        $params['fullTitle'] = $chapter->getLocalizedFullTitle();
        $params['title'] = $chapter->getLocalizedTitle();
        $params['subtitle'] = $chapter->getLocalizedData('subtitle');
        $params['longAbstract'] = $chapter->getLocalizedData('abstract');
        $params['pageCount'] = $chapter->getPages();
        $params['publicationDate'] = $chapter->getDatePublished();
        $params['doi'] = $chapter->getStoredPubId('doi');

        return $this->new($params);
    }

    public function new($params)
    {
        $work = new ThothWork();
        $work->setId($params['workId'] ?? null);
        $work->setImprintId($params['imprintId'] ?? null);
        $work->setWorkType($params['workType']);
        $work->setWorkType($params['workType']);
        $work->setWorkStatus($params['workStatus']);
        $work->setFullTitle($params['fullTitle']);
        $work->setTitle($params['title']);
        $work->setLongAbstract($params['longAbstract'] ?? null);
        $work->setEdition($params['edition'] ?? null);
        $work->setPublicationDate($params['publicationDate'] ?? null);
        $work->setSubtitle($params['subtitle'] ?? null);
        $work->setPageCount($params['pageCount'] ?? null);
        $work->setDoi($params['doi'] ?? null);
        $work->setLicense($params['license'] ?? null);
        $work->setCopyrightHolder($params['copyrightHolder'] ?? null);
        $work->setLandingPage($params['landingPage'] ?? null);
        $work->setCoverUrl($params['coverUrl'] ?? null);

        return $work;
    }

    public function get($thothClient, $thothWorkId)
    {
        $thothWorkData = $thothClient->work($thothWorkId);
        return $this->new($thothWorkData);
    }

    public function registerBook($thothClient, $submission, $thothImprintId)
    {
        $thothBook = $this->newBySubmission($submission);
        $thothBook->setImprintId($thothImprintId);

        $thothBookId = $thothClient->createWork($thothBook);
        $thothBook->setId($thothBookId);

        $authors = DAORegistry::getDAO('AuthorDAO')
            ->getByPublicationId($submission->getData('currentPublicationId'));
        foreach ($authors as $author) {
            ThothService::contribution()->register($thothClient, $author, $thothBookId);
        }

        $chapters = DAORegistry::getDAO('ChapterDAO')
            ->getByPublicationId($submission->getData('currentPublicationId'))
            ->toArray();
        foreach ($chapters as $chapter) {
            $this->registerWorkRelation($thothClient, $chapter, $thothImprintId, $thothBookId);
        }

        $publicationFormats = Application::getRepresentationDao()
            ->getApprovedByPublicationId($submission->getData('currentPublicationId'))
            ->toArray();
        foreach ($publicationFormats as $publicationFormat) {
            if ($publicationFormat->getIsAvailable()) {
                ThothService::publication()->register($thothClient, $publicationFormat, $thothBookId);
            }
        }

        $submissionKeywords = DAORegistry::getDAO('SubmissionKeywordDAO')
            ->getKeywords($submission->getData('currentPublicationId'));
        foreach ($submissionKeywords[$submission->getLocale()] ?? [] as $seq => $submissionKeyword) {
            ThothService::subject()->registerKeyword($thothClient, $submissionKeyword, $thothBookId, $seq + 1);
        }

        $submissionLocale = $submission->getData('locale');
        ThothService::language()->register($thothClient, $submissionLocale, $thothBookId);

        $citations = DAORegistry::getDAO('CitationDAO')
            ->getByPublicationId($submission->getData('currentPublicationId'))
            ->toArray();
        foreach ($citations as $citation) {
            ThothService::reference()->register($thothClient, $citation, $thothBookId);
        }

        return $thothBook;
    }

    public function registerChapter($thothClient, $chapter, $thothImprintId)
    {
        $thothChapter = $this->newByChapter($chapter);
        $thothChapter->setImprintId($thothImprintId);

        $thothChapterId = $thothClient->createWork($thothChapter);
        $thothChapter->setId($thothChapterId);

        $authors = $chapter->getAuthors()->toArray();
        foreach ($authors as $author) {
            ThothService::contribution()->register($thothClient, $author, $thothChapterId);
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
                ThothService::publication()->register(
                    $thothClient,
                    $publicationFormat,
                    $thothChapterId,
                    $chapter->getId()
                );
            }
        }

        return $thothChapter;
    }

    public function registerWorkRelation($thothClient, $chapter, $thothImprintId, $relatedWorkId)
    {
        $thothChapter = $this->registerChapter($thothClient, $chapter, $thothImprintId);

        $relation = new ThothWorkRelation();
        $relation->setRelatorWorkId($thothChapter->getId());
        $relation->setRelatedWorkId($relatedWorkId);
        $relation->setRelationType(ThothWorkRelation::RELATION_TYPE_IS_CHILD_OF);
        $relation->setRelationOrdinal($chapter->getSequence() + 1);

        $relationId = $thothClient->createWorkRelation($relation);
        $relation->setId($relationId);

        return $relation;
    }

    public function update($thothClient, $thothWork, $params, $submissionLocale)
    {
        $workData = $thothWork->getData();
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'title':
                    $workData['title'] = $value[$submissionLocale];
                    break;
                case 'subtitle':
                    $workData['subtitle'] = $value[$submissionLocale];
                    break;
                case 'abstract':
                    $workData['longAbstract'] = $value[$submissionLocale];
                    break;
                default:
                    break;
            }
        }

        if (isset($workData['title']) && isset($workData['subtitle'])) {
            $workData['fullTitle'] = $workData['title'] . ': ' . $workData['subtitle'];
        } elseif (isset($workData['title'])) {
            $workData['fullTitle'] = $workData['title'];
        }

        $newThothWork = $this->new($workData);
        $thothClient->updateWork($newThothWork);
        return $newThothWork;
    }

    public function getWorkTypeBySubmissionWorkType($submissionWorkType)
    {
        $workTypeMapping = [
            WORK_TYPE_EDITED_VOLUME => ThothWork::WORK_TYPE_EDITED_BOOK,
            WORK_TYPE_AUTHORED_WORK => ThothWork::WORK_TYPE_MONOGRAPH
        ];

        return $workTypeMapping[$submissionWorkType];
    }
}
