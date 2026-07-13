<?php

class ThothFeatureVideoTemplateFilter
{
    private $video = [];

    public function registerFilter($templateMgr, $template)
    {
        if ($template !== 'frontend/pages/book.tpl') {
            return false;
        }
        $publication = $templateMgr->getTemplateVars('publication');
        $url = $publication ? $publication->getData('thothFeatureVideoUrl') : null;
        if (!$this->isValidUrl($url)) {
            return false;
        }
        $this->video = [
            'title' => (string) $publication->getData('thothFeatureVideoTitle'),
            'url' => $url,
            'width' => $this->dimension($publication->getData('thothFeatureVideoWidth'), 640, 1920),
            'height' => $this->dimension($publication->getData('thothFeatureVideoHeight'), 360, 1080),
        ];
        $templateMgr->registerFilter('output', [$this, 'addVideo']);
        return false;
    }

    public function addVideo($output)
    {
        $title = htmlspecialchars($this->video['title'], ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($this->video['url'], ENT_QUOTES, 'UTF-8');
        $html = '<div class="item thoth_feature_video"><h2 class="label">' . $title . '</h2>'
            . '<video controls preload="metadata" style="display:block;max-width:100%;height:auto" width="' . $this->video['width']
            . '" height="' . $this->video['height'] . '" src="' . $url . '"></video></div>';
        return preg_replace(
            '/<\/div><!-- \.main_entry -->/',
            $html . '</div><!-- .main_entry -->',
            $output,
            1
        );
    }

    private function isValidUrl($url)
    {
        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
            && parse_url($url, PHP_URL_SCHEME) === 'https';
    }

    private function dimension($value, $default, $maximum)
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        return $value && $value > 0 ? min($value, $maximum) : $default;
    }
}
