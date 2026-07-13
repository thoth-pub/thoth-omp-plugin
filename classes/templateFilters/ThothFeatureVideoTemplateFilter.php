<?php

/**
 * @file plugins/generic/thoth/classes/templateFilters/ThothFeatureVideoTemplateFilter.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothFeatureVideoTemplateFilter
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Adds a Thoth-hosted featured video to the public book page.
 */

namespace APP\plugins\generic\thoth\classes\templateFilters;

use APP\plugins\generic\thoth\classes\facades\ThothRepository;
use APP\plugins\generic\thoth\classes\services\ThothFeatureVideoCacheService;
use Throwable;

class ThothFeatureVideoTemplateFilter
{
    private array $video = [];

    public function registerFilter($templateMgr, $template): bool
    {
        if ($template !== 'frontend/pages/book.tpl') {
            return false;
        }

        $submission = $templateMgr->getTemplateVars('publishedSubmission')
            ?: $templateMgr->getTemplateVars('monograph');
        $workId = $submission ? $submission->getData('thothWorkId') : null;
        if (!$workId) {
            return false;
        }
        try {
            $video = $this->loadVideo($workId);
        } catch (Throwable $exception) {
            return false;
        }
        $url = $video['url'] ?? null;
        if (!$this->isValidUrl($url)) {
            return false;
        }

        $this->video = [
            'title' => (string) ($video['title'] ?? ''),
            'url' => $url,
            'width' => $this->normalizeDimension($video['width'] ?? null, 640, 1920),
            'height' => $this->normalizeDimension($video['height'] ?? null, 360, 1080),
        ];
        $templateMgr->registerFilter('output', $this->addVideo(...));

        return false;
    }

    protected function loadVideo(string $workId): ?array
    {
        return (new ThothFeatureVideoCacheService())->get($workId, function () use ($workId) {
            $featuredVideo = ThothRepository::work()->getFeatureVideo($workId);
            return $featuredVideo ? $featuredVideo->toArray() : null;
        });
    }

    public function addVideo($output, $template = null): string
    {
        if (!$this->video) {
            return $output;
        }

        $title = htmlspecialchars($this->video['title'], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($this->video['url'], ENT_QUOTES, 'UTF-8');
        $html = '<div class="item thoth_feature_video">'
            . '<h2 class="label">' . $title . '</h2>'
            . '<video controls preload="metadata" style="display:block;max-width:100%;height:auto" width="' . $this->video['width']
            . '" height="' . $this->video['height'] . '" src="' . $url . '"></video>'
            . '</div>';

        return preg_replace(
            '/<\/div><!-- \.main_entry -->/',
            $html . '</div><!-- .main_entry -->',
            $output,
            1
        );
    }

    private function isValidUrl($url): bool
    {
        return is_string($url)
            && filter_var($url, FILTER_VALIDATE_URL)
            && parse_url($url, PHP_URL_SCHEME) === 'https';
    }

    private function normalizeDimension($value, int $default, int $maximum): int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        return $value && $value > 0 ? min($value, $maximum) : $default;
    }
}
