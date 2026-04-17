<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothTitleService.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothTitleService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth titles
 */

class ThothTitleService
{
    public $factory;
    public $repository;

    public function __construct($factory, $repository)
    {
        $this->factory = $factory;
        $this->repository = $repository;
    }

    public function registerByPublication($publication, string $thothWorkId, ?string $preferredLocale = null): void
    {
        $thothTitles = $this->factory->createFromPublication($publication, $thothWorkId, $preferredLocale);
        $this->register($thothTitles);
    }

    public function updateByPublication($publication, string $thothWorkId, array $existingTitles = [], ?string $preferredLocale = null): void
    {
        $thothTitles = $this->factory->createFromPublication($publication, $thothWorkId, $preferredLocale);
        $this->update($thothTitles, $existingTitles);
    }

    public function registerByChapter($chapter, string $thothWorkId, ?string $preferredLocale = null): void
    {
        $thothTitles = $this->factory->createFromChapter($chapter, $thothWorkId, $preferredLocale);
        $this->register($thothTitles);
    }

    public function updateByChapter($chapter, string $thothWorkId, array $existingTitles = [], ?string $preferredLocale = null): void
    {
        $thothTitles = $this->factory->createFromChapter($chapter, $thothWorkId, $preferredLocale);
        $this->update($thothTitles, $existingTitles);
    }

    private function register(array $thothTitles): void
    {
        foreach ($thothTitles as $thothTitle) {
            $this->repository->add($thothTitle);
        }
    }

    private function update(array $thothTitles, array $existingTitles): void
    {
        $existingThothTitlesByLocale = $this->indexEntriesByLocale($existingTitles, 'titleId');

        foreach ($thothTitles as $localeKey => $thothTitle) {
            $existingThothTitle = $existingThothTitlesByLocale[$localeKey] ?? null;
            if ($existingThothTitle === null) {
                $this->repository->add($thothTitle);
                continue;
            }

            $thothTitle->setTitleId($existingThothTitle['titleId']);
            $this->repository->edit($thothTitle);
            unset($existingThothTitlesByLocale[$localeKey]);
        }

        foreach ($existingThothTitlesByLocale as $existingThothTitle) {
            $this->repository->delete($existingThothTitle['titleId']);
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
