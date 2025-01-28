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

use ThothApi\GraphQL\Models\Work as ThothWork;
use ThothApi\GraphQL\Models\WorkRelation as ThothWorkRelation;

import('plugins.generic.thoth.classes.facades.ThothService');

class ThothWorkService
{
    public function getDoiResolvingUrl($doi)
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

        $allowedTags = '<b><strong><em><i><u><ul><ol><li><p><h1><h2><h3><h4><h5><h6>';

        $data = [];
        $data['workType'] = $this->getWorkTypeBySubmissionWorkType($submission->getData('workType'));
        $data['workStatus'] = ThothWork::WORK_STATUS_ACTIVE;
        $data['fullTitle'] = $publication->getLocalizedFullTitle();
        $data['title'] = $publication->getLocalizedTitle();
        $data['subtitle'] = $publication->getLocalizedData('subtitle');
        $data['longAbstract'] = strip_tags($publication->getLocalizedData('abstract'), $allowedTags);
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
        $work->setWorkId($params['workId'] ?? null);
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
        $work->setPageCount(!empty($params['pageCount']) ? (int) $params['pageCount'] : null);
        $work->setDoi($params['doi'] ?? null);
        $work->setLicense($params['license'] ?? null);
        $work->setCopyrightHolder($params['copyrightHolder'] ?? null);
        $work->setLandingPage($params['landingPage'] ?? null);
        $work->setCoverUrl($params['coverUrl'] ?? null);

