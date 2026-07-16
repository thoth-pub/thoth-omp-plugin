<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothFrontcoverService.inc.php
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

use APP\facades\Repo;
use APP\publication\DAO as PublicationDAO;

import('classes.file.PublicFileManager');
import('plugins.generic.thoth.classes.facades.ThothRepo');
import('plugins.generic.thoth.classes.services.ThothMeCacheService');

class ThothFrontcoverService
{
    public const UNSUPPORTED_FORMAT_WARNING = 'plugins.generic.thoth.frontcover.unsupportedFormat';

    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg'];
    private const SUPPORTED_MIME_TYPE = 'image/jpeg';

    private const PATCH_WORK_FIELDS = [
        'workId' => true,
        'workType' => true,
        'workStatus' => true,
        'reference' => true,
        'edition' => true,
        'imprintId' => true,
        'doi' => true,
        'publicationDate' => true,
        'withdrawnDate' => true,
        'place' => true,
        'pageCount' => true,
        'pageBreakdown' => true,
        'imageCount' => true,
        'tableCount' => true,
        'audioCount' => true,
        'videoCount' => true,
        'license' => true,
        'copyrightHolder' => true,
        'landingPage' => true,
        'lccn' => true,
        'oclc' => true,
        'generalNote' => true,
        'bibliographyNote' => true,
        'toc' => true,
        'resourcesDescription' => true,
        'coverUrl' => true,
        'coverCaption' => true,
        'firstPage' => true,
        'lastPage' => true,
        'pageInterval' => true,
    ];

    private $frontcoverFileUploadRepository;
    private $workRepository;
    private $fileUploadService;

    public function __construct($frontcoverFileUploadRepository, $workRepository, $fileUploadService)
    {
        $this->frontcoverFileUploadRepository = $frontcoverFileUploadRepository;
        $this->workRepository = $workRepository;
        $this->fileUploadService = $fileUploadService;
    }

    public function sync($publication, string $thothWorkId): ?string
    {
        if (!$publication->getData('thothUploadFrontcover')) {
            $this->clearUploadData($publication);
            return null;
        }

        if (!$this->canUploadFrontcover($publication)) {
            return null;
        }

        $frontcoverFile = $this->resolveFrontcoverFile($publication);
        if (!$frontcoverFile) {
            return null;
        }

        if (!$this->isSupportedFrontcover($frontcoverFile)) {
            $this->disableUnsupportedFrontcoverHosting($publication);
            return self::UNSUPPORTED_FORMAT_WARNING;
        }

        if ($frontcoverFile['sha256'] === $publication->getData('thothFrontcoverSha256')) {
            return null;
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
        $this->workRepository->edit($this->workRepository->new(array_merge(
            $this->getPatchWorkData($this->workRepository->get($thothWorkId)),
            ['coverUrl' => $cdnUrl]
        )));
        $this->saveUploadData($publication, $frontcoverFile['sha256'], $cdnUrl);

        return null;
    }

    private function getPatchWorkData($thothWork): array
    {
        return array_intersect_key($thothWork->toArray(), self::PATCH_WORK_FIELDS);
    }

    private function isSupportedFrontcover(array $frontcoverFile): bool
    {
        return in_array($frontcoverFile['extension'] ?? null, self::SUPPORTED_EXTENSIONS, true)
            && ($frontcoverFile['mimeType'] ?? null) === self::SUPPORTED_MIME_TYPE;
    }

    private function disableUnsupportedFrontcoverHosting($publication): void
    {
        $publication->setData('thothUploadFrontcover', false);
        $publication->setData('thothFrontcoverSha256', null);
        $publication->setData('thothFrontcoverUrl', null);
        $this->persistPublication($publication);
    }

    protected function canUploadFrontcover($publication): bool
    {
        try {
            $contextId = $this->getContextId($publication);
            return $contextId ? $this->hasCdnWritePermission($contextId) : false;
        } catch (Throwable $exception) {
            return false;
        }
    }

    protected function hasCdnWritePermission($contextId): bool
    {
        return (new ThothMeCacheService(ThothRepo::me()))->hasCdnWritePermission($contextId);
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
        $publication->setData('thothFrontcoverSha256', $sha256);
        $publication->setData('thothFrontcoverUrl', $cdnUrl);

        $this->persistPublication($publication);
    }

    protected function clearUploadData($publication): void
    {
        if (!$publication->getData('thothFrontcoverSha256') && !$publication->getData('thothFrontcoverUrl')) {
            return;
        }

        $publication->setData('thothFrontcoverSha256', null);
        $publication->setData('thothFrontcoverUrl', null);

        $this->persistPublication($publication);
    }

    protected function persistPublication($publication): void
    {
        app(PublicationDAO::class)->update($publication);
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
