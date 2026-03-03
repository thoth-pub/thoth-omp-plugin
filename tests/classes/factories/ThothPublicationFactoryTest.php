<?php


namespace APP\plugins\generic\thoth\tests\classes\factories;
/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothPublicationFactoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
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

require_once(__DIR__ . '/../../../vendor/autoload.php');

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Models\Publication as ThothPublication;
use APP\plugins\generic\thoth\classes\factories\ThothPublicationFactory;

class ThothPublicationFactoryTest extends PKPTestCase
{
    protected array $mocks = [];
    private function setUpMockEnvironment()
    {
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
            ->onlyMethods(['getEntryKey', 'getLocalizedName', 'getIdentificationCodes'])
            ->getMock();
        $mockPubFormat->expects($this->any())
            ->method('getEntryKey')
            ->willReturn('DA');
        $mockPubFormat->expects($this->any())
            ->method('getLocalizedName')
            ->willReturn('PDF');
        $mockPubFormat->expects($this->any())
            ->method('getIdentificationCodes')
            ->willReturn($mockResult);

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
}
