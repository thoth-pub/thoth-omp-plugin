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

use APP\plugins\generic\thoth\classes\container\ThothContainer;
use APP\plugins\generic\thoth\classes\factories\ThothPublicationFactory;
use APP\plugins\generic\thoth\classes\repositories\ThothPublicationRepository;
use APP\plugins\generic\thoth\classes\services\ThothLocationService;
use APP\plugins\generic\thoth\classes\services\ThothPublicationService;
use APP\publicationFormat\IdentificationCode;
use APP\publicationFormat\PublicationFormat;
use PKP\submissionFile\SubmissionFile;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\LocationPlatform;
use ThothApi\GraphQL\Enums\PublicationType;
use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Inputs\PatchLocation as ThothLocation;
use ThothApi\GraphQL\Inputs\PatchPublication as ThothPublication;

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
        $mockLocationService = $this->createMock(ThothLocationService::class);
        $mockLocationService->expects($this->once())
            ->method('registerByPublicationFormat')
            ->with($mockPubFormat, null);

        $thothWorkId = '14d026ea-803f-4e51-a813-cea355287ab6';

        $service = new ThothPublicationService($mockFactory, $mockRepository, $mockLocationService);
        $thothPublicationId = $service->register($mockPubFormat, $thothWorkId);

        $this->assertSame('4296c934-0f05-4920-a208-a5ab214b908a', $thothPublicationId);
    }

    public function testUpdateReconcilesPublicationsAndLocationsAndDeletesDifferences(): void
    {
        $pdfFormat = $this->createMock(PublicationFormat::class);
        $pdfFormat->method('getId')->willReturn(1);
        $pdfFormat->method('getPhysicalFormat')->willReturn(false);
        $pdfFormat->method('getEntryKey')->willReturn('DA');
        $pdfFormat->method('getLocalizedName')->willReturn('PDF');
        $pdfFormat->method('getIdentificationCodes')->willReturn($this->emptyIdentificationCodes());
        $pdfFormat->expects($this->once())
            ->method('setData')
            ->with('thothPublicationId', 'pdf-publication-id');

        $epubFormat = $this->createMock(PublicationFormat::class);
        $epubFormat->method('getId')->willReturn(2);
        $epubFormat->method('getPhysicalFormat')->willReturn(false);
        $epubFormat->method('getEntryKey')->willReturn('DA');
        $epubFormat->method('getLocalizedName')->willReturn('EPUB');
        $epubFormat->method('getIdentificationCodes')->willReturn($this->emptyIdentificationCodes());
        $epubFormat->expects($this->once())
            ->method('setData')
            ->with('thothPublicationId', 'new-epub-publication-id');

        $pdfFile = new SubmissionFile();
        $pdfFile->setData('originalFileName', 'book.pdf');
        $epubFile = new SubmissionFile();
        $epubFile->setData('originalFileName', 'book.epub');

        $factory = new ThothPublicationFactory();

        $repository = $this->createMock(ThothPublicationRepository::class);
        $repository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function (ThothPublication $publication): bool {
                return $publication->getPublicationId() === 'pdf-publication-id'
                    && $publication->getWorkId() === 'work-id'
                    && $publication->getIsbn() === null
                    && $publication->hasAccessibilityStandard()
                    && $publication->getAccessibilityStandard() === null;
            }));
        $repository->expects($this->once())
            ->method('add')
            ->with($this->callback(function (ThothPublication $publication): bool {
                return $publication->getPublicationType() === PublicationType::EPUB
                    && $publication->getWorkId() === 'work-id';
            }))
            ->willReturn('new-epub-publication-id');
        $repository->expects($this->once())
            ->method('delete')
            ->with('mobi-publication-id');

        $locationService = $this->createMock(ThothLocationService::class);
        $pdfLocation = new ThothLocation([
            'fullTextUrl' => 'https://publisher.example/book.pdf',
            'locationPlatform' => LocationPlatform::OTHER,
        ]);
        $epubLocation = new ThothLocation([
            'fullTextUrl' => 'https://publisher.example/book.epub',
            'locationPlatform' => LocationPlatform::OTHER,
        ]);
        $locationService->expects($this->exactly(2))
            ->method('getDesiredByPublicationFormat')
            ->willReturnMap([
                [$pdfFormat, [$pdfFile], [$pdfLocation]],
                [$epubFormat, [$epubFile], [$epubLocation]],
            ]);
        $locationService->expects($this->exactly(2))
            ->method('update');

        $service = new ThothPublicationService($factory, $repository, $locationService);
        $deletionsSkipped = $service->update(
            [$pdfFormat, $epubFormat],
            'work-id',
            [
                [
                    'publicationId' => 'pdf-publication-id',
                    'publicationType' => PublicationType::PDF,
                ],
                [
                    'publicationId' => 'mobi-publication-id',
                    'publicationType' => PublicationType::MOBI,
                ],
            ],
            [1 => [$pdfFile], 2 => [$epubFile]],
            WorkStatus::FORTHCOMING
        );

        $this->assertFalse($deletionsSkipped);
    }

    public function testUpdateSkipsPublicationDeletionsForActiveWork(): void
    {
        $repository = $this->createMock(ThothPublicationRepository::class);
        $repository->expects($this->never())->method('delete');
        $service = new ThothPublicationService(
            new ThothPublicationFactory(),
            $repository,
            $this->createMock(ThothLocationService::class)
        );

        $this->assertTrue($service->update(
            [],
            'work-id',
            [[
                'publicationId' => 'remaining-publication-id',
                'publicationType' => PublicationType::PDF,
            ]],
            [],
            WorkStatus::ACTIVE
        ));
    }

    public function testUpdateMatchesSameTypePublicationByNormalizedIsbn(): void
    {
        $publicationFormat = $this->createMock(PublicationFormat::class);
        $publicationFormat->method('getId')->willReturn(1);
        $publicationFormat->method('getPhysicalFormat')->willReturn(true);
        $publicationFormat->method('getEntryKey')->willReturn('DA');
        $publicationFormat->method('getLocalizedName')->willReturn('PDF');
        $identificationCode = new IdentificationCode();
        $identificationCode->setCode('15');
        $identificationCode->setValue('978-3-16-148410-0');
        $publicationFormat->method('getIdentificationCodes')
            ->willReturn($this->identificationCodes([$identificationCode]));
        $publicationFormat->expects($this->once())
            ->method('setData')
            ->with('thothPublicationId', 'matching-publication-id');

        $factory = new ThothPublicationFactory();

        $repository = $this->createMock(ThothPublicationRepository::class);
        $repository->expects($this->never())->method('add');
        $repository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function (ThothPublication $publication): bool {
                return $publication->getPublicationId() === 'matching-publication-id';
            }));
        $repository->expects($this->once())
            ->method('delete')
            ->with('other-publication-id');

        $locationService = $this->createMock(ThothLocationService::class);
        $locationService->expects($this->once())
            ->method('getDesiredByPublicationFormat')
            ->with($publicationFormat, [])
            ->willReturn([]);
        $locationService->expects($this->once())
            ->method('update')
            ->with('matching-publication-id', [], []);

        $service = new ThothPublicationService($factory, $repository, $locationService);
        $service->update([$publicationFormat], 'work-id', [
            [
                'publicationId' => 'other-publication-id',
                'publicationType' => PublicationType::PDF,
                'isbn' => '978-1-4028-9462-6',
            ],
            [
                'publicationId' => 'matching-publication-id',
                'publicationType' => PublicationType::PDF,
                'isbn' => '9783161484100',
            ],
        ]);

    }

    public function testUpdateMatchesSameTypePublicationByLocationsWhenIsbnIsMissing(): void
    {
        $publicationFormat = $this->createMock(PublicationFormat::class);
        $publicationFormat->method('getId')->willReturn(1);
        $publicationFormat->method('getPhysicalFormat')->willReturn(true);
        $publicationFormat->method('getEntryKey')->willReturn('DA');
        $publicationFormat->method('getLocalizedName')->willReturn('PDF');
        $publicationFormat->method('getIdentificationCodes')->willReturn($this->emptyIdentificationCodes());
        $publicationFormat->expects($this->once())
            ->method('setData')
            ->with('thothPublicationId', 'matching-publication-id');

        $factory = new ThothPublicationFactory();

        $desiredLocation = new ThothLocation([
            'landingPage' => 'https://publisher.example/book',
            'fullTextUrl' => 'https://publisher.example/matching.pdf',
            'locationPlatform' => LocationPlatform::OTHER,
        ]);
        $matchingLocations = [[
            'locationId' => 'matching-location-id',
            'landingPage' => 'https://publisher.example/book',
            'fullTextUrl' => 'https://publisher.example/matching.pdf',
            'locationPlatform' => LocationPlatform::OTHER,
            'canonical' => true,
        ]];
        $locationService = $this->createMock(ThothLocationService::class);
        $locationService->method('getDesiredByPublicationFormat')->willReturn([$desiredLocation]);
        $locationService->expects($this->once())
            ->method('update')
            ->with('matching-publication-id', [$desiredLocation], $matchingLocations);

        $repository = $this->createMock(ThothPublicationRepository::class);
        $repository->expects($this->never())->method('add');
        $repository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function (ThothPublication $publication): bool {
                return $publication->getPublicationId() === 'matching-publication-id';
            }));
        $repository->expects($this->once())
            ->method('delete')
            ->with('other-publication-id');

        $service = new ThothPublicationService($factory, $repository, $locationService);
        $service->update([$publicationFormat], 'work-id', [
            [
                'publicationId' => 'other-publication-id',
                'publicationType' => PublicationType::PDF,
                'isbn' => null,
                'locations' => [[
                    'locationId' => 'other-location-id',
                    'landingPage' => 'https://publisher.example/book',
                    'fullTextUrl' => 'https://publisher.example/other.pdf',
                    'locationPlatform' => LocationPlatform::OTHER,
                    'canonical' => true,
                ]],
            ],
            [
                'publicationId' => 'matching-publication-id',
                'publicationType' => PublicationType::PDF,
                'isbn' => null,
                'locations' => $matchingLocations,
            ],
        ]);

    }

    public function testUpdateRejectsAmbiguousSameTypePublications(): void
    {
        $publicationFormat = $this->createMock(PublicationFormat::class);
        $publicationFormat->method('getId')->willReturn(1);
        $publicationFormat->method('getPhysicalFormat')->willReturn(true);
        $publicationFormat->method('getEntryKey')->willReturn('DA');
        $publicationFormat->method('getLocalizedName')->willReturn('PDF');
        $publicationFormat->method('getIdentificationCodes')->willReturn($this->emptyIdentificationCodes());

        $factory = new ThothPublicationFactory();
        $locationService = $this->createMock(ThothLocationService::class);
        $locationService->method('getDesiredByPublicationFormat')->willReturn([]);
        $repository = $this->createMock(ThothPublicationRepository::class);
        $repository->expects($this->never())->method('add');
        $repository->expects($this->never())->method('edit');
        $repository->expects($this->never())->method('delete');

        $this->expectException(\UnexpectedValueException::class);

        $service = new ThothPublicationService($factory, $repository, $locationService);
        $service->update([$publicationFormat], 'work-id', [
            ['publicationId' => 'first-publication-id', 'publicationType' => PublicationType::PDF],
            ['publicationId' => 'second-publication-id', 'publicationType' => PublicationType::PDF],
        ]);
    }

    private function emptyIdentificationCodes(): \PKP\db\DAOResultFactory
    {
        return $this->identificationCodes([]);
    }

    private function identificationCodes(array $identificationCodes): \PKP\db\DAOResultFactory
    {
        $result = $this->createMock(\PKP\db\DAOResultFactory::class);
        $result->method('toArray')->willReturn($identificationCodes);
        return $result;
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

        $service = new ThothPublicationService(
            $mockFactory,
            $mockRepository,
            $this->createMock(ThothLocationService::class)
        );
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

        $service = new ThothPublicationService(
            $mockFactory,
            $mockRepository,
            $this->createMock(ThothLocationService::class)
        );
        $errors = $service->validate($mockPubFormat);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.isbnExists##',
        ], $errors);
    }
}
