<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothChapterFactoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothChapterFactoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Test class for the ThothChapterFactory class
 */

namespace APP\plugins\generic\thoth\tests\classes\factories;

require_once __DIR__ . '/../../../vendor/autoload.php';

use APP\monograph\Chapter;
use APP\plugins\generic\thoth\classes\factories\ThothChapterFactory;
use APP\publication\Publication;
use PKP\tests\PKPTestCase;

class ThothChapterFactoryTest extends PKPTestCase
{
    public function testUsesExplicitParentPublicationAndLandingPage(): void
    {
        $publication = new Publication();
        $publication->setData('datePublished', '2020-01-01');
        $chapter = new Chapter();
        $chapter->setPages('31 - 50');

        $work = (new ThothChapterFactory())->createFromChapter($chapter, [
            'publication' => $publication,
            'landingPage' => 'https://publisher.example/book/1',
        ]);

        self::assertSame('2020-01-01', $work->getPublicationDate());
        self::assertSame('31', $work->getFirstPage());
        self::assertSame('50', $work->getLastPage());
        self::assertSame('https://publisher.example/book/1', $work->getLandingPage());
    }
}
