<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothBiographyService.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBiographyService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth biographies
 */

class ThothBiographyService
{
    public $factory;
    public $repository;

    public function __construct($factory, $repository)
    {
        $this->factory = $factory;
        $this->repository = $repository;
    }

    public function registerByAuthor($author, string $thothContributionId, ?string $preferredLocale = null): void
    {
        $thothBiographies = $this->factory->createFromAuthor($author, $thothContributionId, $preferredLocale);
        $this->register($thothBiographies);
    }

    public function updateByAuthor($author, string $thothContributionId, array $existingBiographies = [], ?string $preferredLocale = null): void
    {
        $thothBiographies = $this->factory->createFromAuthor($author, $thothContributionId, $preferredLocale);
        $this->update($thothBiographies, $existingBiographies);
    }

    private function register(array $thothBiographies): void
    {
        foreach ($thothBiographies as $thothBiography) {
            $this->repository->add($thothBiography);
        }
    }

    private function update(array $thothBiographies, array $existingBiographies): void
    {
        $existingThothBiographiesByLocale = $this->indexEntriesByLocale($existingBiographies, 'biographyId');

        foreach ($thothBiographies as $localeKey => $thothBiography) {
            $existingThothBiography = $existingThothBiographiesByLocale[$localeKey] ?? null;
            if ($existingThothBiography === null) {
                $this->repository->add($thothBiography);
                continue;
            }

            $thothBiography->setBiographyId($existingThothBiography['biographyId']);
            $this->repository->edit($thothBiography);
            unset($existingThothBiographiesByLocale[$localeKey]);
        }

        foreach ($existingThothBiographiesByLocale as $existingThothBiography) {
            $this->repository->delete($existingThothBiography['biographyId']);
        }
    }

    private function indexEntriesByLocale(array $entries, string $idKey): array
    {
        $indexedEntries = [];

        foreach ($entries as $entry) {
            if (!isset($entry[$idKey])) {
                continue;
            }

            $localeKey = $this->factory->getLocaleKey($entry['localeCode'] ?? null);
            if (!isset($indexedEntries[$localeKey]) || ($entry['canonical'] ?? false)) {
                $indexedEntries[$localeKey] = $entry;
            }
        }

        return $indexedEntries;
    }
}
