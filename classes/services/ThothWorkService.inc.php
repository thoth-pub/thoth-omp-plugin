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
    public function newBySubmission($submission, $publication = null)
    {
        $request = Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();
        $context = $request->getContext();
        $publication = $publication ?? $submission->getCurrentPublication();

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

    public function update($thothClient, $thothWork, $params, $submission, $publication)
    {
        $odlThothWorkData = $thothWork->getData();
        $newThothWorkData = array_merge(
            $odlThothWorkData,
            $this->getDataBySubmission($submission, $publication, $params)
        );
        $newThothWork = $this->new($newThothWorkData);
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

    private function getDataBySubmission($submission, $publication, $params)
    {
        $data = [];
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'title':
                    $data['title'] = $publication->getLocalizedData('title');
                    break;
                case 'subtitle':
                    $data['subtitle'] = $publication->getLocalizedData('subtitle');
                    break;
                case 'abstract':
                    $data['longAbstract'] = $publication->getLocalizedData('abstract');
                    break;
                case 'datePublished':
                    $data['publicationDate'] = $publication->getData('datePublished');
                    break;
                case 'licenseUrl':
                    $data['license'] = $publication->getData('licenseUrl');
                    break;
                case 'copyrightHolder':
                    $data['copyrightHolder'] = $publication->getLocalizedData('copyrightHolder');
                    break;
                case 'coverImage':
                    $data['coverUrl'] = $publication->getLocalizedCoverImageUrl($submission->getData('contextId'));
                    break;
                case 'urlPath':
                    $request = Application::get()->getRequest();
                    $context = $request->getContext();
                    $data['landingPage'] = $request->getDispatcher()->url(
                        $request,
                        ROUTE_PAGE,
                        $context->getPath(),
                        'catalog',
                        'book',
                        $publication->getData('urlPath') ?? $submission->getId()
                    );
                    break;
                default:
                    break;
            }
        }

        if (isset($data['title']) || isset($data['title'])) {
            $data['fullTitle'] = $publication->getLocalizedFullTitle();
        }

        return $data;
    }
}
