<?php

/**
 * @file plugins/generic/thoth/classes/formatters/DoiFormatter.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiFormatter
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class for formatting DOI URLs
 */

class DoiFormatter
{
    public static function resolveUrl($doi)
    {
        if (empty($doi)) {
            return $doi;
        }

        $search = ['%', '"', '#', ' ', '<', '>', '{'];
        $replace = ['%25', '%22', '%23', '%20', '%3c', '%3e', '%7b'];
        $encodedDoi = str_replace($search, $replace, $doi);

        return "https://doi.org/$encodedDoi";
    }
}
