<?php

use ThothApi\GraphQL\Inputs\CompleteFileUpload;
use ThothApi\GraphQL\Inputs\NewWorkFeaturedVideoFileUpload;

class ThothFeatureVideoFileUploadRepository
{
    private const UPLOAD_SELECTION = [
        'fileUploadId', 'uploadUrl', 'uploadHeaders' => ['name', 'value'], 'expiresAt',
    ];
    private const FILE_SELECTION = [
        'fileId', 'fileType', 'workFeaturedVideoId', 'cdnUrl', 'mimeType', 'bytes', 'sha256',
    ];
    private $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function init(array $data)
    {
        return $this->thothClient->initWorkFeaturedVideoFileUpload(
            new NewWorkFeaturedVideoFileUpload($data),
            self::UPLOAD_SELECTION
        );
    }

    public function complete($fileUploadId)
    {
        return $this->thothClient->completeFileUpload(
            new CompleteFileUpload(['fileUploadId' => $fileUploadId]),
            self::FILE_SELECTION
        );
    }
}
