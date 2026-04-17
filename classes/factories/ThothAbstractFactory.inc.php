<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothAbstractFactory.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothAbstractFactory
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth abstracts
 */

use ThothApi\GraphQL\Models\AbstractText as ThothAbstract;

import('plugins.generic.thoth.classes.i18n.ThothLocaleCode');

class ThothAbstractFactory
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
        $abstracts = $this->getLocalizedValues($entity, 'abstract', $canonicalLocale);
        $thothAbstracts = [];

        foreach ($abstracts as $locale => $abstract) {
            $localeCode = $this->getLocaleCode($locale);
            if ($localeCode === null) {
                $this->logUnsupportedLocale('abstract', $locale);
                continue;
            }

            $thothAbstracts[$this->getLocaleKey($localeCode)] = new ThothAbstract([
                'workId' => $workId,
                'localeCode' => $localeCode,
                'content' => $this->wrapInParagraph($abstract),
                'canonical' => $locale === $canonicalLocale,
                'abstractType' => 'LONG',
            ]);
        }

        return $thothAbstracts;
    }

    public function getCanonicalLocale($entity, ?string $preferredLocale = null): ?string
    {
        $locales = $this->getSupportedLocales($this->getLocalizedValues($entity, 'abstract', $preferredLocale));

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

    private function wrapInParagraph($content)
    {
        $content = trim($content);
        if (preg_match('/^<p\b[^>]*>.*<\/p>$/is', $content) === 1) {
            return $content;
        }

        return sprintf('<p>%s</p>', $content);
    }
}
