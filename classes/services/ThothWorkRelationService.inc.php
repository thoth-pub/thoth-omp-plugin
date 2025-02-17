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

use ThothApi\GraphQL\Models\WorkRelation as ThothWorkRelation;

import('plugins.generic.thoth.classes.facades.ThothService');

class ThothWorkRelationService
{
    public $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    public function register($chapter, $thothRelatedWorkId, $thothImprintId)
    {
        $thothChapterId = ThothService::chapter()->register($chapter, $thothImprintId);

        $thothWorkRelation = $this->repository->new([
            'relatorWorkId' => $thothChapterId,
            'relatedWorkId' => $thothRelatedWorkId,
            'relationType' => ThothWorkRelation::RELATION_TYPE_IS_CHILD_OF,
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
            ThothService::workRelation()->register($chapter, $thothBookId, $thothImprintId);
        }
    }
}
