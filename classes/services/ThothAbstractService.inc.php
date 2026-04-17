<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothAbstractService.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAbstractService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth abstracts
 */

class ThothAbstractService
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
        $thothAbstracts = $this->factory->createFromPublication($publication, $thothWorkId, $preferredLocale);
        $this->register($thothAbstracts);
    }

    public function updateByPublication($publication, string $thothWorkId, array $existingAbstracts = [], ?string $preferredLocale = null): void
    {
        $thothAbstracts = $this->factory->createFromPublication($publication, $thothWorkId, $preferredLocale);
        $this->update($thothAbstracts, $existingAbstracts);
    }

    public function registerByChapter($chapter, string $thothWorkId, ?string $preferredLocale = null): void
    {
        $thothAbstracts = $this->factory->createFromChapter($chapter, $thothWorkId, $preferredLocale);
        $this->register($thothAbstracts);
    }

    public function updateByChapter($chapter, string $thothWorkId, array $existingAbstracts = [], ?string $preferredLocale = null): void
    {
        $thothAbstracts = $this->factory->createFromChapter($chapter, $thothWorkId, $preferredLocale);
        $this->update($thothAbstracts, $existingAbstracts);
    }

    private function register(array $thothAbstracts): void
    {
        foreach ($thothAbstracts as $thothAbstract) {
            $this->repository->add($thothAbstract);
        }
    }

    private function update(array $thothAbstracts, array $existingAbstracts): void
    {
        $existingThothAbstractsByLocale = $this->indexEntriesByLocale($existingAbstracts, 'abstractId');

        foreach ($thothAbstracts as $localeKey => $thothAbstract) {
            $existingThothAbstract = $existingThothAbstractsByLocale[$localeKey] ?? null;
            if ($existingThothAbstract === null) {
                $this->repository->add($thothAbstract);
                continue;
            }

            $thothAbstract->setAbstractId($existingThothAbstract['abstractId']);
            $this->repository->edit($thothAbstract);
            unset($existingThothAbstractsByLocale[$localeKey]);
        }

        foreach ($existingThothAbstractsByLocale as $existingThothAbstract) {
            $this->repository->delete($existingThothAbstract['abstractId']);
        }
    }

    private function indexEntriesByLocale(array $entries, string $idKey): array
    {
        $indexedEntries = [];

        foreach ($entries as $entry) {
            if (!isset($entry[$idKey]) || ($entry['abstractType'] ?? null) !== 'LONG') {
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
