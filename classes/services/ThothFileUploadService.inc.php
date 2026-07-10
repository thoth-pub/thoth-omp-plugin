<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothFileUploadService.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothFileUploadService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Shared service for sending files to Thoth presigned upload URLs.
 */

class ThothFileUploadService
{
    public function upload($fileUploadResponse, string $filePath, $fileUploadRepository)
    {
        $headers = array_reduce($fileUploadResponse->getUploadHeaders(), function ($headers, $uploadHeader) {
            $headers[$uploadHeader->getName()] = $uploadHeader->getValue();
            return $headers;
        }, []);

        $resource = fopen($filePath, 'r');
        try {
            $this->getHttpClient()->request('PUT', $fileUploadResponse->getUploadUrl(), [
                'headers' => $headers,
                'body' => $resource,
            ]);
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }

        return $fileUploadRepository->complete($fileUploadResponse->getFileUploadId());
    }

    protected function getHttpClient()
    {
        return Application::get()->getHttpClient();
    }
}
