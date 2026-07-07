<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothWorkRelationService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkRelationService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth work relations
 */

use PKP\db\DAORegistry;
use ThothApi\GraphQL\Enums\RelationType;

class ThothWorkRelationService
{
    public $repository;
    public $chapterService;

    public function __construct($repository, $chapterService)
    {
        $this->repository = $repository;
        $this->chapterService = $chapterService;
    }

    public function register($chapter, $thothRelatedWorkId, $thothImprintId)
    {
        $thothChapterId = $this->chapterService->register($chapter, $thothImprintId);

        $thothWorkRelation = $this->repository->new([
            'relatorWorkId' => $thothChapterId,
            'relatedWorkId' => $thothRelatedWorkId,
            'relationType' => RelationType::IS_CHILD_OF,
            'relationOrdinal' => ($chapter->getSequence() + 1)
        ]);

        return $this->repository->add($thothWorkRelation);
    }

    public function registerByPublication($publication, $thothImprintId)
    {
        $thothBookId = $publication->getData('thothBookId');
        $chapters = DAORegistry::getDAO('ChapterDAO')
            ->getByPublicationId($publication->getId())
            ->toArray();
        foreach ($chapters as $chapter) {
            $this->register($chapter, $thothBookId, $thothImprintId);
        }
    }
}
