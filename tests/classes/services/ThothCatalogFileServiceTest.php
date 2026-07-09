<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothCatalogFileServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothCatalogFileServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothCatalogFileService
 *
 * @brief Test class for the ThothCatalogFileService class
 */

use ThothApi\GraphQL\Schemas\File as ThothFile;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.services.ThothCatalogFileService');

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

    public function testFormatFileReturnsCdnUrlWithoutValidatingScheme()
    {
        $file = new ThothFile([
            'cdnUrl' => 'file:///etc/passwd',
            'mimeType' => 'application/pdf',
            'objectKey' => '10.12345/book.pdf',
        ]);
        $service = new ThothCatalogFileService(new class () {
        });

        $formattedFile = $service->formatFile($file);

        $this->assertSame('file:///etc/passwd', $formattedFile['url']);
    }
}
