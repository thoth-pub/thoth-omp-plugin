<?php

/**
 * @file plugins/generic/thoth/classes/formatters/ThothMarkupFormat.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothMarkupFormat
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Detects the Thoth markup format for text fields
 */

namespace APP\plugins\generic\thoth\classes\formatters;

use ThothApi\GraphQL\Enums\MarkupFormat;

class ThothMarkupFormat
{
    public static function fromContent(?string ...$contents): string
    {
        foreach ($contents as $content) {
            if ($content !== null && self::containsHtmlMarkup($content)) {
                return MarkupFormat::HTML;
            }
        }

        return MarkupFormat::PLAIN_TEXT;
    }

    private static function containsHtmlMarkup(string $content): bool
    {
        return preg_match('/<\/?[a-z][a-z0-9-]*(?:\s+[^<>]*)?\s*\/?>/i', $content) === 1;
    }
}
