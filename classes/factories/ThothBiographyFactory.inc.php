<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothBiographyFactory.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBiographyFactory
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth biographies
 */

use ThothApi\GraphQL\Models\Biography as ThothBiography;

import('plugins.generic.thoth.classes.i18n.ThothLocaleCode');

class ThothBiographyFactory
{
    public function createFromAuthor($author, string $contributionId, ?string $preferredLocale = null): array
    {
        $canonicalLocale = $this->getCanonicalLocale($author, $preferredLocale);
        $biographies = $this->getLocalizedValues($author, 'biography', $canonicalLocale);
        $thothBiographies = [];

        foreach ($biographies as $locale => $biography) {
            $localeCode = $this->getLocaleCode($locale);
            if ($localeCode === null) {
                $this->logUnsupportedLocale('biography', $locale);
                continue;
            }

            $thothBiographies[$this->getLocaleKey($localeCode)] = new ThothBiography([
                'contributionId' => $contributionId,
                'localeCode' => $localeCode,
                'content' => $this->wrapInParagraph($biography),
                'canonical' => $locale === $canonicalLocale,
            ]);
        }

        return $thothBiographies;
    }

    public function getCanonicalLocale($author, ?string $preferredLocale = null): ?string
    {
        $locales = $this->getSupportedLocales($this->getLocalizedValues($author, 'biography', $preferredLocale));

        if ($preferredLocale && in_array($preferredLocale, $locales, true)) {
            return $preferredLocale;
        }

        return $locales[0] ?? $preferredLocale;
    }

    public function getLocaleKey(?string $localeCode): string
    {
        return $localeCode ?? '';
    }

    private function getLocalizedValues($author, string $key, ?string $fallbackLocale = null): array
    {
        $values = $author->getData($key);
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
