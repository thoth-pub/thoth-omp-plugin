<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothChapterService.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothChapterService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth chapters
 */

import('plugins.generic.thoth.classes.facades.ThothService');

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
        $chapter->setData('thothChapterId', $thothChapterId);
        $this->registerMetadata($chapter, $thothChapterId);

        ThothService::contribution()->registerByChapter($chapter);
        ThothService::publication()->registerByChapter($chapter);

        return $thothChapterId;
    }

    private function registerMetadata($chapter, $thothChapterId)
    {
        $publication = DAORegistry::getDAO('PublicationDAO')->getById($chapter->getData('publicationId'));
        ThothService::title()->registerByChapter($chapter, $thothChapterId, $publication->getData('locale'));
        ThothService::abstract()->registerByChapter($chapter, $thothChapterId, $publication->getData('locale'));
    }
}
