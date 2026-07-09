<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothFrontcoverService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothFrontcoverService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Coordinates OMP cover image uploads to Thoth frontcover storage.
 */

namespace APP\plugins\generic\thoth\classes\services;

use APP\facades\Repo;
use APP\file\PublicFileManager;
use Throwable;

class ThothFrontcoverService
{
    private $frontcoverFileUploadRepository;
    private $workRepository;
    private ThothFileUploadService $fileUploadService;
    private ThothMeService $meService;

    public function __construct(
        $frontcoverFileUploadRepository,
        $workRepository,
        ThothFileUploadService $fileUploadService,
        ThothMeService $meService
    ) {
        $this->frontcoverFileUploadRepository = $frontcoverFileUploadRepository;
        $this->workRepository = $workRepository;
        $this->fileUploadService = $fileUploadService;
        $this->meService = $meService;
    }

    public function sync($publication, string $thothWorkId): void
    {
        if (!$publication->getData('thothUploadFrontcover') || !$this->canUploadFrontcover($publication)) {
            return;
        }

        $frontcoverFile = $this->resolveFrontcoverFile($publication);
        if (!$frontcoverFile || $frontcoverFile['sha256'] === $publication->getData('thothFrontcoverSha256')) {
            return;
        }

        $newFrontcoverFileUpload = $this->frontcoverFileUploadRepository->new([
            'workId' => $thothWorkId,
            'declaredExtension' => $frontcoverFile['extension'],
            'declaredMimeType' => $frontcoverFile['mimeType'],
            'declaredSha256' => $frontcoverFile['sha256'],
        ]);
        $fileUploadResponse = $this->frontcoverFileUploadRepository->init($newFrontcoverFileUpload);
        $file = $this->fileUploadService->upload(
            $fileUploadResponse,
            $frontcoverFile['path'],
            $this->frontcoverFileUploadRepository
        );

        $cdnUrl = $file->getCdnUrl();
        $this->workRepository->edit($this->workRepository->new([
            'workId' => $thothWorkId,
            'coverUrl' => $cdnUrl,
        ]));
        $this->saveUploadData($publication, $frontcoverFile['sha256'], $cdnUrl);
    }

    protected function canUploadFrontcover($publication): bool
    {
        try {
            return $this->meService->hasCdnWritePermission();
        } catch (Throwable $exception) {
            return false;
        }
    }

    protected function resolveFrontcoverFile($publication): ?array
    {
        $coverImage = $publication->getLocalizedData('coverImage', $publication->getData('locale'));
        if (empty($coverImage['uploadName'])) {
            return null;
        }

        $contextId = $this->getContextId($publication);
        if (!$contextId) {
            return null;
        }

        $publicFileManager = new PublicFileManager();
        $filePath = $publicFileManager->getContextFilesPath($contextId) . '/' . $coverImage['uploadName'];
        if (!file_exists($filePath)) {
            return null;
        }

        return [
            'path' => $filePath,
            'extension' => strtolower(pathinfo($filePath, PATHINFO_EXTENSION)),
            'mimeType' => mime_content_type($filePath),
            'sha256' => hash_file('sha256', $filePath),
        ];
    }

    protected function saveUploadData($publication, string $sha256, string $cdnUrl): void
    {
        Repo::publication()->edit($publication, [
            'thothFrontcoverSha256' => $sha256,
            'thothFrontcoverUrl' => $cdnUrl,
        ]);
    }

    private function getContextId($publication): ?int
    {
        if ($contextId = $publication->getData('contextId')) {
            return (int) $contextId;
        }

        if (!$submissionId = $publication->getData('submissionId')) {
            return null;
        }

        $submission = Repo::submission()->get($submissionId);
        return $submission ? (int) $submission->getData('contextId') : null;
    }
}
