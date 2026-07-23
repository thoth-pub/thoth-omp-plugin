<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothSubjectClassifierTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\services\ThothSubjectClassifier;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PKP\tests\PKPTestCase;
use RuntimeException;
use ThothApi\GraphQL\Enums\SubjectType;

class ThothSubjectClassifierTest extends PKPTestCase
{
    #[DataProvider('explicitSubjectSchemes')]
    public function testClassifiesExplicitSubjectSchemes(array $subject, string $expectedType): void
    {
        $classifier = new ThothSubjectClassifier(
            fn (string $code): bool => $code === 'JNF',
            fn (string $code): bool => $code === 'MFGV'
        );

        $this->assertSame([
            'subjectType' => $expectedType,
            'subjectCode' => $subject['identifier'],
        ], $classifier->classify($subject));
    }

    public static function explicitSubjectSchemes(): array
    {
        return [
            'ONIX LCC' => [[
                'name' => 'Computer programming',
                'identifier' => 'QA76.73',
                'source' => '03',
            ], SubjectType::LCC],
            'ONIX BISAC' => [[
                'name' => 'Education',
                'identifier' => 'EDU000000',
                'source' => '10',
            ], SubjectType::BISAC],
            'ONIX BIC' => [[
                'name' => 'Educational strategies and policy',
                'identifier' => 'JNF',
                'source' => '12',
            ], SubjectType::BIC],
            'ONIX Thema' => [[
                'name' => 'Biomechanics, human kinetics',
                'identifier' => 'MFGV',
                'source' => '93',
            ], SubjectType::THEMA],
        ];
    }

    public function testClassifiesOnlyUnambiguousCodesWithoutAnExplicitSource(): void
    {
        $classifier = new ThothSubjectClassifier(
            fn (string $code): bool => $code === 'JNF',
            fn (string $code): bool => $code === 'MFGV'
        );

        $this->assertSame([
            'subjectType' => SubjectType::BIC,
            'subjectCode' => 'JNF',
        ], $classifier->classify('JNF'));
        $this->assertSame([
            'subjectType' => SubjectType::THEMA,
            'subjectCode' => 'MFGV',
        ], $classifier->classify('mfgv'));
    }

    public function testPrefersAVerifiedThemaCodeWithoutAnExplicitSource(): void
    {
        $classifier = new ThothSubjectClassifier(
            fn (string $code): bool => $code === 'MFGV',
            fn (string $code): bool => $code === 'MFGV'
        );

        $this->assertSame([
            'subjectType' => SubjectType::THEMA,
            'subjectCode' => 'MFGV',
        ], $classifier->classify('MFGV'));
    }

    public function testClassifiesABisacCodeWithoutAnExplicitSource(): void
    {
        $classifier = new ThothSubjectClassifier(
            fn (string $code): bool => false,
            fn (string $code): bool => false
        );

        $this->assertSame([
            'subjectType' => SubjectType::BISAC,
            'subjectCode' => 'EDU000000',
        ], $classifier->classify('EDU000000'));
    }

    public function testClassifiesAnOnixPrefixedTextValue(): void
    {
        $classifier = new ThothSubjectClassifier(
            fn (string $code): bool => false,
            fn (string $code): bool => $code === 'MFGV'
        );

        $this->assertSame([
            'subjectType' => SubjectType::THEMA,
            'subjectCode' => 'MFGV',
        ], $classifier->classify('93:MFGV'));
        $this->assertSame([
            'subjectType' => SubjectType::BISAC,
            'subjectCode' => 'EDU000000',
        ], $classifier->classify('BISAC:EDU000000'));
    }

    public function testFallsBackToKeywordWhenAnExplicitCodeIsInvalid(): void
    {
        $classifier = new ThothSubjectClassifier(
            fn (string $code): bool => false,
            fn (string $code): bool => false
        );

        $this->assertSame([
            'subjectType' => SubjectType::KEYWORD,
            'subjectCode' => 'Invalid classification',
        ], $classifier->classify([
            'name' => 'Invalid classification',
            'identifier' => 'INVALID',
            'source' => '93',
        ]));
    }

    public function testClassifiesAnUnknownExplicitVocabularyAsCustom(): void
    {
        $classifier = new ThothSubjectClassifier(
            fn (string $code): bool => false,
            fn (string $code): bool => false
        );

        $this->assertSame([
            'subjectType' => SubjectType::CUSTOM,
            'subjectCode' => 'local:123',
        ], $classifier->classify([
            'name' => 'Local controlled subject',
            'identifier' => 'local:123',
            'source' => 'https://example.test/subjects',
        ]));
    }

    public function testValidatesThemaCodesAgainstTheOfficialRegistryResponse(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://ns.editeur.org/thema/en/MFGV', $this->isType('array'))
            ->willReturn(new Response(200, [], '<td class="notation">MFGV</td>'));

        $classifier = new ThothSubjectClassifier(
            fn (string $code): bool => false,
            null,
            $httpClient
        );

        $this->assertSame([
            'subjectType' => SubjectType::THEMA,
            'subjectCode' => 'MFGV',
        ], $classifier->classify('MFGV'));
    }

    public function testAcceptsSixCharacterThemaSubjectCodes(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://ns.editeur.org/thema/en/YPCA21', $this->isType('array'))
            ->willReturn(new Response(200, [], '<td class="notation">YPCA21</td>'));

        $classifier = new ThothSubjectClassifier(
            fn (string $code): bool => false,
            null,
            $httpClient
        );

        $this->assertSame([
            'subjectType' => SubjectType::THEMA,
            'subjectCode' => 'YPCA21',
        ], $classifier->classify('YPCA21'));
    }

    public function testDoesNotAssumeABicCodeWhenThemaValidationIsUnavailable(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('request')->willThrowException(new RuntimeException('Registry unavailable'));
        $classifier = new ThothSubjectClassifier(
            fn (string $code): bool => $code === 'ABA',
            null,
            $httpClient
        );

        $this->assertSame([
            'subjectType' => SubjectType::KEYWORD,
            'subjectCode' => 'ABA',
        ], $classifier->classify('ABA'));
    }

    public function testValidatesBicCodesUsingTheOmpNativeCodeList(): void
    {
        $classifier = new ThothSubjectClassifier();

        $this->assertSame([
            'subjectType' => SubjectType::BIC,
            'subjectCode' => 'JNF',
        ], $classifier->classify([
            'name' => 'Educational strategies and policy',
            'identifier' => 'JNF',
            'source' => '12',
        ]));
    }
}
