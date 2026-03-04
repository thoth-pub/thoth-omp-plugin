<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothPublicationServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothPublicationService
 *
 * @brief Test class for the ThothPublicationService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

use APP\publicationFormat\PublicationFormat;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Publication as ThothPublication;
use APP\plugins\generic\thoth\classes\container\ThothContainer;
use APP\plugins\generic\thoth\classes\factories\ThothPublicationFactory;
use APP\plugins\generic\thoth\classes\repositories\ThothPublicationRepository;
use APP\plugins\generic\thoth\classes\services\ThothPublicationService;

class ThothPublicationServiceTest extends PKPTestCase
{
    protected mixed $backup = null;
    public function setUp(): void
    {
        parent::setUp();
        $this->backup = ThothContainer::getInstance()->backup('client');
    }

    protected function tearDown(): void
    {
        ThothContainer::getInstance()->set('client', $this->backup);
        parent::tearDown();
    }

    public function testRegisterPublication()
    {
        ThothContainer::getInstance()->set('client', function () {
            return $this->getMockBuilder(ThothClient::class)->getMock();
        });

        $mockFactory = $this->getMockBuilder(ThothPublicationFactory::class)
            ->onlyMethods(['createFromPublicationFormat'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublicationFormat')
            ->willReturn(new ThothPublication());

        $mockRepository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->onlyMethods(['add', 'getIdByType'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->willReturn('4296c934-0f05-4920-a208-a5ab214b908a');
        $mockRepository->expects($this->once())
            ->method('getIdByType')
            ->willReturn(null);

        $mockPubFormat = $this->getMockBuilder(PublicationFormat::class)->getMock();

        $thothWorkId = '14d026ea-803f-4e51-a813-cea355287ab6';

        $service = new ThothPublicationService($mockFactory, $mockRepository);
        $thothPublicationId = $service->register($mockPubFormat, $thothWorkId);

        $this->assertSame('4296c934-0f05-4920-a208-a5ab214b908a', $thothPublicationId);
    }

    public function testIsbnPublicationValidationFails()
    {
        $mockFactory = $this->getMockBuilder(ThothPublicationFactory::class)
            ->onlyMethods(['createFromPublicationFormat'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublicationFormat')
            ->willReturn(new ThothPublication([
                'isbn' => '978395796140'
            ]));

        $mockRepository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->getMock();

        $mockPubFormat = $this->getMockBuilder(PublicationFormat::class)->getMock();

        $service = new ThothPublicationService($mockFactory, $mockRepository);
        $errors = $service->validate($mockPubFormat);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.isbn##',
        ], $errors);
    }

    public function testIsbnExistsPublicationValidationFails()
    {
        $mockFactory = $this->getMockBuilder(ThothPublicationFactory::class)
            ->onlyMethods(['createFromPublicationFormat'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublicationFormat')
            ->willReturn(new ThothPublication([
                'isbn' => '978-3-16-148410-0'
            ]));

        $mockRepository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('find')
            ->willReturn(new ThothPublication());

        $mockPubFormat = $this->getMockBuilder(PublicationFormat::class)->getMock();

        $service = new ThothPublicationService($mockFactory, $mockRepository);
        $errors = $service->validate($mockPubFormat);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.isbnExists##',
        ], $errors);
    }
}
