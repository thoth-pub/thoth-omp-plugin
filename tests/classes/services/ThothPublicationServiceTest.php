<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothPublicationServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothPublicationService
 *
 * @brief Test class for the ThothPublicationService class
 */

import('lib.pkp.tests.PKPTestCase');
import('classes.monograph.Author');
import('plugins.generic.thoth.classes.services.ThothPublicationService');

class ThothPublicationServiceTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->publicationService = new ThothPublicationService();
    }

    protected function tearDown(): void
    {
        unset($this->publicationService);
        parent::tearDown();
    }

    public function testGetPublicationTypeByPublicationFormat()
    {
        $publicationFormat = DAORegistry::getDAO('PublicationFormatDAO')->newDataObject();

        $publicationFormat->setEntryKey('BC');
        $this->assertEquals(
            ThothPublication::PUBLICATION_TYPE_PAPERBACK,
            $this->publicationService->getPublicationTypeByPublicationFormat($publicationFormat)
        );
        $publicationFormat->setEntryKey('BB');
        $this->assertEquals(
            ThothPublication::PUBLICATION_TYPE_HARDBACK,
            $this->publicationService->getPublicationTypeByPublicationFormat($publicationFormat)
        );
        $publicationFormat->setEntryKey('DA');
        $publicationFormat->setName('HTML', 'en_US');
        $this->assertEquals(
            ThothPublication::PUBLICATION_TYPE_HTML,
            $this->publicationService->getPublicationTypeByPublicationFormat($publicationFormat)
        );
        $publicationFormat->setEntryKey('DA');
        $publicationFormat->setName('PDF', 'en_US');
        $this->assertEquals(
            ThothPublication::PUBLICATION_TYPE_PDF,
            $this->publicationService->getPublicationTypeByPublicationFormat($publicationFormat)
        );
        $publicationFormat->setEntryKey('DA');
        $publicationFormat->setName('XML', 'en_US');
        $this->assertEquals(
            ThothPublication::PUBLICATION_TYPE_XML,
            $this->publicationService->getPublicationTypeByPublicationFormat($publicationFormat)
        );
        $publicationFormat->setEntryKey('DA');
        $publicationFormat->setName('EPUB', 'en_US');
        $this->assertEquals(
            ThothPublication::PUBLICATION_TYPE_EPUB,
            $this->publicationService->getPublicationTypeByPublicationFormat($publicationFormat)
        );
        $publicationFormat->setEntryKey('DA');
        $publicationFormat->setName('MOBI', 'en_US');
        $this->assertEquals(
            ThothPublication::PUBLICATION_TYPE_MOBI,
            $this->publicationService->getPublicationTypeByPublicationFormat($publicationFormat)
        );
        $publicationFormat->setEntryKey('DA');
        $publicationFormat->setName('AZW3', 'en_US');
        $this->assertEquals(
            ThothPublication::PUBLICATION_TYPE_AZW3,
            $this->publicationService->getPublicationTypeByPublicationFormat($publicationFormat)
        );
        $publicationFormat->setEntryKey('DA');
        $publicationFormat->setName('DOCX', 'en_US');
        $this->assertEquals(
            ThothPublication::PUBLICATION_TYPE_DOCX,
            $this->publicationService->getPublicationTypeByPublicationFormat($publicationFormat)
        );
        $publicationFormat->setEntryKey('DA');
        $publicationFormat->setName('Fiction Book', 'en_US');
        $this->assertEquals(
            ThothPublication::PUBLICATION_TYPE_FICTION_BOOK,
            $this->publicationService->getPublicationTypeByPublicationFormat($publicationFormat)
        );
    }

    public function testGetIsbnByPublicationFormat()
    {
        $identificationCode = DAORegistry::getDAO('IdentificationCodeDAO')->newDataObject();
        $identificationCode->setCode('15');
        $identificationCode->setValue('978-3-95796-140-2');

        $mockResult = $this->getMockBuilder(DAOResultFactory::class)
            ->setMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockResult->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue([$identificationCode]));

        $publicationFormat = $mockRequest = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods(['getIdentificationCodes'])
            ->getMock();
        $publicationFormat->expects($this->any())
            ->method('getIdentificationCodes')
            ->will($this->returnValue($mockResult));

        $this->assertEquals(
            '978-3-95796-140-2',
            $this->publicationService->getIsbnByPublicationFormat($publicationFormat)
        );
    }

    public function testGetPublicationPropertiesByPublicationFormat()
    {
        $expectedProps = [
            'publicationType' => ThothPublication::PUBLICATION_TYPE_PAPERBACK,
            'isbn' => '978-0-615-94946-8',
        ];

        $identificationCode = DAORegistry::getDAO('IdentificationCodeDAO')->newDataObject();
        $identificationCode->setCode('02');
        $identificationCode->setValue('978-0-615-94946-8');

        $mockResult = $this->getMockBuilder(DAOResultFactory::class)
            ->setMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockResult->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue([$identificationCode]));

        $publicationFormat = $mockRequest = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods(['getIdentificationCodes'])
            ->getMock();
        $publicationFormat->expects($this->any())
            ->method('getIdentificationCodes')
            ->will($this->returnValue($mockResult));
        $publicationFormat->setEntryKey('BC');

        $publicationProps = $this->publicationService->getPropertiesByPublicationFormat($publicationFormat);

        $this->assertEquals($expectedProps, $publicationProps);
    }

    public function testCreateNewContributor()
    {
        $expectedPublication = new ThothPublication();
        $expectedPublication->setPublicationType(ThothPublication::PUBLICATION_TYPE_PDF);
        $expectedPublication->setIsbn('978-0-615-62535-5');

        $params = [
            'publicationType' => ThothPublication::PUBLICATION_TYPE_PDF,
            'isbn' => '978-0-615-62535-5',
        ];

        $publication = $this->publicationService->new($params);
        $this->assertEquals($expectedPublication, $publication);
    }
}
