<?php

use APP\publication\DAO as PublicationDAO;
import('lib.pkp.classes.file.TemporaryFileManager');

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

    public function upload($submission, $publication, string $title, int $fileId, int $userId): array
    {
        $workId = $submission->getData('thothWorkId');
        if (!$workId) {
            throw new InvalidArgumentException('The submission is not registered in Thoth.');
        }
        $file = $this->resolveTemporaryFile($fileId, $userId);
        $metadata = $this->featureVideoService->upload($workId, trim($title), $file);
        foreach ([
            'thothFeatureVideoId' => 'id',
            'thothFeatureVideoTitle' => 'title',
            'thothFeatureVideoUrl' => 'url',
            'thothFeatureVideoWidth' => 'width',
            'thothFeatureVideoHeight' => 'height',
            'thothFeatureVideoSha256' => 'sha256',
        ] as $property => $key) {
            $publication->setData($property, $metadata[$key]);
        }
        $this->persistPublication($publication);
        $this->deleteTemporaryFile($fileId, $userId);
        return $metadata;
    }

    protected function resolveTemporaryFile(int $fileId, int $userId): array
    {
        $file = $this->getTemporaryFile($fileId, $userId);
        if (!$file) {
            throw new InvalidArgumentException('The temporary video file was not found.');
        }
        $path = $file->getFilePath();
        $extension = strtolower(pathinfo($file->getOriginalFileName(), PATHINFO_EXTENSION));
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

    protected function getTemporaryFile(int $fileId, int $userId)
    {
        return (new TemporaryFileManager())->getFile($fileId, $userId);
    }

    protected function persistPublication($publication): void
    {
        app(PublicationDAO::class)->update($publication);
    }

    protected function deleteTemporaryFile(int $fileId, int $userId): void
    {
        (new TemporaryFileManager())->deleteById($fileId, $userId);
    }
}
