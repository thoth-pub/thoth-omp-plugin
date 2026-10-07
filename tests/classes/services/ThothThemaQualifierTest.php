<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothThemaQualifierTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

require_once(__DIR__ . '/../../../vendor/autoload.php');
require_once(__DIR__ . '/../../../classes/services/ThothSubjectClassifier.php');

use APP\plugins\generic\thoth\classes\services\ThothSubjectClassifier;
use PHPUnit\Framework\TestCase;
use ThothApi\GraphQL\Enums\SubjectType;

class ThothThemaQualifierTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('themaQualifiers')]
    public function testClassifiesThemaQualifiers($subject, string $code): void
    {
        $classifier = new ThothSubjectClassifier(fn ($code) => false);

        $this->assertSame([
            'subjectType' => SubjectType::THEMA,
            'subjectCode' => $code,
        ], $classifier->classify($subject));
    }

    public static function themaQualifiers(): array
    {
        $subjects = [];
        foreach (['1D', '2A', '3A', '4C', '5A', '6AA', '1DDB-BE-FAA'] as $code) {
            $subjects[$code . ' plain'] = [$code, $code];
            $subjects[$code . ' prefixed'] = ['THEMA:' . $code, $code];
            $subjects[$code . ' metadata'] = [[
                'name' => 'Thema qualifier',
                'identifier' => $code,
                'source' => 'THEMA',
            ], $code];
        }
        return $subjects;
    }

    public function testRejectsAQualifierAbsentFromTheLocalCodeList(): void
    {
        $classifier = new ThothSubjectClassifier(fn ($code) => false);

        $this->assertSame([
            'subjectType' => SubjectType::KEYWORD,
            'subjectCode' => 'Unknown qualifier',
        ], $classifier->classify([
            'name' => 'Unknown qualifier',
            'identifier' => '1INVALID',
            'source' => 'THEMA',
        ]));
    }
}
