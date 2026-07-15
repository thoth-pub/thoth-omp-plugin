<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothFileUploadService.php
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

namespace APP\plugins\generic\thoth\classes\services;

use APP\core\Application;
use APP\plugins\generic\thoth\classes\security\ThothApiUrlValidator;
use Exception;

class ThothFileUploadService
{
    public function upload($fileUploadResponse, string $filePath, $fileUploadRepository)
    {
        $uploadUrl = $fileUploadResponse->getUploadUrl();
        if (!$this->isSafeUploadUrl($uploadUrl)) {
            throw new Exception('Unsafe Thoth upload URL');
        }

        $headers = array_reduce($fileUploadResponse->getUploadHeaders(), function ($headers, $uploadHeader) {
            $headers[$uploadHeader->getName()] = $uploadHeader->getValue();
            return $headers;
        }, []);

        $resource = fopen($filePath, 'r');
        try {
            $this->getHttpClient()->request('PUT', $uploadUrl, [
                'headers' => $headers,
                'body' => $resource,
                'allow_redirects' => false,
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

    protected function isSafeUploadUrl(string $url): bool
    {
        return (new ThothApiUrlValidator())->isSafe($url);
    }
}
