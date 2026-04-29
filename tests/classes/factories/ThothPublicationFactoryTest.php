<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothPublicationFactoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationFactoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothPublicationFactory
 *
 * @brief Test class for the ThothPublicationFactory class
 */

namespace APP\plugins\generic\thoth\tests\classes\factories;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\factories\ThothPublicationFactory;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Models\Publication as ThothPublication;

class ThothPublicationFactoryTest extends PKPTestCase
{
    protected array $mocks = [];
    private function setUpMockEnvironment(
        string $entryKey = 'DA',
        string $localizedName = 'PDF',
        ?string $remoteUrl = null,
        array $publicationFormatData = []
    ) {
        $mockIdentificationCode = $this->getMockBuilder(\APP\publicationFormat\IdentificationCode::class)
            ->onlyMethods(['getCode', 'getValue'])
            ->getMock();
        $mockIdentificationCode->expects($this->any())
            ->method('getCode')
            ->willReturn('15');
        $mockIdentificationCode->expects($this->any())
            ->method('getValue')
            ->willReturn('978-3-16-148410-0');

        $mockResult = $this->getMockBuilder(\PKP\db\DAOResultFactory::class)
            ->onlyMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockResult->expects($this->any())
            ->method('toArray')
            ->willReturn([$mockIdentificationCode]);

        $mockPubFormat = $this->getMockBuilder(\APP\publicationFormat\PublicationFormat::class)
            ->onlyMethods(['getEntryKey', 'getLocalizedName', 'getIdentificationCodes', 'getData'])
            ->getMock();
        $mockPubFormat->expects($this->any())
            ->method('getEntryKey')
            ->willReturn($entryKey);
        $mockPubFormat->expects($this->any())
            ->method('getLocalizedName')
            ->willReturn($localizedName);
        $mockPubFormat->expects($this->any())
            ->method('getIdentificationCodes')
            ->willReturn($mockResult);
        $publicationFormatData['urlRemote'] = $remoteUrl;

        $mockPubFormat->expects($this->any())
            ->method('getData')
            ->willReturnCallback(function ($key) use ($publicationFormatData) {
                return $publicationFormatData[$key] ?? null;
            });

        $this->mocks = [];
        $this->mocks['publicationFormat'] = $mockPubFormat;
    }

    public function testCreateThothPublicationFromPublicationFormat()
    {
        $this->setUpMockEnvironment();
        $mockPubFormat = $this->mocks['publicationFormat'];

        $factory = new ThothPublicationFactory();
        $thothPublication = $factory->createFromPublicationFormat($mockPubFormat);

        $this->assertEquals(new ThothPublication([
            'publicationType' => ThothPublication::PUBLICATION_TYPE_PDF,
            'isbn' => '978-3-16-148410-0',
        ]), $thothPublication);
    }

    public function testCreateThothPublicationFromSubmissionFileExtension()
    {
        $this->setUpMockEnvironment('DA', 'Digital');
        $mockPubFormat = $this->mocks['publicationFormat'];

        $mockSubmissionFile = new class () {
            public function getOriginalFileName()
            {
                return 'book.docx';
            }

            public function getServerFileName()
            {
                return null;
            }

            public function getFileType()
            {
                return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            }

            public function getData($key)
            {
                return null;
            }
        };

        $factory = new ThothPublicationFactory();
        $thothPublication = $factory->createFromPublicationFormat($mockPubFormat, $mockSubmissionFile);

        $this->assertSame(ThothPublication::PUBLICATION_TYPE_DOCX, $thothPublication->getPublicationType());
    }

    public function testCreateThothPublicationFromRemoteUrlExtension()
    {
        $this->setUpMockEnvironment('DA', 'Digital', 'https://example.com/catalog/book.epub');
        $mockPubFormat = $this->mocks['publicationFormat'];

        $factory = new ThothPublicationFactory();
        $thothPublication = $factory->createFromPublicationFormat($mockPubFormat);

        $this->assertSame(ThothPublication::PUBLICATION_TYPE_EPUB, $thothPublication->getPublicationType());
    }

    public function testCreateThothPublicationFromPublicationFormatAccessibilityMetadata()
    {
        $this->setUpMockEnvironment('DA', 'PDF', null, [
            'accessibilityStandard' => 'WCAG21AA',
            'accessibilityAdditionalStandard' => 'PDF_UA1',
            'accessibilityException' => 'MICRO_ENTERPRISES',
            'accessibilityReportUrl' => 'https://example.com/accessibility-report',
        ]);
        $mockPubFormat = $this->mocks['publicationFormat'];

        $factory = new ThothPublicationFactory();
        $thothPublication = $factory->createFromPublicationFormat($mockPubFormat);

        $this->assertSame('WCAG21AA', $thothPublication->getData('accessibilityStandard'));
        $this->assertSame('PDF_UA1', $thothPublication->getData('accessibilityAdditionalStandard'));
        $this->assertSame('MICRO_ENTERPRISES', $thothPublication->getData('accessibilityException'));
        $this->assertSame(
            'https://example.com/accessibility-report',
            $thothPublication->getData('accessibilityReportUrl')
        );
    }
}
