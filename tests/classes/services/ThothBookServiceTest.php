<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothBookServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothBookService
 *
 * @brief Test class for the ThothBookService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

use APP\plugins\generic\thoth\classes\container\ThothContainer;
use APP\plugins\generic\thoth\classes\factories\ThothBookFactory;
use APP\plugins\generic\thoth\classes\repositories\ThothBookRepository;
use APP\plugins\generic\thoth\classes\services\ThothAbstractService;
use APP\plugins\generic\thoth\classes\services\ThothBookService;
use APP\plugins\generic\thoth\classes\services\ThothPublicationService;
use APP\plugins\generic\thoth\classes\services\ThothTitleService;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

class ThothBookServiceTest extends PKPTestCase
{
    protected array $backups = [];
    public function setUp(): void
    {
        parent::setUp();
        $container = ThothContainer::getInstance();
        $this->backups = [
            'client' => $container->backup('client'),
            'abstractService' => $container->backup('abstractService'),
            'publicationService' => $container->backup('publicationService'),
            'titleService' => $container->backup('titleService'),
        ];
    }

    protected function tearDown(): void
    {
        $container = ThothContainer::getInstance();
        foreach ($this->backups as $key => $factory) {
            $container->set($key, $factory);
        }
        parent::tearDown();
    }

    public function testRegisterBook()
    {
        $container = ThothContainer::getInstance();

        $container->set('client', function () {
            return $this->getMockBuilder(ThothClient::class)->getMock();
        });

        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->onlyMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->willReturn(new ThothWork());

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->willReturn('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3');

        $mockPublication = $this->getMockBuilder(\APP\publication\Publication::class)
            ->onlyMethods(['getData'])
            ->getMock();
        $mockPublication->expects($this->any())
            ->method('getData')
            ->willReturnCallback(function ($key) {
                $values = [
                    'locale' => 'en_US',
                    'title' => [
                        'en_US' => 'My book title',
                        'pt_BR' => 'Meu titulo',
                    ],
                    'subtitle' => [
                        'en_US' => 'My book subtitle',
                        'pt_BR' => 'Meu subtitulo',
                    ],
                    'abstract' => [
                        'en_US' => 'This is my book abstract',
                        'pt_BR' => 'Este e meu resumo',
                    ],
                ];

                return $values[$key] ?? null;
            });

        $thothImprintId = 'f740cf4e-16d1-487c-9a92-615882a591e9';

        $service = new ThothBookService(
            $mockFactory,
            $mockRepository,
            $this->createMock(ThothPublicationService::class),
            $this->createMock(ThothTitleService::class),
            $this->createMock(ThothAbstractService::class)
        );
        $thothBookId = $service->register($mockPublication, $thothImprintId);

        $this->assertSame('d8fa2e63-5513-45e5-84c1-e9c2d89f99d3', $thothBookId);
    }

    public function testDoiExistsBookValidationFails()
    {
        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->onlyMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->willReturn(new ThothWork([
                'doi' => 'https://doi.org/10.12345/10101010'
            ]));

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['getByDoi'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('getByDoi')
            ->willReturn(new ThothWork());

        $mockPublication = $this->getMockBuilder(\APP\publication\Publication::class)->getMock();

        $service = new ThothBookService(
            $mockFactory,
            $mockRepository,
            $this->createMock(ThothPublicationService::class),
            $this->createMock(ThothTitleService::class),
            $this->createMock(ThothAbstractService::class)
        );
        $errors = $service->validate($mockPublication);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.doiExists##',
        ], $errors);
    }

    public function testLandingPageExistsBookValidationFails()
    {
        $mockFactory = $this->getMockBuilder(ThothBookFactory::class)
            ->onlyMethods(['createFromPublication'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublication')
            ->willReturn(new ThothWork([
                'landingPage' => 'http://www.publicknowledge.omp/index.php/publicknowledge/catalog/book/14'
            ]));

        $mockRepository = $this->getMockBuilder(ThothBookRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['find'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('find')
            ->willReturn(new ThothWork([
                'landingPage' => 'http://www.publicknowledge.omp/index.php/publicknowledge/catalog/book/14'
            ]));

        $mockPublication = $this->getMockBuilder(\APP\publication\Publication::class)->getMock();

        $service = new ThothBookService(
            $mockFactory,
            $mockRepository,
            $this->createMock(ThothPublicationService::class),
            $this->createMock(ThothTitleService::class),
            $this->createMock(ThothAbstractService::class)
        );
        $errors = $service->validate($mockPublication);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.landingPageExists##',
        ], $errors);
    }
}
