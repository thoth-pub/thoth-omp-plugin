<?php

/**
 * @file plugins/generic/thoth/tests/classes/ThothValidatorTest.php
 *
 * Copyright (c) 2025 Lepidus Tecnologia
 * Copyright (c) 2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothValidatorTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothValidator
 *
 * @brief Test class for the ThothValidator class
 */

use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Models\Work as ThothWork;

import('plugins.generic.thoth.classes.ThothValidator');

class ThothValidatorTest extends PKPTestCase
{
    public function testISBNValidationFails()
    {
        $publicationFormats = [
            Mockery::mock(\APP\publicationFormat\PublicationFormat::class)
                ->makePartial()
                ->shouldReceive('getLocalizedName')
                ->withAnyArgs()
                ->andReturn('PDF')
                ->shouldReceive('getIdentificationCodes')
                ->withAnyArgs()
                ->andReturn(
                    Mockery::mock(\PKP\db\DAOResultFactory::class)
                        ->makePartial()
                        ->shouldReceive('toArray')
                        ->withAnyArgs()
                        ->andReturn([
                            Mockery::mock(\APP\publicationFormat\IdentificationCode::class)
                                ->makePartial()
                                ->shouldReceive('getCode')
                                ->withAnyArgs()
                                ->andReturn('24')
                                ->shouldReceive('getValue')
                                ->withAnyArgs()
                                ->andReturn('97-83-9-579-61')
                                ->getMock()
                        ])
                        ->getMock()
                )
                ->getMock(),
            Mockery::mock(\APP\publicationFormat\PublicationFormat::class)
                ->makePartial()
                ->shouldReceive('getLocalizedName')
                ->withAnyArgs()
                ->andReturn('EPUB')
                ->shouldReceive('getIdentificationCodes')
                ->withAnyArgs()
                ->andReturn(
                    Mockery::mock(\PKP\db\DAOResultFactory::class)
                        ->makePartial()
                        ->shouldReceive('toArray')
                        ->withAnyArgs()
                        ->andReturn([
                            Mockery::mock(\APP\publicationFormat\IdentificationCode::class)
                                ->makePartial()
                                ->shouldReceive('getCode')
                                ->withAnyArgs()
                                ->andReturn('15')
                                ->shouldReceive('getValue')
                                ->withAnyArgs()
                                ->andReturn('978395796140')
                                ->getMock()
                        ])
                        ->getMock()
                )
                ->getMock(),
        ];

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
}
