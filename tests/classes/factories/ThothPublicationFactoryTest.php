<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothPublicationFactoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationFactoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothPublicationFactory
 *
 * @brief Test class for the ThothPublicationFactory class
 */

use ThothApi\GraphQL\Models\Publication as ThothPublication;

import('classes.publicationFormat.IdentificationCode');
import('classes.publicationFormat.PublicationFormat');
import('lib.pkp.classes.db.DAOResultFactory');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothPublicationFactory');

class ThothPublicationFactoryTest extends PKPTestCase
{
    private function setUpMockEnvironment()
    {
        $mockIdentificationCode = $this->getMockBuilder(IdentificationCode::class)
            ->setMethods(['getCode', 'getValue'])
            ->getMock();
        $mockIdentificationCode->expects($this->any())
            ->method('getCode')
            ->will($this->returnValue('15'));
        $mockIdentificationCode->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue('978-3-16-148410-0'));

        $mockResult = $this->getMockBuilder(DAOResultFactory::class)
            ->setMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockResult->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue([$mockIdentificationCode]));

        $mockPubFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods(['getEntryKey', 'getLocalizedName', 'getIdentificationCodes'])
            ->getMock();
        $mockPubFormat->expects($this->any())
            ->method('getEntryKey')
            ->will($this->returnValue('DA'));
        $mockPubFormat->expects($this->any())
            ->method('getLocalizedName')
            ->will($this->returnValue('PDF'));
        $mockPubFormat->expects($this->any())
            ->method('getIdentificationCodes')
            ->will($this->returnValue($mockResult));

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
