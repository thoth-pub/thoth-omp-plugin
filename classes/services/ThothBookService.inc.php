<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothBookService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth books
 */

use PKP\db\DAORegistry;
use ThothApi\GraphQL\Models\Work as ThothWork;

import('plugins.generic.thoth.classes.facades.ThothService');
import('lib.pkp.classes.services.PKPSchemaService');

class ThothBookService
{
    public $factory;
    public $repository;

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

        if ($thothBook->getWorkStatus() === ThothWork::WORK_STATUS_ACTIVE) {
            $this->setOriginalThothBook($thothBook);
            $thothBook->setWorkStatus(ThothWork::WORK_STATUS_FORTHCOMING);
        }

        $thothBookId = $this->repository->add($thothBook);
        $publication->setData('thothBookId', $thothBookId);
        $this->setRegisteredEntryId($thothBookId);

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
            $oldThothBook->getAllData(),
            $newThothBook->getAllData()
        ));

        $this->repository->edit($thothBook);
    }

    public function validate($publication)
    {
        $errors = [];

        $thothBook = $this->factory->createFromPublication($publication);
        if ($doi = $thothBook->getDoi()) {
            $retrievedThothBook = $this->repository->getByDoi($doi);
            if ($retrievedThothBook !== null) {
                $errors[] = __(
                    'plugins.generic.thoth.validation.doiExists',
                    ['doi' => $doi]
                );
            }
        }

        if ($landingPage = $thothBook->getLandingPage()) {
            $retrievedThothBook = $this->repository->find($landingPage);
            if (
                $retrievedThothBook !== null
                && $retrievedThothBook->getLandingPage() === $landingPage
            ) {
                $errors[] = __(
                    'plugins.generic.thoth.validation.landingPageExists',
                    ['landingPage' => $landingPage]
                );
            }
        }

        $publicationFormats = DAORegistry::getDAO('PublicationFormatDAO')
            ->getByPublicationId($publication->getId());
        foreach ($publicationFormats as $publicationFormat) {
            $errors = array_merge(
                $errors,
                ThothService::publication()->validate($publicationFormat)
            );
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
        $thothBook->setWorkStatus(ThothWork::WORK_STATUS_ACTIVE);
        $this->repository->edit($thothBook);
    }
}
