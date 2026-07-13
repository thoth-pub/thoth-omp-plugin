<?php

/**
 * @file plugins/generic/thoth/classes/services/FeatureVideoSubmissionService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FeatureVideoSubmissionService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Validates an OMP temporary video and uploads it to Thoth.
 */

namespace APP\plugins\generic\thoth\classes\services;

use InvalidArgumentException;
use PKP\file\TemporaryFileManager;

class FeatureVideoSubmissionService
{
    private const ALLOWED_MIME_TYPES = [
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
        'mov' => ['video/quicktime'],
    ];

    private $featureVideoService;

    public function __construct($featureVideoService)
    {
        $this->featureVideoService = $featureVideoService;
    }

    public function upload($submission, string $title, int $temporaryFileId, int $userId): array
    {
        $workId = $submission->getData('thothWorkId');
        if (!$workId) {
            throw new InvalidArgumentException('The submission is not registered in Thoth.');
        }

        $file = $this->resolveTemporaryFile($temporaryFileId, $userId);
        $metadata = $this->featureVideoService->upload($workId, trim($title), $file);

        (new ThothFeatureVideoCacheService())->flush($workId);
        $this->deleteTemporaryFile($temporaryFileId, $userId);

        return $metadata;
    }

    protected function resolveTemporaryFile(int $temporaryFileId, int $userId): array
    {
        $temporaryFile = $this->getTemporaryFile($temporaryFileId, $userId);
        if (!$temporaryFile) {
            throw new InvalidArgumentException('The temporary video file was not found.');
        }

        $path = $temporaryFile->getFilePath();
        $extension = strtolower(pathinfo($temporaryFile->getOriginalFileName(), PATHINFO_EXTENSION));
        $mimeType = mime_content_type($path);
        if (!isset(self::ALLOWED_MIME_TYPES[$extension]) ||
            !in_array($mimeType, self::ALLOWED_MIME_TYPES[$extension], true)
        ) {
            throw new InvalidArgumentException('The uploaded file is not a supported video.');
        }

        return [
            'path' => $path,
            'extension' => $extension,
            'mimeType' => $mimeType,
            'sha256' => hash_file('sha256', $path),
        ];
    }

    protected function getTemporaryFile(int $temporaryFileId, int $userId)
    {
        return (new TemporaryFileManager())->getFile($temporaryFileId, $userId);
    }

    protected function deleteTemporaryFile(int $temporaryFileId, int $userId): void
    {
        (new TemporaryFileManager())->deleteById($temporaryFileId, $userId);
    }

}
