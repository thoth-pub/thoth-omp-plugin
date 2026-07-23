<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothLocationService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth locations
 */

use ThothApi\GraphQL\Enums\LocationPlatform;

import('plugins.generic.thoth.classes.exceptions.MetadataSynchronizationException');

class ThothLocationService
{
    public $factory;
    public $repository;

    public function __construct($factory, $repository)
    {
        $this->factory = $factory;
        $this->repository = $repository;
    }

    public function register($publicationFormat, $thothPublicationId, $fileId = null)
    {
        $thothLocation = $this->factory->createFromPublicationFormat($publicationFormat, $fileId);
        $thothLocation->setPublicationId($thothPublicationId);
        $thothLocation->setCanonical(!$this->repository->hasCanonical($thothPublicationId));

        return $this->repository->add($thothLocation);
    }

    public function registerByPublicationFormat($publicationFormat, $chapterId = null)
    {
        $submissionFiles = array_filter(
            iterator_to_array(Services::get('submissionFile')->getMany([
                'assocTypes' => [ASSOC_TYPE_PUBLICATION_FORMAT],
                'assocIds' => [$publicationFormat->getId()],
            ])),
            function ($submissionFile) use ($chapterId) {
                return $submissionFile->getData('chapterId') == $chapterId;
            }
        );

        $thothPublicationId = $publicationFormat->getData('thothPublicationId');
        if (empty($submissionFiles) && $publicationFormat->getRemoteUrl()) {
            $this->register($publicationFormat, $thothPublicationId);
        }

        foreach ($submissionFiles as $submissionFile) {
            $this->register($publicationFormat, $thothPublicationId, $submissionFile->getId());
        }
    }

    public function update($thothPublicationId, array $desiredLocations, array $existingLocations)
    {
        $hasCanonicalThothLocation = false;
        $remainingLocations = [];
        foreach ($existingLocations as $key => $existingLocation) {
            if (($existingLocation['locationPlatform'] ?? null) === LocationPlatform::THOTH) {
                $hasCanonicalThothLocation = $hasCanonicalThothLocation
                    || ($existingLocation['canonical'] ?? false);
                continue;
            }

            $remainingLocations[$key] = $existingLocation;
        }

        foreach (array_values($desiredLocations) as $index => $thothLocation) {
            $thothLocation->setPublicationId($thothPublicationId);
            $thothLocation->setCanonical($index === 0 && !$hasCanonicalThothLocation);

            $existingKey = $this->findMatchingLocationKey($thothLocation, $remainingLocations);
            if ($thothLocation->getCanonical() && !($remainingLocations[$existingKey]['canonical'] ?? false)) {
                $canonicalKey = $this->findCanonicalLocationKey($remainingLocations);
                if ($canonicalKey !== null) {
                    $existingKey = $canonicalKey;
                }
            }
            if ($existingKey === null) {
                $this->repository->add($thothLocation);
                continue;
            }

            $thothLocation->setLocationId($remainingLocations[$existingKey]['locationId']);
            $this->repository->edit($thothLocation);
            unset($remainingLocations[$existingKey]);
        }

        foreach ($remainingLocations as $remainingLocation) {
            $this->repository->delete($remainingLocation['locationId']);
        }
    }

    public function getDesiredByPublicationFormat($publicationFormat, array $submissionFiles)
    {
        if (empty($submissionFiles)) {
            return $publicationFormat->getRemoteUrl()
                ? [$this->factory->createFromPublicationFormat($publicationFormat)]
                : [];
        }

        return array_map(function ($submissionFile) use ($publicationFormat) {
            return $this->factory->createFromPublicationFormat($publicationFormat, $submissionFile->getId());
        }, array_values($submissionFiles));
    }

    private function findMatchingLocationKey($thothLocation, array $existingLocations)
    {
        $fullTextUrl = $this->normalizeUrl($thothLocation->getFullTextUrl());
        $matchingKeys = array_keys(array_filter(
            $existingLocations,
            function ($existingLocation) use ($fullTextUrl) {
                return $fullTextUrl !== null
                    && $fullTextUrl === $this->normalizeUrl($existingLocation['fullTextUrl'] ?? null);
            }
        ));
        if (!empty($matchingKeys)) {
            return $this->getUniqueMatchingKey($matchingKeys);
        }

        $landingPage = $this->normalizeUrl($thothLocation->getLandingPage());
        $matchingKeys = array_keys(array_filter(
            $existingLocations,
            function ($existingLocation) use ($fullTextUrl, $landingPage, $thothLocation) {
                return $fullTextUrl === null
                    && $this->normalizeUrl($existingLocation['fullTextUrl'] ?? null) === null
                    && $landingPage === $this->normalizeUrl($existingLocation['landingPage'] ?? null)
                    && $thothLocation->getLocationPlatform() === ($existingLocation['locationPlatform'] ?? null);
            }
        ));
        return $this->getUniqueMatchingKey($matchingKeys);
    }

    private function findCanonicalLocationKey(array $existingLocations)
    {
        foreach ($existingLocations as $key => $existingLocation) {
            if ($existingLocation['canonical'] ?? false) {
                return $key;
            }
        }

        return null;
    }

    private function getUniqueMatchingKey(array $matchingKeys)
    {
        if (count($matchingKeys) > 1) {
            throw new MetadataSynchronizationException('Ambiguous Thoth locations with the same semantic URL');
        }

        return $matchingKeys[0] ?? null;
    }

    private function normalizeUrl($url)
    {
        $url = trim((string) $url);
        return $url === '' ? null : $url;
    }
}
