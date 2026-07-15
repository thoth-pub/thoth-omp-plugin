<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothFileUploadServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothFileUploadServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothFileUploadService
 *
 * @brief Test class for the ThothFileUploadService class
 */

use ThothApi\GraphQL\Schemas\File as ThothFile;
use ThothApi\GraphQL\Schemas\FileUploadResponse;
use ThothApi\GraphQL\Schemas\UploadRequestHeader;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.services.ThothFileUploadService');

class ThothFileUploadServiceTest extends PKPTestCase
{
    public function testUploadsFileToPresignedUrlAndCompletesUpload(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'thoth-upload-');
        file_put_contents($filePath, 'file contents');
        $thothFile = new ThothFile(['fileId' => 'file-id']);
        $repository = new class ($thothFile) {
            public string $completedFileUploadId = '';
            private ThothFile $thothFile;

            public function __construct(ThothFile $thothFile)
            {
                $this->thothFile = $thothFile;
            }

            public function complete($fileUploadId)
            {
                $this->completedFileUploadId = $fileUploadId;
                return $this->thothFile;
            }
        };
        $httpClient = new class () {
            public array $request = [];

            public function request($method, $url, $options)
            {
                $this->request = compact('method', 'url', 'options');
                fclose($options['body']);
            }
        };

        $service = new class ($httpClient) extends ThothFileUploadService {
            private $httpClient;

            public function __construct($httpClient)
            {
                $this->httpClient = $httpClient;
            }

            protected function getHttpClient()
            {
                return $this->httpClient;
            }

            protected function isSafeUploadUrl($url)
            {
                return true;
            }
        };

        $response = new FileUploadResponse([
            'fileUploadId' => 'upload-id',
            'uploadUrl' => 'https://uploads.example.test/file',
            'uploadHeaders' => [
                new UploadRequestHeader(['name' => 'Content-Type', 'value' => 'application/pdf']),
            ],
        ]);

        $result = $service->upload($response, $filePath, $repository);

        $this->assertSame($thothFile, $result);
        $this->assertSame('upload-id', $repository->completedFileUploadId);
        $this->assertSame('PUT', $httpClient->request['method']);
        $this->assertSame('https://uploads.example.test/file', $httpClient->request['url']);
        $this->assertSame(['Content-Type' => 'application/pdf'], $httpClient->request['options']['headers']);

        unlink($filePath);
    }

    public function testRejectsUnsafeUploadUrlBeforeSendingFile(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'thoth-upload-');
        file_put_contents($filePath, 'file contents');
        $service = new class () extends ThothFileUploadService {
            protected function getHttpClient()
            {
                throw new RuntimeException('The HTTP client must not be created');
            }

            protected function isSafeUploadUrl($url)
            {
                return false;
            }
        };
        $response = new FileUploadResponse([
            'fileUploadId' => 'upload-id',
            'uploadUrl' => 'http://127.0.0.1/private',
            'uploadHeaders' => [],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unsafe Thoth upload URL');

        try {
            $service->upload($response, $filePath, new stdClass());
        } finally {
            unlink($filePath);
        }
    }
}
