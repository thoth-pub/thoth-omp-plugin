<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothBookServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothBookService
 *
 * @brief Test class for the ThothBookService class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Work as ThothWork;

import('classes.publication.Publication');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothBookFactory');
import('plugins.generic.thoth.classes.repositories.ThothBookRepository');
import('plugins.generic.thoth.classes.services.ThothBookService');

class ThothBookServiceTest extends PKPTestCase
{
    public function setUp(): void
    {
        $this->backup = ThothContainer::getInstance()->backup('client');
    }

    protected function tearDown(): void
    {
        ThothContainer::getInstance()->set('client', $this->backup);
        parent::tearDown();
    }

    public function testRegisterBook()
    {
        ThothContainer::getInstance()->set('client', function () {
            return $this->getMockBuilder(ThothClient::class)->getMock();
        });

        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->setMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->will($this->returnValue(new ThothWork()));

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3'));

        $mockPublication = $this->getMockBuilder(Publication::class)->getMock();

        $thothImprintId = 'f740cf4e-16d1-487c-9a92-615882a591e9';

        $service = new ThothBookService($mockFactory, $mockRepository);
        $thothBookId = $service->register($mockPublication, $thothImprintId);

        $this->assertSame('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3', $thothBookId);
    }

    public function testDoiExistsBookValidationFails()
    {
        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->setMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->will($this->returnValue(new ThothWork([
                'doi' => 'https://doi.org/10.12345/10101010'
            ])));

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['getByDoi'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('getByDoi')
            ->will($this->returnValue(new ThothWork()));

        $mockPublication = $this->getMockBuilder(Publication::class)->getMock();

        $service = new ThothBookService($mockFactory, $mockRepository);
        $errors = $service->validate($mockPublication);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.doiExists##',
        ], $errors);
    }

    public function testLandingPageExistsBookValidationFails()
    {
        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->setMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->will($this->returnValue(new ThothWork([
                'landingPage' => 'http://www.publicknowledge.omp/index.php/publicknowledge/catalog/book/14'
            ])));

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['find'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('find')
            ->will($this->returnValue(new ThothWork()));

        $mockPublication = $this->getMockBuilder(Publication::class)->getMock();

        $service = new ThothBookService($mockFactory, $mockRepository);
        $errors = $service->validate($mockPublication);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.landingPageExists##',
        ], $errors);
    }
}
