<?php

/**
 * @file plugins/generic/thoth/classes/handlers/ThothCoverHandler.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothCoverHandler
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Serves Thoth-hosted frontcovers as OMP cover images.
 */

import('controllers.submission.CoverHandler');

class ThothCoverHandler extends CoverHandler
{
    public function cover($args, $request)
    {
        $submission = $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
        if ($frontcoverUrl = $this->getThothFrontcoverUrl($submission)) {
            $this->redirectToCoverUrl($frontcoverUrl);
        }

        return parent::cover($args, $request);
    }

    public function thumbnail($args, $request)
    {
        $submission = $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
        if ($frontcoverUrl = $this->getThothFrontcoverUrl($submission)) {
            $this->redirectToCoverUrl($frontcoverUrl);
        }

        return parent::thumbnail($args, $request);
    }

    protected function getThothFrontcoverUrl($submission): ?string
    {
        if (!$submission || !$publication = $submission->getCurrentPublication()) {
            return null;
        }

        $frontcoverUrl = $publication->getData('thothFrontcoverUrl');
        if (!$frontcoverUrl || !filter_var($frontcoverUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        return in_array(parse_url($frontcoverUrl, PHP_URL_SCHEME), ['http', 'https'])
            ? $frontcoverUrl
            : null;
    }

    protected function redirectToCoverUrl(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
