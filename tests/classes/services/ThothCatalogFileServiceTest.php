<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothCatalogFileServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothCatalogFileServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothCatalogFileService
 *
 * @brief Test class for the ThothCatalogFileService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\services\ThothCatalogFileService;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Schemas\File as ThothFile;

class ThothCatalogFileServiceTest extends PKPTestCase
{
    public function testFormatFileReturnsPublicCatalogFileData()
    {
        $file = new ThothFile([
            'cdnUrl' => 'https://example.thoth.pub/10.12345/book.pdf',
            'mimeType' => 'application/pdf',
            'objectKey' => '10.12345/book.pdf',
        ]);
        $service = new ThothCatalogFileService(new class () {
        });

        $formattedFile = $service->formatFile($file);

        $this->assertSame([
            'url' => 'https://example.thoth.pub/10.12345/book.pdf',
            'label' => '10.12345/book.pdf',
            'mimeType' => 'application/pdf',
            'publicationType' => null,
        ], $formattedFile);
    }

    public function testFormatFileReturnsPublicationTypeWhenPresent()
    {
        $file = new ThothFile([
            'cdnUrl' => 'https://example.thoth.pub/10.12345/book.pdf',
            'mimeType' => 'application/pdf',
            'objectKey' => '10.12345/book.pdf',
        ]);
        $service = new ThothCatalogFileService(new class () {
        });

        $formattedFile = $service->formatFile([
            'publicationType' => 'PDF',
            'file' => $file,
        ]);

        $this->assertSame('PDF', $formattedFile['publicationType']);
    }

    public function testFormatFileReturnsNullWhenFileHasNoCdnUrl()
    {
        $file = new ThothFile([
            'mimeType' => 'application/pdf',
            'objectKey' => '10.12345/book.pdf',
        ]);
        $service = new ThothCatalogFileService(new class () {
        });

        $formattedFile = $service->formatFile($file);

        $this->assertNull($formattedFile);
    }

    /**
     * @dataProvider unsafeCdnUrlProvider
     */
    public function testFormatFileRejectsUnsafeCdnUrl(string $url): void
    {
        $file = new ThothFile([
            'cdnUrl' => $url,
            'mimeType' => 'application/pdf',
            'objectKey' => 'book.pdf',
        ]);
        $service = new ThothCatalogFileService(new class () {
        });

        self::assertNull($service->formatFile($file));
    }

    public static function unsafeCdnUrlProvider(): array
    {
        return [
            'JavaScript URL' => ['javascript:alert(1)'],
            'data URL' => ['data:text/html,<script>alert(1)</script>'],
            'cleartext HTTP' => ['http://example.thoth.pub/book.pdf'],
            'relative URL' => ['/book.pdf'],
        ];
    }
}