        return $work;
    }

    public function get($thothWorkId)
    {
        $thothClient = ThothContainer::getInstance()->get('client');
        return $thothClient->work($thothWorkId);
    }

    public function getByDoi($doi)
    {
        $thothClient = ThothContainer::getInstance()->get('client');
        return $thothClient->workByDoi($doi);
    }

    public function registerBook($submission, $thothImprintId)
    {
        $thothBook = $this->newBySubmission($submission);
        $thothBook->setImprintId($thothImprintId);

        $thothClient = ThothContainer::getInstance()->get('client');
        $thothBookId = $thothClient->createWork($thothBook);
        $thothBook->setWorkId($thothBookId);

        $authors = DAORegistry::getDAO('AuthorDAO')
            ->getByPublicationId($submission->getData('currentPublicationId'));
        foreach ($authors as $author) {
            ThothService::contribution()->register($author, $thothBookId);
        }

        $chapters = DAORegistry::getDAO('ChapterDAO')
            ->getByPublicationId($submission->getData('currentPublicationId'))
            ->toArray();
        foreach ($chapters as $chapter) {
            $this->registerWorkRelation($chapter, $thothImprintId, $thothBookId);
        }

        $publicationFormats = Application::getRepresentationDao()
            ->getApprovedByPublicationId($submission->getData('currentPublicationId'))
            ->toArray();
        foreach ($publicationFormats as $publicationFormat) {
            if ($publicationFormat->getIsAvailable()) {
                ThothService::publication()->register($publicationFormat, $thothBookId);
            }
        }

        $submissionKeywords = DAORegistry::getDAO('SubmissionKeywordDAO')
            ->getKeywords($submission->getData('currentPublicationId'));
        foreach ($submissionKeywords[$submission->getLocale()] ?? [] as $seq => $submissionKeyword) {
            ThothService::subject()->registerKeyword($submissionKeyword, $thothBookId, $seq + 1);
        }

        $submissionLocale = $submission->getData('locale');
        ThothService::language()->register($submissionLocale, $thothBookId);

        $citations = DAORegistry::getDAO('CitationDAO')
            ->getByPublicationId($submission->getData('currentPublicationId'))
            ->toArray();
        foreach ($citations as $citation) {
            ThothService::reference()->register($citation, $thothBookId);
        }

        return $thothBook;
    }

    public function registerChapter($chapter, $thothImprintId)
    {
        $thothChapter = $this->newByChapter($chapter);
        $thothChapter->setImprintId($thothImprintId);

        $thothClient = ThothContainer::getInstance()->get('client');
        $thothChapterId = $thothClient->createWork($thothChapter);
        $thothChapter->setWorkId($thothChapterId);

        $authors = $chapter->getAuthors()->toArray();
        foreach ($authors as $author) {
            ThothService::contribution()->register($author, $thothChapterId);
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
                    $publicationFormat,
                    $thothChapterId,
                    $chapter->getId()
                );
            }
        }

        return $thothChapter;
    }

    public function registerWorkRelation($chapter, $thothImprintId, $relatedWorkId)
    {
        $thothChapter = $this->registerChapter($chapter, $thothImprintId);

        $relation = new ThothWorkRelation();
        $relation->setRelatorWorkId($thothChapter->getWorkId());
        $relation->setRelatedWorkId($relatedWorkId);
        $relation->setRelationType(ThothWorkRelation::RELATION_TYPE_IS_CHILD_OF);
        $relation->setRelationOrdinal($chapter->getSequence() + 1);

        $thothClient = ThothContainer::getInstance()->get('client');
        $relationId = $thothClient->createWorkRelation($relation);
        $relation->setWorkRelationId($relationId);

        return $relation;
    }

    public function updateBook($thothWorkId, $submission, $publication)
    {
        $thothClient = ThothContainer::getInstance()->get('client');

        $thothData = $thothClient->rawQuery($this->getCompleteWorkQuery(), ['workId' => $thothWorkId]);
        $thothWorkData = $thothData['work'];
        $submissionData = $this->getDataBySubmission($submission, $publication);
        $mergedWorkData = array_merge(
            $thothWorkData,
            $submissionData
        );
        $newThothWork = $this->new($mergedWorkData);

        $thothClient->updateWork($newThothWork);

        if (isset($thothWorkData['contributions'])) {
            ThothService::contribution()->updateContributions(
                $thothWorkData['contributions'],
                $publication,
                $thothWorkId
            );
        }

        if (isset($thothWorkData['relations'])) {
            $this->updateRelations(
                $thothWorkData['relations'],
                $publication,
                $thothWorkId,
                $thothWorkData['imprintId']
            );
        }

        if (isset($thothWorkData['subjects'])) {
            ThothService::subject()->updateKeywords(
                $thothWorkData['subjects'],
                $publication,
                $thothWorkId
            );
        }

        if (isset($thothWorkData['references'])) {
            ThothService::reference()->updateReferences(
                $thothWorkData['references'],
                $publication,
                $thothWorkId
            );
        }

        if (isset($thothWorkData['publications'])) {
            ThothService::publication()->updateBookPublications(
                $thothWorkData['publications'],
                $publication,
                $thothWorkId
            );
        }

        return $newThothWork;
    }

    public function updateRelations($thothRelations, $publication, $thothWorkId, $thothImprintId)
    {
        $chapterDAO = DAORegistry::getDAO('ChapterDAO');
        $chapters = $chapterDAO->getByPublicationId($publication->getId())->toArray();

        if (empty($chapters) || empty($thothRelations)) {
            return;
        }

        $thothClient = ThothContainer::getInstance()->get('client');

        $thothRelationsData = [];
        foreach ($thothRelations as $thothRelation) {
            $relationFullTitle = $thothRelation['relatedWork']['fullTitle'];
            $thothRelationsData[$relationFullTitle] = $thothRelation;
        }

        $chapterTitles = array_map(function ($chapter) {
            return $chapter->getLocalizedFullTitle();
        }, $chapters);

        foreach ($thothRelationsData as $fullTitle => $relatedWork) {
            if (!in_array($fullTitle, $chapterTitles)) {
                $thothClient->deleteWork($relatedWork['workRelationId']);
            }
        }

        $submissionService = Services::get('submission');
        $submission = $submissionService->get($publication->getData('submissionId'));

        foreach ($chapters as $chapter) {
            $chapterTitle = $chapter->getLocalizedFullTitle();
            if (!isset($thothRelationsData[$chapterTitle])) {
                $this->registerWorkRelation($chapter, $thothImprintId, $thothWorkId);
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

    private function getCompleteWorkQuery()
    {
        return <<<GQL
        query(\$workId: Uuid!) {
            work(workId: \$workId) {
                ...workFields
                contributions {
                    ...contributionFields
                }
                relations {
                    ...relationFields
                }
                subjects {
                    ...subjectFields
                }
                references {
                    ...referenceFields
                }
                publications {
                    ...publicationFields
                }
            }
        }

        fragment workFields on Work {
            workId
            workType
            workStatus
            fullTitle
            title
            subtitle
            reference
            edition
            imprintId
            doi
            publicationDate
            withdrawnDate
            place
            pageCount
            pageBreakdown
            imageCount
            tableCount
            audioCount
            videoCount
            license
            copyrightHolder
            landingPage
            lccn
            oclc
            shortAbstract
            longAbstract
            generalNote
            bibliographyNote
            toc
            coverUrl
            coverCaption
            firstPage
            lastPage
            pageInterval
        }

        fragment contributionFields on Contribution {
            contributionId
            contributorId
            workId
            contributionType
            mainContribution
            biography
            firstName
            lastName
            fullName
            contributionOrdinal
        }

        fragment relationFields on WorkRelation {
            workRelationId
            relatorWorkId
            relatedWorkId
            relationType
            relationOrdinal
            relatedWork {
                ...workFields
            }
        }

        fragment subjectFields on Subject {
            subjectId
            workId
            subjectType
            subjectCode
            subjectOrdinal
        }

        fragment referenceFields on Reference {
            referenceId
            workId
            referenceOrdinal
            unstructuredCitation
        }

        fragment publicationFields on Publication {
            publicationId
            publicationType
            workId
            isbn
            locations {
                ...locationFields
            }
        }

        fragment locationFields on Location {
            locationId
            publicationId
            landingPage
            fullTextUrl
            locationPlatform
            canonical
        }
        GQL;
    }
}
