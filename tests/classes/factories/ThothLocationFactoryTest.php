<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothLocationFactoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationFactoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Test class for the ThothLocationFactory class
 */

namespace APP\plugins\generic\thoth\tests\classes\factories;

require_once __DIR__ . '/../../../vendor/autoload.php';

use APP\plugins\generic\thoth\classes\factories\ThothLocationFactory;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Enums\LocationPlatform;

class ThothLocationFactoryTest extends PKPTestCase
{
    public function testCreatesLocationFromResolvedUrls(): void
    {
        $location = (new ThothLocationFactory())->create([
            'landingPage' => 'https://publisher.example/book/1',
            'fullTextUrl' => 'https://publisher.example/download/1',
        ]);

        self::assertSame('https://publisher.example/book/1', $location->getLandingPage());
        self::assertSame('https://publisher.example/download/1', $location->getFullTextUrl());
        self::assertSame(LocationPlatform::OTHER, $location->getLocationPlatform());
    }

    public function testOmitsAbsentDownloadUrl(): void
    {
        $location = (new ThothLocationFactory())->create([
            'landingPage' => 'https://publisher.example/book/1',
            'fullTextUrl' => null,
        ]);

        self::assertArrayNotHasKey('fullTextUrl', $location->getAllData());
    }
}
