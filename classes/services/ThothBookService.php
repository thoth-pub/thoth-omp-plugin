<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothBookService.php
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

namespace APP\plugins\generic\thoth\classes\services;

use APP\plugins\generic\thoth\classes\factories\ThothBookFactory;
use APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource;
use APP\plugins\generic\thoth\classes\repositories\ThothBookRepository;
use PKP\db\DAORegistry;

class ThothBookService
{
    private ThothBookFactory $factory;
    private ThothBookRepository $repository;
    private ThothPublicationService $publicationService;
    private ThothTitleService $titleService;
    private ThothAbstractService $abstractService;
    private ?ThothFrontcoverService $frontcoverService;

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
        ThothBookFactory $factory,
        ThothBookRepository $repository,
        ThothPublicationService $publicationService,
        ThothTitleService $titleService,
        ThothAbstractService $abstractService,
        private OmpMetadataSource $metadataSource,
        ?ThothFrontcoverService $frontcoverService = null
    ) {
        $this->factory = $factory;
        $this->repository = $repository;
        $this->publicationService = $publicationService;
        $this->titleService = $titleService;
        $this->abstractService = $abstractService;
        $this->frontcoverService = $frontcoverService;
    }

    public function update($publication, $thothBookId, bool $includeTitlesAndAbstracts = false): array
    {
        $oldThothBook = $this->repository->get($thothBookId);
        $newThothBook = $this->factory->createFromPublication(
            $publication,
            $this->metadataSource->getBookContext($publication)
        );

        $thothBook = $this->repository->new(array_merge(
            $this->getPatchWorkData($oldThothBook),
            $newThothBook->getAllData()
        ));

        $this->repository->edit($thothBook);
        if ($includeTitlesAndAbstracts) {
            $this->updateTitlesAndAbstracts($publication, $thothBookId, $oldThothBook);
        }
        $warning = $this->frontcoverService?->sync($publication, $thothBookId);
        return $warning === null ? [] : [$warning];
    }

    private function getPatchWorkData($thothBook): array
    {
        return array_intersect_key($thothBook->toArray(), self::PATCH_WORK_FIELDS);
    }

    private function updateTitlesAndAbstracts($publication, string $thothBookId, $oldThothBook): void
    {
        $oldThothBookData = $oldThothBook->toArray();
        $locale = $publication->getData('locale');

        $this->titleService->updateByPublication(
            $publication,
            $thothBookId,
            $oldThothBookData['titles'] ?? [],
            $locale
        );
        $this->abstractService->updateByPublication(
            $publication,
            $thothBookId,
            $oldThothBookData['abstracts'] ?? [],
            $locale
        );
    }

    public function validate($publication)
    {
        $errors = [];

        $thothBook = $this->factory->createFromPublication(
            $publication,
            $this->metadataSource->getBookContext($publication)
        );
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
                $this->publicationService->validate($publicationFormat)
            );
        }

        return $errors;
    }

}
