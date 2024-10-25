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
import('plugins.generic.thoth.lib.thothAPI.models.ThothWork');
import('plugins.generic.thoth.lib.thothAPI.models.ThothWorkRelation');
import('plugins.generic.thoth.classes.services.queryBuilders.ThothWorkQueryBuilder');

class ThothWorkService
{
    public function getQueryBuilder($thothClient)
    {
        return new ThothWorkQueryBuilder($thothClient);
    }

    private function getDoiResolvingUrl($doi)
    {
        if (empty($doi)) {
            return $doi;
        }

        $search = ['%', '"', '#', ' ', '<', '>', '{'];
        $replace = ['%25', '%22', '%23', '%20', '%3c', '%3e', '%7b'];
        $encodedDoi = str_replace($search, $replace, $doi);

        return "https://doi.org/$encodedDoi";
    }

    public function getDataBySubmission($submission, $publication = null)
    {
        $request = Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();
        $context = $request->getContext();
        $publication = $publication ?? $submission->getCurrentPublication();

        $data = [];
        $data['workType'] = $this->getWorkTypeBySubmissionWorkType($submission->getData('workType'));
        $data['workStatus'] = ThothWork::WORK_STATUS_ACTIVE;
        $data['fullTitle'] = $publication->getLocalizedFullTitle();
        $data['title'] = $publication->getLocalizedTitle();
        $data['subtitle'] = $publication->getLocalizedData('subtitle');
        $data['longAbstract'] = $publication->getLocalizedData('abstract');
        $data['edition'] = $publication->getData('version');
        $data['doi'] = $this->getDoiResolvingUrl($publication->getStoredPubId('doi'));
        $data['publicationDate'] = $publication->getData('datePublished');
        $data['license'] = $publication->getData('licenseUrl');
        $data['copyrightHolder'] = $publication->getLocalizedData('copyrightHolder');
        $data['coverUrl'] = $publication->getLocalizedCoverImageUrl($context->getId());
        $data['landingPage'] = $dispatcher->url(
            $request,
            ROUTE_PAGE,
            $context->getPath(),
            'catalog',
            'book',
            $submission->getBestId()
        );

        return $data;
    }

    public function newBySubmission($submission, $publication = null)
    {
        return $this->new($this->getDataBySubmission($submission, $publication));
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
        $params['publicationDate'] = $chapter->getDatePublished() ??
            Services::get('publication')->get($chapter->getData('publicationId'))->getData('datePublished');
        $params['doi'] = $this->getDoiResolvingUrl($chapter->getStoredPubId('doi'));

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

    public function updateBook($thothClient, $thothWorkId, $submission, $publication)
    {
        $thothWorkData = [];
        $thothWorkData = $this->getQueryBuilder($thothClient)
            ->includeContributions()
            ->includeRelations(true)
            ->includeSubjects()
            ->includeReferences()
            ->includePublications(true)
            ->get($thothWorkId);
        $newThothWork = $this->new(array_merge(
            $thothWorkData,
            $this->getDataBySubmission($submission, $publication)
        ));
        $thothClient->updateWork($newThothWork);

        if (isset($thothWorkData['contributions'])) {
            ThothService::contribution()->updateContributions(
                $thothClient,
                $thothWorkData['contributions'],
                $publication,
                $thothWorkId
            );
        }

        if (isset($thothWorkData['relations'])) {
            $this->updateRelations(
                $thothClient,
                $thothWorkData['relations'],
                $publication,
                $thothWorkId,
                $thothWorkData['imprintId']
            );
        }

        if (isset($thothWorkData['subjects'])) {
            ThothService::subject()->updateKeywords(
                $thothClient,
                $thothWorkData['subjects'],
                $publication,
                $thothWorkId
            );
        }

        if (isset($thothWorkData['references'])) {
            ThothService::reference()->updateReferences(
                $thothClient,
                $thothWorkData['references'],
                $publication,
                $thothWorkId
            );
        }

        if (isset($thothWorkData['publications'])) {
            ThothService::publication()->updateBookPublications(
                $thothClient,
                $thothWorkData['publications'],
                $publication,
                $thothWorkId
            );
        }

        return $newThothWork;
    }

    public function updateRelations($thothClient, $thothRelations, $publication, $thothWorkId, $thothImprintId)
    {
        $chapterDAO = DAORegistry::getDAO('ChapterDAO');
        $chapters = $chapterDAO->getByPublicationId($publication->getId())->toArray();

        if (empty($chapters) || empty($thothRelations)) {
            return;
        }

        $thothRelationsData = array_column($thothRelations, 'relatedWork', 'fullTitle');
        $chapterTitles = array_map(function ($chapter) {
            return $chapter->getLocalizedFullTitle();
        }, $chapters);

        foreach ($thothRelationsData as $fullTitle => $relatedWork) {
            if (!in_array($fullTitle, $chapterTitles)) {
                $thothClient->deleteWork($relatedWork['workId']);
            }
        }

        $submissionService = Services::get('submission');
        $submission = $submissionService->get($publication->getData('submissionId'));

        foreach ($chapters as $chapter) {
            $chapterTitle = $chapter->getLocalizedFullTitle();
            if (!isset($thothRelationsData[$chapterTitle])) {
                $this->registerWorkRelation($thothClient, $chapter, $thothImprintId, $thothWorkId);
            }
        }
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
