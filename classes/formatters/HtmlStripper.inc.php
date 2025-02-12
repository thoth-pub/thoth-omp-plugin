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
    private const ALLOWED_TAGS = '<b><strong><em><i><u><ul><ol><li><p><h1><h2><h3><h4><h5><h6>';

    public static function stripTags($string)
    {
        return strip_tags($string, self::ALLOWED_TAGS);
    }
}
