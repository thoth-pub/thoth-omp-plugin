<?php

/**
 * @file plugins/generic/thoth/classes/services/FeatureVideoSubmissionService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FeatureVideoSubmissionService
 * @ingroup plugins_generic_thoth
 *
 * @brief Validates an OMP temporary video and persists its Thoth metadata.
 */

namespace APP\plugins\generic\thoth\classes\services;

use APP\facades\Repo;
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

    public function upload($submission, $publication, string $title, int $temporaryFileId, int $userId): array
    {
        $workId = $submission->getData('thothWorkId');
        if (!$workId) {
            throw new InvalidArgumentException('The submission is not registered in Thoth.');
        }

        $file = $this->resolveTemporaryFile($temporaryFileId, $userId);
        $metadata = $this->featureVideoService->upload($workId, trim($title), $file);

        foreach ($this->getPublicationData($metadata) as $name => $value) {
            $publication->setData($name, $value);
        }
        $this->persistPublication($publication);
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

    protected function persistPublication($publication): void
    {
        Repo::publication()->dao->update($publication);
    }

    protected function deleteTemporaryFile(int $temporaryFileId, int $userId): void
    {
        (new TemporaryFileManager())->deleteById($temporaryFileId, $userId);
    }

    private function getPublicationData(array $metadata): array
    {
        return [
            'thothFeatureVideoId' => $metadata['id'],
            'thothFeatureVideoTitle' => $metadata['title'],
            'thothFeatureVideoUrl' => $metadata['url'],
            'thothFeatureVideoWidth' => $metadata['width'],
            'thothFeatureVideoHeight' => $metadata['height'],
            'thothFeatureVideoSha256' => $metadata['sha256'],
        ];
    }
}
