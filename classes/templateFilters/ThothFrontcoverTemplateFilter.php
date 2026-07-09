<?php

/**
 * @file plugins/generic/thoth/classes/templateFilters/ThothFrontcoverTemplateFilter.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothFrontcoverTemplateFilter
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Template filter to use Thoth-hosted frontcovers on the book page.
 */

namespace APP\plugins\generic\thoth\classes\templateFilters;

class ThothFrontcoverTemplateFilter
{
    public function registerFilter($templateMgr, $template)
    {
        if ($template !== 'frontend/pages/book.tpl') {
            return false;
        }

        $templateMgr->registerFilter('output', $this->replaceCoverImage(...));

        return false;
    }

    public function replaceCoverImage($output, $templateMgr)
    {
        $publication = $templateMgr->getTemplateVars('publication');
        $frontcoverUrl = $publication ? $publication->getData('thothFrontcoverUrl') : null;
        if (!$this->isValidFrontcoverUrl($frontcoverUrl)) {
            return $output;
        }

        $pattern = '/(<div class="item cover">\s*<img\b[^>]*\bsrc=")[^"]*("[^>]*>)/';
        $output = preg_replace($pattern, '$1' . htmlspecialchars($frontcoverUrl, ENT_QUOTES, 'UTF-8') . '$2', $output, 1);

        $templateMgr->unregisterFilter('output', $this->replaceCoverImage(...));

        return $output;
    }

    private function isValidFrontcoverUrl($url): bool
    {
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https']);
    }
}
