<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothCatalogFileService.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothCatalogFileService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Service to format Thoth files for the public catalog page.
 */

class ThothCatalogFileService
{
    private $publicationRepository;

    public function __construct($publicationRepository)
    {
        $this->publicationRepository = $publicationRepository;
    }

    public function getFilesByWorkId($thothWorkId)
    {
        if (!$thothWorkId) {
            return [];
        }

        return array_values(array_filter(array_map(
            [$this, 'formatFile'],
            $this->publicationRepository->getFilesByWorkId($thothWorkId)
        )));
    }

    public function formatFile($file)
    {
        $publicationType = null;
        if (is_array($file)) {
            $publicationType = $file['publicationType'] ?? null;
            $file = $file['file'] ?? null;
        }

        if (!$file || !$file->getCdnUrl()) {
            return null;
        }

        return [
            'url' => $file->getCdnUrl(),
            'label' => $this->getFileLabel($file),
            'mimeType' => $file->getMimeType(),
            'publicationType' => $publicationType,
        ];
    }

    private function getFileLabel($file)
    {
        $objectKey = $file->getObjectKey();
        if ($objectKey) {
            return $objectKey;
        }

        return __('common.download');
    }
}
