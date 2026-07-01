<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothBookService.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth books
 */

use PKP\db\DAORegistry;
use ThothApi\GraphQL\Enums\WorkStatus;

import('plugins.generic.thoth.classes.facades.ThothService');
import('lib.pkp.classes.services.PKPSchemaService');

class ThothBookService
{
    public $factory;
    public $repository;

    private const PATCH_WORK_FIELDS = [
        'workId' => true,
        'workType' => true,
        'workStatus' => true,
        'reference' => true,
        'edition' => true,
        'imprintId' => true,
        'doi' => true,
        'publicationDate' => true,
        'withdrawnDate' => true,
        'place' => true,
        'pageCount' => true,
        'pageBreakdown' => true,
        'imageCount' => true,
        'tableCount' => true,
        'audioCount' => true,
        'videoCount' => true,
        'license' => true,
        'copyrightHolder' => true,
        'landingPage' => true,
        'lccn' => true,
        'oclc' => true,
        'generalNote' => true,
        'bibliographyNote' => true,
        'toc' => true,
        'resourcesDescription' => true,
        'coverUrl' => true,
        'coverCaption' => true,
        'firstPage' => true,
        'lastPage' => true,
        'pageInterval' => true,
    ];

    private $originalThothBook;
    private $registeredEntryId;

    public function __construct($factory, $repository)
    {
        $this->factory = $factory;
        $this->repository = $repository;
    }

    public function getOriginalThothBook()
    {
        return $this->originalThothBook;
    }

    public function setOriginalThothBook($originalThothBook)
    {
        $this->originalThothBook = $originalThothBook;
    }

    public function getRegisteredEntryId()
    {
        return $this->registeredEntryId;
    }

    public function setRegisteredEntryId($registeredEntryId)
    {
        $this->registeredEntryId = $registeredEntryId;
    }

    public function register($publication, $thothImprintId)
    {
        $thothBook = $this->factory->createFromPublication($publication);
        $thothBook->setImprintId($thothImprintId);

        if ($thothBook->getWorkStatus() === WorkStatus::ACTIVE) {
            $this->setOriginalThothBook($thothBook);
            $thothBook->setWorkStatus(WorkStatus::FORTHCOMING);
        }

        $thothBookId = $this->repository->add($thothBook);
        $publication->setData('thothBookId', $thothBookId);
        $this->setRegisteredEntryId($thothBookId);
        $this->registerMetadata($publication, $thothBookId);

        ThothService::contribution()->registerByPublication($publication);
        ThothService::publication()->registerByPublication($publication);
        ThothService::language()->registerByPublication($publication);
        ThothService::subject()->registerByPublication($publication);
        ThothService::reference()->registerByPublication($publication);
        ThothService::workRelation()->registerByPublication($publication, $thothImprintId);

        return $thothBookId;
    }

    public function update($publication, $thothBookId)
    {
        $oldThothBook = $this->repository->get($thothBookId);
        $newThothBook = $this->factory->createFromPublication($publication);

        $thothBook = $this->repository->new(array_merge(
            $this->getPatchWorkData($oldThothBook),
            $newThothBook->getAllData()
        ));

        $this->repository->edit($thothBook);
        $this->updateMetadata($publication, $thothBookId, $oldThothBook);
    }

    private function getPatchWorkData($thothBook): array
    {
        return array_intersect_key($thothBook->toArray(), self::PATCH_WORK_FIELDS);
    }

    public function validate($publication)
    {
        $errors = [];

        $thothBook = $this->factory->createFromPublication($publication);
        if ($doi = $thothBook->getDoi()) {
            $retrievedThothBook = $this->repository->getByDoi($doi);
            if ($retrievedThothBook !== null) {
                $errors[] = __('plugins.generic.thoth.validation.doiExists', ['doi' => $doi]);
            }
        }

        if ($landingPage = $thothBook->getLandingPage()) {
            $retrievedThothBook = $this->repository->find($landingPage);
            if ($retrievedThothBook !== null && $retrievedThothBook->getLandingPage() === $landingPage) {
                $errors[] = __('plugins.generic.thoth.validation.landingPageExists', ['landingPage' => $landingPage]);
            }
        }

        $publicationFormats = DAORegistry::getDAO('PublicationFormatDAO')->getByPublicationId($publication->getId());
        foreach ($publicationFormats as $publicationFormat) {
            $errors = array_merge($errors, ThothService::publication()->validate($publicationFormat));
        }

        return $errors;
    }

    public function deleteRegisteredEntry()
    {
        if ($this->getRegisteredEntryId() === null) {
            return;
        }

        $this->repository->delete($this->getRegisteredEntryId());
        $this->setRegisteredEntryId(null);
    }

    public function setActive()
    {
        if ($this->getOriginalThothBook() === null) {
            return;
        }

        $thothBook = $this->getOriginalThothBook();
        $thothBook->setWorkId($this->getRegisteredEntryId());
        $thothBook->setWorkStatus(WorkStatus::ACTIVE);
        $this->repository->edit($thothBook);
    }

    private function registerMetadata($publication, $thothBookId)
    {
        ThothService::title()->registerByPublication(
            $publication,
            $thothBookId,
            $publication->getData('locale')
        );
        ThothService::abstract()->registerByPublication(
            $publication,
            $thothBookId,
            $publication->getData('locale')
        );
    }

    private function updateMetadata($publication, $thothBookId, $oldThothBook)
    {
        ThothService::title()->updateByPublication(
            $publication,
            $thothBookId,
            $oldThothBook->toArray()['titles'] ?? [],
            $publication->getData('locale')
        );
        ThothService::abstract()->updateByPublication(
            $publication,
            $thothBookId,
            $oldThothBook->toArray()['abstracts'] ?? [],
            $publication->getData('locale')
        );
    }
}
