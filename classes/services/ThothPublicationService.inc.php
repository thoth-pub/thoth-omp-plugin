<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothPublicationService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth publications
 */

use APP\core\Application;
use APP\facades\Repo;
use Biblys\Isbn\Isbn;
use Biblys\Isbn\IsbnParsingException;
use Biblys\Isbn\IsbnValidationException;
use PKP\db\DAORegistry;
use ThothApi\GraphQL\Enums\WorkStatus;

import('plugins.generic.thoth.classes.exceptions.MetadataSynchronizationException');

class ThothPublicationService
{
    public $factory;
    public $repository;
    public $locationService;

    public function __construct($factory, $repository, $locationService)
    {
        $this->factory = $factory;
        $this->repository = $repository;
        $this->locationService = $locationService;
    }

    public function register($publicationFormat, $thothWorkId, $chapterId = null, $submissionFile = null)
    {
        $thothPublication = $this->factory->createFromPublicationFormat($publicationFormat, $submissionFile);
        $thothPublication->setWorkId($thothWorkId);

        if ($chapterId !== null) {
            $thothPublication->setIsbn(null);
        }

        $thothPublicationId = $this->repository->getIdByType(
            $thothWorkId,
            $thothPublication->getPublicationType()
        );

        if ($thothPublicationId === null) {
            $thothPublicationId = $this->repository->add($thothPublication);
        }

        $publicationFormat->setData('thothPublicationId', $thothPublicationId);

        $this->locationService->registerByPublicationFormat($publicationFormat, $chapterId);

        return $thothPublicationId;
    }

    public function registerByPublication($publication)
    {
        $thothBookId = $publication->getData('thothBookId');
        $submissionFiles = $this->getBookSubmissionFiles($publication);
        $submissionFilesByPublicationFormat = $this->getSubmissionFilesByPublicationFormat($submissionFiles);

        $publicationFormats = DAORegistry::getDAO('PublicationFormatDAO')
            ->getByPublicationId($publication->getId());
        foreach ($publicationFormats as $publicationFormat) {
            $publicationFormatFiles = $submissionFilesByPublicationFormat[$publicationFormat->getId()] ?? [];
            $submissionFile = $publicationFormatFiles[0] ?? null;
            if (!$this->canRegister($publicationFormat, $submissionFile)) {
                continue;
            }

            $this->register(
                $publicationFormat,
                $thothBookId,
                null,
                $submissionFile
            );
        }
    }

    public function synchronizeByPublication($publication, $thothWorkId)
    {
        $submissionFiles = $this->getBookSubmissionFiles($publication);
        $publicationFormats = DAORegistry::getDAO('PublicationFormatDAO')
            ->getByPublicationId($publication->getId());
        $thothWork = $this->repository->getByWorkId($thothWorkId);

        return $this->update(
            $publicationFormats,
            $thothWorkId,
            $thothWork['publications'],
            $this->getSubmissionFilesByPublicationFormat($submissionFiles),
            $thothWork['workStatus']
        );
    }

    public function update(
        iterable $publicationFormats,
        $thothWorkId,
        array $existingPublications,
        array $submissionFilesByPublicationFormat = [],
        $workStatus = null
    ) {
        $remainingPublications = $existingPublications;

        foreach ($publicationFormats as $publicationFormat) {
            $publicationFormatFiles = $submissionFilesByPublicationFormat[$publicationFormat->getId()] ?? [];
            $submissionFile = $publicationFormatFiles[0] ?? null;
            if (!$this->canRegister($publicationFormat, $submissionFile)) {
                continue;
            }

            $thothPublication = $this->factory->createFromPublicationFormat($publicationFormat, $submissionFile);
            $thothPublication->setWorkId($thothWorkId);
            $desiredLocations = $this->locationService->getDesiredByPublicationFormat(
                $publicationFormat,
                $publicationFormatFiles
            );
            $existingKey = $this->findMatchingPublicationKey(
                $thothPublication,
                $desiredLocations,
                $remainingPublications
            );

            if ($existingKey === null) {
                $thothPublicationId = $this->repository->add($thothPublication);
                $existingLocations = [];
            } else {
                $thothPublicationId = $remainingPublications[$existingKey]['publicationId'];
                $existingLocations = $remainingPublications[$existingKey]['locations'] ?? [];
                $thothPublication->setPublicationId($thothPublicationId);
                $this->repository->edit($thothPublication);
                unset($remainingPublications[$existingKey]);
            }

            $publicationFormat->setData('thothPublicationId', $thothPublicationId);
            $this->locationService->update(
                $thothPublicationId,
                $desiredLocations,
                $existingLocations
            );
        }

        if ($workStatus === WorkStatus::ACTIVE && !empty($remainingPublications)) {
            return true;
        }

        foreach ($remainingPublications as $remainingPublication) {
            $this->repository->delete($remainingPublication['publicationId']);
        }

        return false;
    }

