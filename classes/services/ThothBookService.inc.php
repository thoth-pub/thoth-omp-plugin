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

import('lib.pkp.classes.services.PKPSchemaService');
import('plugins.generic.thoth.classes.services.ThothFrontcoverService');

class ThothBookService
{
    public $factory;
    public $repository;
    public $publicationService;
    public $titleService;
    public $abstractService;
    private $frontcoverService;

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

    public function __construct(
        $factory,
        $repository,
        $publicationService,
        $titleService,
        $abstractService,
        $frontcoverService = null
    ) {
        $this->factory = $factory;
        $this->repository = $repository;
        $this->publicationService = $publicationService;
        $this->titleService = $titleService;
        $this->abstractService = $abstractService;
        $this->frontcoverService = $frontcoverService;
    }

    public function register($publication, $thothImprintId)
    {
        $thothBook = $this->factory->createFromPublication($publication);
        $thothBook->setImprintId($thothImprintId);

        $thothBookId = $this->repository->add($thothBook);
        $publication->setData('thothBookId', $thothBookId);
        if ($this->frontcoverService) {
            $this->frontcoverService->sync($publication, $thothBookId);
        }

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
        if ($this->frontcoverService) {
            return $this->frontcoverService->sync($publication, $thothBookId);
        }

        return null;
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
            $errors = array_merge($errors, $this->publicationService->validate($publicationFormat));
        }

        return $errors;
    }

}
