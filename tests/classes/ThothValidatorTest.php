<?php

/**
 * @file plugins/generic/thoth/tests/classes/ThothValidatorTest.php
 *
 * Copyright (c) 2025 Lepidus Tecnologia
 * Copyright (c) 2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothValidatorTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothValidator
 *
 * @brief Test class for the ThothValidator class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Publication as ThothPublication;
use ThothApi\GraphQL\Models\Work as ThothWork;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.ThothValidator');
import('classes.publicationFormat.PublicationFormat');

class ThothValidatorTest extends PKPTestCase
{
    public function testISBNValidationFails()
    {
        $publicationFormats = [];

        $identificationCode1 = DAORegistry::getDAO('IdentificationCodeDAO')->newDataObject();
        $identificationCode1->setCode('15');
        $identificationCode1->setValue('978395796140');

        $mockResult1 = $this->getMockBuilder(DAOResultFactory::class)
            ->setMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockResult1->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue([$identificationCode1]));

        $identificationCode2 = DAORegistry::getDAO('IdentificationCodeDAO')->newDataObject();
        $identificationCode2->setCode('24');
        $identificationCode2->setValue('9783957961');

        $mockResult2 = $this->getMockBuilder(DAOResultFactory::class)
        ->setMethods(['toArray'])
        ->disableOriginalConstructor()
        ->getMock();
        $mockResult2->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue([$identificationCode2]));

        $publicationFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods(['getIdentificationCodes'])
            ->getMock();
        $publicationFormat->expects($this->any())
            ->method('getIdentificationCodes')
            ->will($this->returnValue($mockResult1));

        $publicationFormats[] = $publicationFormat;

        $publicationFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods(['getIdentificationCodes'])
            ->getMock();
        $publicationFormat->expects($this->any())
            ->method('getIdentificationCodes')
            ->will($this->returnValue($mockResult2));

        $publicationFormats[] = $publicationFormat;

        $errors = ThothValidator::validateIsbn($publicationFormats);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.isbn##',
            '##plugins.generic.thoth.validation.isbn##'
        ], $errors);
    }

    public function testDOIExistsValidationFails()
    {
        $doi = 'https://doi.org/10.12345/12345678';

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods([
                'workByDoi',
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('workByDoi')
            ->will($this->returnValue(new ThothWork([
                'doi' => $doi
            ])));

        ThothContainer::getInstance()->set('client', function () use ($mockThothClient) {
            return $mockThothClient;
        });

        $errors = ThothValidator::validateDoiExists($doi);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.doiExists##',
        ], $errors);
    }

    public function testLandingPageExistsValidationFails()
    {
        $landingPage = 'http://www.publicknowledge.omp/index.php/publicknowledge/catalog/book/14';

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods([
                'works',
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('works')
            ->will($this->returnValue([
                new ThothWork([
                    'landingPage' => $landingPage
                ])
            ]));

        ThothContainer::getInstance()->set('client', function () use ($mockThothClient) {
            return $mockThothClient;
        });

        $errors = ThothValidator::validateLandingPageExists($landingPage);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.landingPageExists##',
        ], $errors);
    }

    public function testISBNExistsValidationFails()
    {
        $isbn = '978-65-89999-01-3';

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods([
                'publications',
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('publications')
            ->will($this->returnValue([
                new ThothPublication([
                    'isbn' => $isbn
                ])
            ]));

        ThothContainer::getInstance()->set('client', function () use ($mockThothClient) {
            return $mockThothClient;
        });

        $errors = ThothValidator::validateIsbnExists($isbn);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.isbnExists##',
        ], $errors);
    }
}