    public function registerByChapter($chapter)
    {
        $publication = Repo::publication()->get($chapter->getData('publicationId'));
        $submissionFiles = iterator_to_array(
            Repo::submissionFile()->getCollector()
                ->filterBySubmissionIds([$publication->getData('submissionId')])
                ->filterByAssoc(Application::ASSOC_TYPE_PUBLICATION_FORMAT)
                ->getMany()
        );

        $chapterSubmissionFiles = array_filter($submissionFiles, function ($submissionFile) use ($chapter) {
            return $submissionFile->getData('chapterId') == $chapter->getId();
        });
        $submissionFilesByPublicationFormat = $this->getSubmissionFilesByPublicationFormat($chapterSubmissionFiles);

        $publicationFormatIds = array_map(function ($file) {
            return $file->getData('assocId');
        }, $chapterSubmissionFiles);

        $thothChapterId = $chapter->getData('thothChapterId');
        $publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');

        $publicationFormats = [];
        foreach (array_unique($publicationFormatIds) as $publicationFormatId) {
            $publicationFormat = $publicationFormatDao->getById($publicationFormatId);
            if ($publicationFormat) {
                $publicationFormats[$publicationFormatId] = $publicationFormat;
            }
        }

        foreach ($publicationFormats as $publicationFormat) {
            $publicationFormatFiles = $submissionFilesByPublicationFormat[$publicationFormat->getId()] ?? [];
            $this->register(
                $publicationFormat,
                $thothChapterId,
                $chapter->getId(),
                $publicationFormatFiles[0] ?? null
            );
        }
    }

    public function validate($publicationFormat)
    {
        $errors = [];

        $thothPublication = $this->factory->createFromPublicationFormat($publicationFormat);
        if ($isbn = $thothPublication->getIsbn()) {
            $isbnValidationMessage = __(
                'plugins.generic.thoth.validation.isbn',
                ['isbn' => $isbn,'formatName' => $publicationFormat->getLocalizedName()]
            );
            try {
                Isbn::validateAsIsbn13($isbn);
            } catch (IsbnParsingException $e) {
                $errors[] = $isbnValidationMessage;
            } catch (IsbnValidationException $e) {
                $errors[] = $isbnValidationMessage;
            }

            $retrievedThothPublication = $this->repository->find($isbn);
            if ($retrievedThothPublication !== null) {
                $errors[] = __(
                    'plugins.generic.thoth.validation.isbnExists',
                    ['isbn' => $isbn]
                );
            }
        }

        return $errors;
    }

    public function canRegister($publicationFormat, $submissionFile = null)
    {
        if ($publicationFormat->getPhysicalFormat()) {
            return true;
        }

        if ($submissionFile !== null) {
            return true;
        }

        $submissionFiles = array_filter(
            iterator_to_array(Repo::submissionFile()
                ->getCollector()
                ->filterByAssoc(
                    Application::ASSOC_TYPE_PUBLICATION_FORMAT,
                    [$publicationFormat->getId()]
                )
                ->getMany()),
            function ($submissionFile) {
                return $submissionFile->getData('chapterId') == null;
            }
        );

        return count($submissionFiles) > 0 || !empty($publicationFormat->getRemoteUrl());
    }

