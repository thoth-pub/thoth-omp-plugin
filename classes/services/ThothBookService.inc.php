<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothBookService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth books
 */

use ThothApi\GraphQL\Models\Work as ThothWork;

import('plugins.generic.thoth.classes.facades.ThothService');
import('lib.pkp.classes.services.PKPSchemaService');

class ThothBookService
{
    public $factory;
    public $repository;

    public function __construct($factory, $repository)
    {
        $this->factory = $factory;
        $this->repository = $repository;
    }

    public function register($publication, $thothImprintId)
    {
        $thothBook = $this->factory->createFromPublication($publication);
        $thothBook->setImprintId($thothImprintId);

        $thothBookId = $this->repository->add($thothBook);
        $thothBook->setWorkId($thothBookId);

        $authors = DAORegistry::getDAO('AuthorDAO')->getByPublicationId($publication->getId());
        $primaryContactId = $publication->getData('primaryContactId');
        foreach ($authors as $author) {
            ThothService::contribution()->register($author, $thothBookId, $primaryContactId);
        }

        $publicationFormats = DAORegistry::getDAO('PublicationFormatDAO')
            ->getApprovedByPublicationId($publication->getId())
            ->toArray();
        foreach ($publicationFormats as $publicationFormat) {
            if ($publicationFormat->getIsAvailable()) {
                ThothService::publication()->register($publicationFormat, $thothBookId);
            }
        }

        $locale = $publication->getData('locale');
        ThothService::language()->register($locale, $thothBookId);

        $keywords = $publication->getData('keywords');
        foreach ($keywords[$locale] ?? [] as $seq => $keyword) {
            ThothService::subject()->register($keyword, ($seq + 1), $thothBookId);
        }

        $citations = DAORegistry::getDAO('CitationDAO')
            ->getByPublicationId($publication->getId())
            ->toArray();
        foreach ($citations as $citation) {
            ThothService::reference()->register($citation, $thothBookId);
        }

        $chapters = DAORegistry::getDAO('ChapterDAO')
            ->getByPublicationId($publication->getId())
            ->toArray();
        foreach ($chapters as $chapter) {
            ThothService::workRelation()->register($chapter, $thothBook);
        }

        return $thothBookId;
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
            if ($retrievedThothBook !== null) {
                $errors[] = __(
                    'plugins.generic.thoth.validation.landingPageExists',
                    ['landingPage' => $landingPage]
                );
            }
        }

        $publicationFormats = DAORegistry::getDAO('PublicationFormatDAO')
            ->getApprovedByPublicationId($publication->getId())
            ->toArray();
        foreach ($publicationFormats as $publicationFormat) {
            if ($publicationFormat->getIsAvailable()) {
                $errors = array_merge(
                    $errors,
                    ThothService::publication()->validate($publicationFormat)
                );
            }
        }

        return $errors;
    }
}
