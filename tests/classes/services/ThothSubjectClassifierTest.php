<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use ThothApi\GraphQL\Enums\SubjectType;

import('lib.pkp.tests.PKPTestCase');
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
            fn ($code) => in_array($code, ['JNF', 'ABA'], true),
            fn ($code) => in_array($code, ['MFGV', 'ABA'], true)
        );

        $this->assertSame([
            'subjectType' => SubjectType::BIC,
            'subjectCode' => 'JNF',
        ], $classifier->classify('JNF'));
        $this->assertSame([
            'subjectType' => SubjectType::THEMA,
            'subjectCode' => 'MFGV',
        ], $classifier->classify('mfgv'));
        $this->assertSame([
            'subjectType' => SubjectType::KEYWORD,
            'subjectCode' => 'ABA',
        ], $classifier->classify('ABA'));
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

    public function testValidatesThemaCodesAgainstTheOfficialRegistryResponse()
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://ns.editeur.org/thema/en/MFGV', $this->isType('array'))
            ->willReturn(new Response(200, [], '<td class="notation">MFGV</td>'));

        $classifier = new ThothSubjectClassifier(fn ($code) => false, null, $httpClient);

        $this->assertSame([
            'subjectType' => SubjectType::THEMA,
            'subjectCode' => 'MFGV',
        ], $classifier->classify('MFGV'));
    }

    public function testAcceptsSixCharacterThemaSubjectCodes()
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://ns.editeur.org/thema/en/YPCA21', $this->isType('array'))
            ->willReturn(new Response(200, [], '<td class="notation">YPCA21</td>'));

        $classifier = new ThothSubjectClassifier(fn ($code) => false, null, $httpClient);

        $this->assertSame([
            'subjectType' => SubjectType::THEMA,
            'subjectCode' => 'YPCA21',
        ], $classifier->classify('YPCA21'));
    }

    public function testDoesNotAssumeABicCodeWhenThemaValidationIsUnavailable()
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('request')->willThrowException(new RuntimeException('Registry unavailable'));
        $classifier = new ThothSubjectClassifier(fn ($code) => $code === 'ABA', null, $httpClient);

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