    private function findMatchingPublicationKey($thothPublication, array $desiredLocations, array $existingPublications)
    {
        $typeMatches = array_filter(
            $existingPublications,
            function ($existingPublication) use ($thothPublication) {
                return ($existingPublication['publicationType'] ?? null) === $thothPublication->getPublicationType();
            }
        );
        if (empty($typeMatches)) {
            return null;
        }

        $exactMatches = array_filter(
            $typeMatches,
            function ($existingPublication) use ($thothPublication, $desiredLocations) {
                return $this->normalizePublication($thothPublication->getAllData(), $desiredLocations)
                    === $this->normalizePublication(
                        $existingPublication,
                        $existingPublication['locations'] ?? []
                    );
            }
        );
        if (count($exactMatches) === 1) {
            return array_key_first($exactMatches);
        }

        $isbn = $this->normalizeIsbn($thothPublication->getIsbn());
        if ($isbn !== null) {
            $isbnMatches = array_filter(
                $typeMatches,
                function ($existingPublication) use ($isbn) {
                    return $this->normalizeIsbn($existingPublication['isbn'] ?? null) === $isbn;
                }
            );
            if (count($isbnMatches) === 1) {
                return array_key_first($isbnMatches);
            }
        }

        $normalizedLocations = $this->normalizeLocations($desiredLocations);
        if (!empty($normalizedLocations)) {
            $locationMatches = array_filter(
                $typeMatches,
                function ($existingPublication) use ($normalizedLocations) {
                    return $this->normalizeLocations($existingPublication['locations'] ?? []) === $normalizedLocations;
                }
            );
            if (count($locationMatches) === 1) {
                return array_key_first($locationMatches);
            }
        }

        if (count($typeMatches) === 1) {
            return array_key_first($typeMatches);
        }

        throw new MetadataSynchronizationException('Ambiguous Thoth publications for the same publication type');
    }

    private function normalizePublication(array $publication, array $locations)
    {
        return [
            'publicationType' => $publication['publicationType'] ?? null,
            'isbn' => $this->normalizeIsbn($publication['isbn'] ?? null),
            'accessibilityStandard' => $this->normalizeOptionalValue($publication['accessibilityStandard'] ?? null),
            'accessibilityAdditionalStandard' => $this->normalizeOptionalValue(
                $publication['accessibilityAdditionalStandard'] ?? null
            ),
            'accessibilityException' => $this->normalizeOptionalValue(
                $publication['accessibilityException'] ?? null
            ),
            'accessibilityReportUrl' => $this->normalizeOptionalValue(
                $publication['accessibilityReportUrl'] ?? null
            ),
            'locations' => $this->normalizeLocations($locations),
        ];
    }

    private function normalizeLocations(array $locations)
    {
        $normalized = array_map(function ($location) {
            $location = is_object($location) ? $location->getAllData() : $location;
            return [
                'landingPage' => $this->normalizeOptionalValue($location['landingPage'] ?? null),
                'fullTextUrl' => $this->normalizeOptionalValue($location['fullTextUrl'] ?? null),
                'locationPlatform' => $this->normalizeOptionalValue($location['locationPlatform'] ?? null),
            ];
        }, $locations);
        usort($normalized, function (array $first, array $second) {
            return strcmp(json_encode($first), json_encode($second));
        });
        return $normalized;
    }

    private function normalizeIsbn($isbn)
    {
        $isbn = strtoupper(preg_replace('/[^0-9X]/i', '', (string) $isbn));
        return $isbn === '' ? null : $isbn;
    }

    private function normalizeOptionalValue($value)
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function getBookSubmissionFiles($publication)
    {
        return array_filter(
            iterator_to_array(Repo::submissionFile()
                ->getCollector()
                ->filterBySubmissionIds([$publication->getData('submissionId')])
                ->filterByAssoc(Application::ASSOC_TYPE_PUBLICATION_FORMAT)
                ->getMany()),
            function ($submissionFile) {
                return $submissionFile->getData('chapterId') == null;
            }
        );
    }

    private function getSubmissionFilesByPublicationFormat(array $submissionFiles)
    {
        $submissionFilesByPublicationFormat = [];
        foreach ($submissionFiles as $submissionFile) {
            $publicationFormatId = $submissionFile->getData('assocId');
            $submissionFilesByPublicationFormat[$publicationFormatId][] = $submissionFile;
        }

        return $submissionFilesByPublicationFormat;
    }
}
