<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Enums\SubjectType;

import('plugins.generic.thoth.classes.services.ThothSubjectClassifier');

class ThothSubjectClassifierTest extends PKPTestCase
{
    /**
     * @dataProvider explicitSubjectSchemes
     */
    public function testClassifiesExplicitSubjectSchemes(array $subject, $expectedType)
    {
        $classifier = new ThothSubjectClassifier(
            fn ($code) => $code === 'JNF',
            fn ($code) => $code === 'MFGV'
        );

        $this->assertSame([
            'subjectType' => $expectedType,
            'subjectCode' => $subject['identifier'],
        ], $classifier->classify($subject));
    }

    public function explicitSubjectSchemes()
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

    public function testClassifiesOnlyUnambiguousCodesWithoutAnExplicitSource()
    {
        $classifier = new ThothSubjectClassifier(
            fn ($code) => $code === 'JNF',
            fn ($code) => $code === 'MFGV'
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

    public function testPrefersAVerifiedThemaCodeWithoutAnExplicitSource()
    {
        $classifier = new ThothSubjectClassifier(
            fn ($code) => $code === 'MFGV',
            fn ($code) => $code === 'MFGV'
        );

        $this->assertSame([
            'subjectType' => SubjectType::THEMA,
            'subjectCode' => 'MFGV',
        ], $classifier->classify('MFGV'));
    }

    public function testClassifiesABisacCodeWithoutAnExplicitSource()
    {
        $classifier = new ThothSubjectClassifier(
            fn ($code) => false,
            fn ($code) => false
        );

        $this->assertSame([
            'subjectType' => SubjectType::BISAC,
            'subjectCode' => 'EDU000000',
        ], $classifier->classify('EDU000000'));
    }

    public function testClassifiesAnOnixPrefixedTextValue()
    {
        $classifier = new ThothSubjectClassifier(
            fn ($code) => false,
            fn ($code) => $code === 'MFGV'
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

    public function testFallsBackToKeywordWhenAnExplicitCodeIsInvalid()
    {
        $classifier = new ThothSubjectClassifier(fn ($code) => false, fn ($code) => false);

        $this->assertSame([
            'subjectType' => SubjectType::KEYWORD,
            'subjectCode' => 'Invalid classification',
        ], $classifier->classify([
            'name' => 'Invalid classification',
            'identifier' => 'INVALID',
            'source' => '93',
        ]));
    }

    public function testClassifiesAnUnknownExplicitVocabularyAsCustom()
    {
        $classifier = new ThothSubjectClassifier(fn ($code) => false, fn ($code) => false);

        $this->assertSame([
            'subjectType' => SubjectType::CUSTOM,
            'subjectCode' => 'local:123',
        ], $classifier->classify([
            'name' => 'Local controlled subject',
            'identifier' => 'local:123',
            'source' => 'https://example.test/subjects',
        ]));
    }

    public function testValidatesThemaCodesWithoutExternalRequests()
    {
        $classifier = new ThothSubjectClassifier(fn ($code) => false);

        $this->assertSame([
            'subjectType' => SubjectType::THEMA,
            'subjectCode' => 'GTK',
        ], $classifier->classify('GTK'));
    }

    public function testAcceptsSixCharacterThemaSubjectCodes()
    {
        $classifier = new ThothSubjectClassifier(fn ($code) => false);

        $this->assertSame([
            'subjectType' => SubjectType::THEMA,
            'subjectCode' => 'YPCA21',
        ], $classifier->classify('YPCA21'));
    }

    public function testDoesNotAssumeABicCodeWhenThemaValidationIsUnavailable()
    {
        $classifier = new ThothSubjectClassifier(
            fn ($code) => $code === 'ABA',
            null,
            __DIR__ . '/missing-thema-codes.json'
        );

        $this->assertSame([
            'subjectType' => SubjectType::KEYWORD,
            'subjectCode' => 'ABA',
        ], $classifier->classify('ABA'));
    }

    public function testValidatesBicCodesUsingTheOmpNativeCodeList()
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
