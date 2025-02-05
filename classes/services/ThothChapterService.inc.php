<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothChapterService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothChapterService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth Chapters
 */

use ThothApi\GraphQL\Models\WorkRelation as ThothWorkRelation;

class ThothChapterService
{
    public $factory;
    public $repository;

    public function __construct($factory, $repository)
    {
        $this->factory = $factory;
        $this->repository = $repository;
    }

    public function register($chapter, $thothImprintId)
    {
        $thothChapter = $this->factory->createFromChapter($chapter);
        $thothChapter->setImprintId($thothImprintId);

        $thothChapterId = $this->repository->add($thothChapter);

        $authors = $chapter->getAuthors()->toArray();
        foreach ($authors as $author) {
            ThothService::contribution()->register($author, $thothChapterId);
        }

        $publication = Services::get('publication')->get($chapter->getData('publicationId'));
        $submissionFiles = iterator_to_array(
            Services::get('submissionFile')->getMany([
                'assocTypes' => [ASSOC_TYPE_PUBLICATION_FORMAT],
                'submissionIds' => [$publication->getData('submissionId')],
            ])
        );
        $chapterSubmissionFiles = array_filter($submissionFiles, function ($submissionFile) use ($chapter) {
            return $submissionFile->getData('chapterId') == $chapter->getId();
        });

        $publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');
        foreach ($chapterSubmissionFiles as $chapterSubmissionFile) {
            $publicationFormat = $publicationFormatDao->getById($chapterSubmissionFile->getData('assocId'));
            if ($publicationFormat->getIsAvailable()) {
                ThothService::publication()->register($publicationFormat, $thothChapterId, $chapter->getId());
            }
        }

        return $thothChapterId;
    }
}
