<?php

/**
 * @file plugins/generic/thoth/classes/formatters/HtmlStripper.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class HtmlStripper
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class for removing HTML tags from strings
 */

class HtmlStripper
{
    private const ALLOWED_TAGS = '<strong><mark><em><i><u><sup><sub><ul><ol><li>';

    public static function stripTags($string)
    {
        return strip_tags($string, self::ALLOWED_TAGS);
    }
}
