<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothBookFactoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookFactoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Test class for the ThothBookFactory class
 */

namespace APP\plugins\generic\thoth\tests\classes\factories;

require_once __DIR__ . '/../../../vendor/autoload.php';

use APP\plugins\generic\thoth\classes\factories\ThothBookFactory;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Enums\WorkType;

class ThothBookFactoryTest extends PKPTestCase
{
    public function testCreatesBookFromExplicitMetadataWithoutRequestOrDatabase(): void
    {
        $publication = new Publication();
        $publication->setData('datePublished', '2020-01-01');
        $context = [
            'submissionWorkType' => Submission::WORK_TYPE_AUTHORED_WORK,
            'landingPage' => 'https://publisher.example/book/1',
            'license' => 'https://creativecommons.org/licenses/by/4.0/',
            'copyrightHolder' => 'Publisher',
            'coverUrl' => null,
            'fallbackDoi' => '10.1234/book',
        ];

        $book = (new ThothBookFactory())->createFromPublication($publication, $context, WorkType::TEXTBOOK);

        self::assertSame(WorkType::TEXTBOOK, $book->getWorkType());
        self::assertSame('https://doi.org/10.1234/book', $book->getDoi());
        self::assertSame($context['landingPage'], $book->getLandingPage());
        self::assertSame($context['license'], $book->getLicense());
        self::assertArrayNotHasKey('coverUrl', $book->getAllData());
    }
}
