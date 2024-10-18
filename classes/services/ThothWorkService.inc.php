<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothWorkService.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth works
 */

use APP\core\Application;
use APP\facades\Repo;
use APP\submission\Submission;
use PKP\db\DAORegistry;

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
        $data['doi'] = $publication->getDoi();
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
            Repo::publication()->get($chapter->getData('publicationId'))->getData('datePublished');
        $params['doi'] = $chapter->getDoi();

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

        $publication = $submission->getCurrentPublication();

        $authors = Repo::author()->getCollector()
            ->filterByPublicationIds([$publication->getId()])
            ->getMany();
        foreach ($authors as $author) {
            ThothService::contribution()->register($thothClient, $author, $thothBookId);
        }

        $chapterDAO = DAORegistry::getDAO('ChapterDAO');
        $chapters = $chapterDAO->getByPublicationId($publication->getId())->toArray();
        foreach ($chapters as $chapter) {
            $this->registerWorkRelation($thothClient, $chapter, $thothImprintId, $thothBookId);
        }

        $publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');
        $publicationFormats = $publicationFormatDao->getApprovedByPublicationId($publication->getId())->toArray();
        foreach ($publicationFormats as $publicationFormat) {
            if ($publicationFormat->getIsAvailable()) {
                ThothService::publication()->register($thothClient, $publicationFormat, $thothBookId);
            }
        }

        $locale = $submission->getData('locale');
        ThothService::language()->register($thothClient, $locale, $thothBookId);

        $keywords = $publication->getData('keywords');
        foreach ($keywords[$locale] ?? [] as $seq => $keyword) {
            ThothService::subject()->registerKeyword($thothClient, $keyword, $thothBookId, $seq + 1);
        }

        $citations = DAORegistry::getDAO('CitationDAO')
            ->getByPublicationId($publication->getId())
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

        $publication = Repo::publication()->get($chapter->getData('publicationId'));
        $files = array_filter(
            iterator_to_array(Repo::submissionFile()
                ->getCollector()
                ->filterByAssoc(Application::ASSOC_TYPE_PUBLICATION_FORMAT)
                ->filterBySubmissionIds([$publication->getData('submissionId')])
                ->getMany()),
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

        $thothRelationsData = [];
        foreach ($thothRelations as $thothRelation) {
            $relatedWork = $thothRelation['relatedWork'];
            $fullTitle = $relatedWork['fullTitle'];
            $thothRelationsData[$fullTitle] = $relatedWork;
        }
        $chapterTitles = array_map(function ($chapter) {
            return $chapter->getLocalizedFullTitle();
        }, $chapters);

        foreach ($thothRelationsData as $fullTitle => $relatedWork) {
            if (!in_array($fullTitle, $chapterTitles)) {
                $thothClient->deleteWork($relatedWork['workId']);
            }
        }

        $submission = Repo::submission()->get($publication->getData('submissionId'));
        $pluginSettingsDAO = DAORegistry::getDAO('PluginSettingsDAO');

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
            Submission::WORK_TYPE_EDITED_VOLUME => ThothWork::WORK_TYPE_EDITED_BOOK,
            Submission::WORK_TYPE_AUTHORED_WORK => ThothWork::WORK_TYPE_MONOGRAPH
        ];

        return $workTypeMapping[$submissionWorkType];
    }
}
