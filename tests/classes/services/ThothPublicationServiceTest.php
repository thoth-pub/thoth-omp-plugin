<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/services/ThothPublicationServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothPublicationService
 *
 * @brief Test class for the ThothPublicationService class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\LocationPlatform;
use ThothApi\GraphQL\Enums\PublicationType;
use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Inputs\PatchLocation as ThothLocation;
use ThothApi\GraphQL\Inputs\PatchPublication as ThothPublication;

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.db.DAOResultFactory');
import('lib.pkp.classes.submission.SubmissionFile');
import('classes.publicationFormat.IdentificationCode');
import('plugins.generic.thoth.classes.container.ThothContainer');
import('plugins.generic.thoth.classes.exceptions.MetadataSynchronizationException');
import('plugins.generic.thoth.classes.factories.ThothPublicationFactory');
import('plugins.generic.thoth.classes.repositories.ThothPublicationRepository');
import('plugins.generic.thoth.classes.services.ThothLocationService');
import('plugins.generic.thoth.classes.services.ThothPublicationService');

class ThothPublicationServiceTest extends PKPTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->backup = ThothContainer::getInstance()->backup('client');
        $this->locationServiceBackup = ThothContainer::getInstance()->backup('locationService');
    }

    protected function tearDown(): void
    {
        ThothContainer::getInstance()->set('client', $this->backup);
        ThothContainer::getInstance()->set('locationService', $this->locationServiceBackup);
        parent::tearDown();
    }

    public function testRegisterPublication()
    {
        ThothContainer::getInstance()->set('client', function () {
            return $this->getMockBuilder(ThothClient::class)->getMock();
        });
        $mockLocationService = $this->createMock(ThothLocationService::class);
        $mockLocationService->expects($this->once())
            ->method('registerByPublicationFormat')
            ->with($this->isInstanceOf(PublicationFormat::class), null);
        $mockRepository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add', 'getIdByType'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('4296c934-0f05-4920-a208-a5ab214b908a'));
        $mockRepository->expects($this->once())
            ->method('getIdByType')
            ->will($this->returnValue(null));

        $mockPubFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods(['getEntryKey', 'getIdentificationCodes', 'getData', 'setData'])
            ->getMock();
        $mockPubFormat->method('getEntryKey')->willReturn('XX');
        $mockPubFormat->method('getIdentificationCodes')->willReturn($this->emptyIdentificationCodes());
        $mockPubFormat->method('getData')->willReturn(null);
        $mockPubFormat->expects($this->once())
            ->method('setData')
            ->with('thothPublicationId', '4296c934-0f05-4920-a208-a5ab214b908a');

        $thothWorkId = '14d026ea-803f-4e51-a813-cea355287ab6';

        $service = new ThothPublicationService(
            new ThothPublicationFactory(),
            $mockRepository,
            $mockLocationService
        );
        $thothPublicationId = $service->register($mockPubFormat, $thothWorkId);

        $this->assertSame('4296c934-0f05-4920-a208-a5ab214b908a', $thothPublicationId);
    }

    public function testUpdateReconcilesPublicationsAndDeletesRemoteDifferences()
    {
        $pdfFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods([
                'getId',
                'getPhysicalFormat',
                'getEntryKey',
                'getLocalizedName',
                'getIdentificationCodes',
                'getData',
                'setData',
            ])
            ->getMock();
        $pdfFormat->method('getId')->willReturn(1);
        $pdfFormat->method('getPhysicalFormat')->willReturn(false);
        $pdfFormat->method('getEntryKey')->willReturn('DA');
        $pdfFormat->method('getLocalizedName')->willReturn('PDF');
        $pdfFormat->method('getIdentificationCodes')->willReturn($this->emptyIdentificationCodes());
        $pdfFormat->method('getData')->willReturn(null);
        $pdfFormat->expects($this->once())
            ->method('setData')
            ->with('thothPublicationId', 'pdf-publication-id');

        $epubFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods([
                'getId',
                'getPhysicalFormat',
                'getEntryKey',
                'getLocalizedName',
                'getIdentificationCodes',
                'getData',
                'setData',
            ])
            ->getMock();
        $epubFormat->method('getId')->willReturn(2);
        $epubFormat->method('getPhysicalFormat')->willReturn(false);
        $epubFormat->method('getEntryKey')->willReturn('DA');
        $epubFormat->method('getLocalizedName')->willReturn('EPUB');
        $epubFormat->method('getIdentificationCodes')->willReturn($this->emptyIdentificationCodes());
        $epubFormat->method('getData')->willReturn(null);
        $epubFormat->expects($this->once())
            ->method('setData')
            ->with('thothPublicationId', 'new-epub-publication-id');

        $pdfFile = new SubmissionFile();
        $pdfFile->setData('originalFileName', 'book.pdf');
        $epubFile = new SubmissionFile();
        $epubFile->setData('originalFileName', 'book.epub');

        $repository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['edit', 'add', 'delete'])
            ->getMock();
        $repository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function (ThothPublication $publication) {
                return $publication->getPublicationId() === 'pdf-publication-id'
                    && $publication->getWorkId() === 'work-id'
                    && $publication->getIsbn() === null
                    && $publication->hasAccessibilityStandard()
                    && $publication->getAccessibilityStandard() === null;
            }));
        $repository->expects($this->once())
            ->method('add')
            ->with($this->callback(function (ThothPublication $publication) {
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
            ->method('update')
            ->willReturnOnConsecutiveCalls(false, false);

        $service = new ThothPublicationService(
            new ThothPublicationFactory(),
            $repository,
            $locationService
        );
        $skippedPublicationDeletions = $service->update(
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

        $this->assertFalse($skippedPublicationDeletions);
    }

    public function testUpdatePreservesRemotePublicationsButReconcilesTheirLocationsForActiveWorks()
    {
        $publicationFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods([
                'getId',
                'getPhysicalFormat',
                'getEntryKey',
                'getLocalizedName',
                'getIdentificationCodes',
                'getData',
                'setData',
            ])
            ->getMock();
        $publicationFormat->method('getId')->willReturn(1);
        $publicationFormat->method('getPhysicalFormat')->willReturn(false);
        $publicationFormat->method('getEntryKey')->willReturn('DA');
        $publicationFormat->method('getLocalizedName')->willReturn('PDF');
        $publicationFormat->method('getIdentificationCodes')->willReturn($this->emptyIdentificationCodes());
        $publicationFormat->method('getData')->willReturn(null);
        $publicationFormat->expects($this->once())
            ->method('setData')
            ->with('thothPublicationId', 'pdf-publication-id');

        $submissionFile = new SubmissionFile();
        $submissionFile->setData('originalFileName', 'book.pdf');
        $desiredLocation = new ThothLocation([
            'fullTextUrl' => 'https://publisher.example/book.pdf',
            'locationPlatform' => LocationPlatform::OTHER,
        ]);
        $existingLocations = [
            [
                'locationId' => 'matching-location-id',
                'fullTextUrl' => 'https://publisher.example/book.pdf',
                'locationPlatform' => LocationPlatform::OTHER,
            ],
            [
                'locationId' => 'removed-location-id',
                'fullTextUrl' => 'https://publisher.example/removed.pdf',
                'locationPlatform' => LocationPlatform::OTHER,
            ],
        ];

        $repository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['add', 'edit', 'delete'])
            ->getMock();
        $repository->expects($this->never())->method('add');
        $repository->expects($this->once())->method('edit');
        $repository->expects($this->never())->method('delete');

        $locationService = $this->createMock(ThothLocationService::class);
        $locationService->expects($this->once())
            ->method('getDesiredByPublicationFormat')
            ->with($publicationFormat, [$submissionFile])
            ->willReturn([$desiredLocation]);
        $locationService->expects($this->once())
            ->method('update')
            ->with('pdf-publication-id', [$desiredLocation], $existingLocations);

        $service = new ThothPublicationService(
            new ThothPublicationFactory(),
            $repository,
            $locationService
        );

        $this->assertTrue($service->update(
            [$publicationFormat],
            'work-id',
            [
                [
                    'publicationId' => 'pdf-publication-id',
                    'publicationType' => PublicationType::PDF,
                    'locations' => $existingLocations,
                ],
                [
                    'publicationId' => 'remote-only-publication-id',
                    'publicationType' => PublicationType::MOBI,
                ],
            ],
            [1 => [$submissionFile]],
            WorkStatus::ACTIVE
        ));
    }

    public function testUpdateChapterPublicationOmitsBookIsbn()
    {
        $publicationFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods(['getId', 'getPhysicalFormat', 'setData'])
            ->getMock();
        $publicationFormat->method('getId')->willReturn(1);
        $publicationFormat->method('getPhysicalFormat')->willReturn(false);
        $publicationFormat->expects($this->once())
            ->method('setData')
            ->with('thothPublicationId', 'chapter-publication-id');
        $submissionFile = new SubmissionFile();

        $factory = $this->createMock(ThothPublicationFactory::class);
        $factory->method('createFromPublicationFormat')
            ->willReturn(new ThothPublication([
                'publicationType' => PublicationType::PDF,
                'isbn' => '978-1-23456-789-7',
            ]));
        $repository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['add'])
            ->getMock();
        $repository->expects($this->once())
            ->method('add')
            ->with($this->callback(function (ThothPublication $publication) {
                return $publication->getWorkId() === 'chapter-id'
                    && $publication->getIsbn() === null;
            }))
            ->willReturn('chapter-publication-id');
        $locationService = $this->createMock(ThothLocationService::class);
        $locationService->method('getDesiredByPublicationFormat')->willReturn([]);
        $locationService->expects($this->once())
            ->method('update')
            ->with('chapter-publication-id', [], []);

        $service = new ThothPublicationService($factory, $repository, $locationService);

        $service->update(
            [$publicationFormat],
            'chapter-id',
            [],
            [1 => [$submissionFile]],
            WorkStatus::FORTHCOMING,
            10
        );
    }

    public function testUpdateMatchesSameTypePublicationByNormalizedIsbn()
    {
        $publicationFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods([
                'getId',
                'getPhysicalFormat',
                'getEntryKey',
                'getIdentificationCodes',
                'getData',
                'setData',
            ])
            ->getMock();
        $publicationFormat->method('getId')->willReturn(1);
        $publicationFormat->method('getPhysicalFormat')->willReturn(true);
        $publicationFormat->method('getEntryKey')->willReturn('XX');
        $publicationFormat->method('getIdentificationCodes')->willReturn(
            $this->identificationCodes(['978-3-16-148410-0'])
        );
        $publicationFormat->method('getData')->willReturn(null);
        $publicationFormat->expects($this->once())
            ->method('setData')
            ->with('thothPublicationId', 'matching-publication-id');

        $repository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['add', 'edit', 'delete'])
            ->getMock();
        $repository->expects($this->never())->method('add');
        $repository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function (ThothPublication $publication) {
                return $publication->getPublicationId() === 'matching-publication-id';
            }));
        $repository->expects($this->once())
            ->method('delete')
            ->with('other-publication-id');

        $locationService = $this->createMock(ThothLocationService::class);
        $locationService->method('getDesiredByPublicationFormat')->willReturn([]);
        $locationService->expects($this->once())
            ->method('update')
            ->with('matching-publication-id', [], [])
            ->willReturn(false);

        $service = new ThothPublicationService(new ThothPublicationFactory(), $repository, $locationService);
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

    public function testUpdateDoesNotReportSkippedDeletionsWithoutRemoteDifferences()
    {
        $service = new ThothPublicationService(
            new ThothPublicationFactory(),
            $this->createMock(ThothPublicationRepository::class),
            $this->createMock(ThothLocationService::class)
        );

        $this->assertFalse($service->update([], 'work-id', [], [], WorkStatus::ACTIVE));
    }

    public function testUpdateMatchesSameTypePublicationByLocationsWhenIsbnIsMissing()
    {
        $publicationFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods([
                'getId',
                'getPhysicalFormat',
                'getEntryKey',
                'getIdentificationCodes',
                'getData',
                'setData',
            ])
            ->getMock();
        $publicationFormat->method('getId')->willReturn(1);
        $publicationFormat->method('getPhysicalFormat')->willReturn(true);
        $publicationFormat->method('getEntryKey')->willReturn('XX');
        $publicationFormat->method('getIdentificationCodes')->willReturn($this->emptyIdentificationCodes());
        $publicationFormat->method('getData')->willReturn(null);
        $publicationFormat->expects($this->once())
            ->method('setData')
            ->with('thothPublicationId', 'matching-publication-id');

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
            ->with('matching-publication-id', [$desiredLocation], $matchingLocations)
            ->willReturn(false);

        $repository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['add', 'edit', 'delete'])
            ->getMock();
        $repository->expects($this->never())->method('add');
        $repository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function (ThothPublication $publication) {
                return $publication->getPublicationId() === 'matching-publication-id';
            }));
        $repository->expects($this->once())
            ->method('delete')
            ->with('other-publication-id');

        $service = new ThothPublicationService(new ThothPublicationFactory(), $repository, $locationService);
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

    public function testUpdateRejectsAmbiguousSameTypePublications()
    {
        $publicationFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods([
                'getId',
                'getPhysicalFormat',
                'getEntryKey',
                'getIdentificationCodes',
                'getData',
            ])
            ->getMock();
        $publicationFormat->method('getId')->willReturn(1);
        $publicationFormat->method('getPhysicalFormat')->willReturn(true);
        $publicationFormat->method('getEntryKey')->willReturn('XX');
        $publicationFormat->method('getIdentificationCodes')->willReturn($this->emptyIdentificationCodes());
        $publicationFormat->method('getData')->willReturn(null);
        $locationService = $this->createMock(ThothLocationService::class);
        $locationService->method('getDesiredByPublicationFormat')->willReturn([]);
        $repository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['add', 'edit', 'delete'])
            ->getMock();
        $repository->expects($this->never())->method('add');
        $repository->expects($this->never())->method('edit');
        $repository->expects($this->never())->method('delete');

        $this->expectException(MetadataSynchronizationException::class);

        $service = new ThothPublicationService(new ThothPublicationFactory(), $repository, $locationService);
        $service->update([$publicationFormat], 'work-id', [
            ['publicationId' => 'first-publication-id', 'publicationType' => PublicationType::PDF],
            ['publicationId' => 'second-publication-id', 'publicationType' => PublicationType::PDF],
        ]);
    }

    private function emptyIdentificationCodes()
    {
        return $this->identificationCodes([]);
    }

    private function identificationCodes(array $isbns)
    {
        $identificationCodes = array_map(function ($isbn) {
            $identificationCode = new IdentificationCode();
            $identificationCode->setCode('15');
            $identificationCode->setValue($isbn);
            return $identificationCode;
        }, $isbns);

        $result = $this->getMockBuilder(DAOResultFactory::class)
            ->setMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $result->method('toArray')->willReturn($identificationCodes);
        return $result;
    }

    public function testIsbnPublicationValidationFails()
    {
        $mockRepository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->getMock();

        $mockPubFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods(['getEntryKey', 'getIdentificationCodes', 'getData', 'getLocalizedName'])
            ->getMock();
        $mockPubFormat->method('getEntryKey')->willReturn('XX');
        $mockPubFormat->method('getIdentificationCodes')->willReturn(
            $this->identificationCodes(['978395796140'])
        );
        $mockPubFormat->method('getData')->willReturn(null);
        $mockPubFormat->expects($this->once())
            ->method('getLocalizedName')
            ->will($this->returnValue('PDF'));

        $service = new ThothPublicationService(
            new ThothPublicationFactory(),
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
        $mockRepository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('find')
            ->will($this->returnValue(new ThothPublication()));

        $mockPubFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods(['getEntryKey', 'getIdentificationCodes', 'getData', 'getLocalizedName'])
            ->getMock();
        $mockPubFormat->method('getEntryKey')->willReturn('XX');
        $mockPubFormat->method('getIdentificationCodes')->willReturn(
            $this->identificationCodes(['978-3-16-148410-0'])
        );
        $mockPubFormat->method('getData')->willReturn(null);
        $mockPubFormat->expects($this->once())
            ->method('getLocalizedName')
            ->will($this->returnValue('PDF'));

        $service = new ThothPublicationService(
            new ThothPublicationFactory(),
            $mockRepository,
            $this->createMock(ThothLocationService::class)
        );
        $errors = $service->validate($mockPubFormat);

        $this->assertEquals([
            '##plugins.generic.thoth.validation.isbnExists##',
        ], $errors);
    }
}
