<?php

/**
 * @file plugins/generic/thoth/classes/formatters/ThothMarkupFormat.inc.php
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

use ThothApi\GraphQL\Enums\MarkupFormat;

class ThothMarkupFormat
{
    public static function fromContent(...$contents)
    {
        foreach ($contents as $content) {
            if ($content !== null && self::containsHtmlMarkup($content)) {
                return MarkupFormat::HTML;
            }
        }

        return MarkupFormat::PLAIN_TEXT;
    }

    private static function containsHtmlMarkup($content)
    {
        return preg_match('/<\/?[a-z][a-z0-9-]*(?:\s+[^<>]*)?\s*\/?>/i', $content) === 1;
    }
}
