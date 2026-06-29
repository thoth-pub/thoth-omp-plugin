<?php

/**
 * @file plugins/generic/thoth/classes/templateFilters/ThothCatalogFilesTemplateFilter.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothCatalogFilesTemplateFilter
 * @ingroup plugins_generic_thoth
 *
 * @brief Template filter to add async Thoth file targets to the catalog page.
 */

class ThothCatalogFilesTemplateFilter
{
    public function registerFilter($templateMgr, $template)
    {
        if ($template !== 'frontend/pages/book.tpl') {
            return false;
        }

        $templateMgr->registerFilter('output', [$this, 'injectChapterPlaceholders']);

        return false;
    }

    public function injectChapterPlaceholders($output, $templateMgr)
    {
        $chapters = $templateMgr->getTemplateVars('chapters');

        if (empty($chapters)) {
            return $output;
        }

        $offset = 0;
        foreach ($chapters as $chapter) {
            $placeholder = sprintf(
                '<div class="files thoth_files" data-thoth-target="chapter" data-chapter-id="%d"></div>',
                (int) $chapter->getId()
            );
            $chapterTitle = preg_quote(htmlspecialchars($chapter->getLocalizedTitle(), ENT_QUOTES, 'UTF-8'), '/');
            $pattern = '/(<li>\s*<div class="title">\s*' . $chapterTitle . '[\s\S]*?)(<\/li>)/';

            if (!preg_match($pattern, $output, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                continue;
            }

            $match = $matches[0][0];
            $matchOffset = $matches[0][1];
            $insertOffset = $matchOffset + strlen($matches[1][0]);
            $output = substr($output, 0, $insertOffset)
                . $placeholder
                . substr($output, $insertOffset);
            $offset = $matchOffset + strlen($match) + strlen($placeholder);
        }

        $templateMgr->unregisterFilter('output', [$this, 'injectChapterPlaceholders']);

        return $output;
    }
}
