<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothTitleFactory.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothTitleFactory
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth titles
 */

use ThothApi\GraphQL\Models\Title as ThothTitle;

import('plugins.generic.thoth.classes.i18n.ThothLocaleCode');

class ThothTitleFactory
{
    public function createFromPublication($publication, string $workId, ?string $preferredLocale = null): array
    {
        return $this->create($publication, $workId, $preferredLocale);
    }

    public function createFromChapter($chapter, string $workId, ?string $preferredLocale = null): array
    {
        return $this->create($chapter, $workId, $preferredLocale);
    }

    private function create($entity, string $workId, ?string $preferredLocale = null): array
    {
        $canonicalLocale = $this->getCanonicalLocale($entity, $preferredLocale);
        $titles = $this->getLocalizedValues($entity, 'title', $canonicalLocale);
        $subtitles = $this->getLocalizedValues($entity, 'subtitle');
        $thothTitles = [];

        foreach ($titles as $locale => $title) {
            $localeCode = $this->getLocaleCode($locale);
            if ($localeCode === null) {
                $this->logUnsupportedLocale('title', $locale);
                continue;
            }

            $thothTitles[$this->getLocaleKey($localeCode)] = new ThothTitle([
                'workId' => $workId,
                'localeCode' => $localeCode,
                'fullTitle' => $this->composeFullTitle($title, $subtitles[$locale] ?? null),
                'title' => $title,
                'subtitle' => $subtitles[$locale] ?? null,
                'canonical' => $locale === $canonicalLocale,
            ]);
        }

        return $thothTitles;
    }

    public function getCanonicalLocale($entity, ?string $preferredLocale = null): ?string
    {
        $locales = $this->getSupportedLocales($this->getLocalizedValues($entity, 'title', $preferredLocale));
        if (empty($locales)) {
            $locales = $this->getSupportedLocales($this->getLocalizedValues($entity, 'subtitle', $preferredLocale));
        }

        if ($preferredLocale && in_array($preferredLocale, $locales, true)) {
            return $preferredLocale;
        }

        return $locales[0] ?? $preferredLocale;
    }

    public function getLocaleKey(?string $localeCode): string
    {
        return $localeCode ?? '';
    }

    private function getLocalizedValues($entity, string $key, ?string $fallbackLocale = null): array
    {
        $values = $entity->getData($key);
        if (is_array($values)) {
            return array_filter($values, fn ($value) => $value !== null && $value !== '');
        }

        if ($values !== null && $values !== '' && $fallbackLocale) {
            return [$fallbackLocale => $values];
        }

        return [];
    }

    private function getLocaleCode(?string $locale): ?string
    {
        return ThothLocaleCode::fromPkpLocale($locale);
    }

    private function composeFullTitle(string $title, ?string $subtitle): string
    {
        return $subtitle ? "{$title}: {$subtitle}" : $title;
    }

    private function getSupportedLocales(array $localizedValues): array
    {
        return array_values(array_filter(
            array_keys($localizedValues),
            fn (string $locale): bool => $this->getLocaleCode($locale) !== null
        ));
    }

    private function logUnsupportedLocale(string $entityType, ?string $locale): void
    {
        $normalizedLocaleCode = $locale ? strtoupper(str_replace(['-', '@'], '_', $locale)) : 'NULL';
        error_log(sprintf(
            '[thoth] Skipping unsupported locale for %s: sourceLocale=%s normalizedLocaleCode=%s',
            $entityType,
            $locale ?? 'NULL',
            $normalizedLocaleCode
        ));
    }
}
