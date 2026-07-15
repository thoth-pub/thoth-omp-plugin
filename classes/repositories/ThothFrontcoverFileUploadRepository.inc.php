<?php

/**
 * @file plugins/generic/thoth/classes/repositories/ThothFrontcoverFileUploadRepository.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothFrontcoverFileUploadRepository
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth front cover file uploads.
 */

use ThothApi\GraphQL\Inputs\CompleteFileUpload;
use ThothApi\GraphQL\Inputs\NewFrontcoverFileUpload;

class ThothFrontcoverFileUploadRepository
{
    private const FILE_UPLOAD_RESPONSE_SELECTION = [
        'fileUploadId',
        'uploadUrl',
        'uploadHeaders' => [
            'name',
            'value',
        ],
        'expiresAt',
    ];

    private const FILE_SELECTION = [
        'fileId',
        'fileType',
        'workId',
        'publicationId',
        'additionalResourceId',
        'workFeaturedVideoId',
        'objectKey',
        'cdnUrl',
        'mimeType',
        'bytes',
        'sha256',
    ];

    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new NewFrontcoverFileUpload($data);
    }

    public function init($newFrontcoverFileUpload)
    {
        return $this->thothClient->initFrontcoverFileUpload(
            $newFrontcoverFileUpload,
            self::FILE_UPLOAD_RESPONSE_SELECTION
        );
    }

    public function complete($fileUploadId)
    {
        $completeFileUpload = new CompleteFileUpload();
        $completeFileUpload->setFileUploadId($fileUploadId);

        return $this->thothClient->completeFileUpload($completeFileUpload, self::FILE_SELECTION);
    }
}
