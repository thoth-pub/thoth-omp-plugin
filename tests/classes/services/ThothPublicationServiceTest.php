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
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothPublicationService
 *
 * @brief Test class for the ThothPublicationService class
 */

use APP\publicationFormat\IdentificationCode;
use PKP\submissionFile\SubmissionFile;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\LocationPlatform;
use ThothApi\GraphQL\Enums\PublicationType;
use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Inputs\PatchLocation as ThothLocation;
use ThothApi\GraphQL\Inputs\PatchPublication as ThothPublication;

import('plugins.generic.thoth.classes.container.ThothContainer');
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
            ->setMethods([
                'getEntryKey',
                'getLocalizedName',
                'getRemoteUrl',
                'getIdentificationCodes',
                'getData',
                'setData',
            ])
            ->getMock();
        $mockPubFormat->method('getEntryKey')->willReturn('DA');
        $mockPubFormat->method('getLocalizedName')->willReturn('PDF');
        $mockPubFormat->method('getRemoteUrl')->willReturn(null);
        $mockPubFormat->method('getIdentificationCodes')->willReturn($this->identificationCodes([]));
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

    public function testUpdateReconcilesPublicationsAndLocationsAndDeletesRemainingRemotePublications()
    {
        $pdfFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods([
                'getId',
                'getPhysicalFormat',
                'getEntryKey',
                'getLocalizedName',
                'getRemoteUrl',
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
                'getRemoteUrl',
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

        $factory = new ThothPublicationFactory();

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

        $service = new ThothPublicationService($factory, $repository, $locationService);
        $service->update(
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
    }

    public function testUpdateSkipsRemainingPublicationDeletionsForActiveWorks()
    {
        $repository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['delete'])
            ->getMock();
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
                'publicationType' => PublicationType::MOBI,
            ]],
            [],
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
                'getLocalizedName',
                'getRemoteUrl',
                'getIdentificationCodes',
                'getData',
                'setData',
            ])
            ->getMock();
        $publicationFormat->method('getId')->willReturn(1);
        $publicationFormat->method('getPhysicalFormat')->willReturn(true);
        $publicationFormat->method('getEntryKey')->willReturn('DA');
        $publicationFormat->method('getLocalizedName')->willReturn('PDF');
        $publicationFormat->method('getRemoteUrl')->willReturn(null);
        $publicationFormat->method('getIdentificationCodes')->willReturn($this->identificationCodes([
            $this->identificationCode('978-3-16-148410-0'),
        ]));
        $publicationFormat->method('getData')->willReturn(null);
        $publicationFormat->expects($this->once())
            ->method('setData')
            ->with('thothPublicationId', 'matching-publication-id');

        $factory = new ThothPublicationFactory();

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

    public function testUpdateMatchesSameTypePublicationByLocationsWhenIsbnIsMissing()
    {
        $publicationFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods([
                'getId',
                'getPhysicalFormat',
                'getEntryKey',
                'getLocalizedName',
                'getRemoteUrl',
                'getIdentificationCodes',
                'getData',
                'setData',
            ])
            ->getMock();
        $publicationFormat->method('getId')->willReturn(1);
        $publicationFormat->method('getPhysicalFormat')->willReturn(true);
        $publicationFormat->method('getEntryKey')->willReturn('DA');
        $publicationFormat->method('getLocalizedName')->willReturn('PDF');
        $publicationFormat->method('getRemoteUrl')->willReturn(null);
        $publicationFormat->method('getIdentificationCodes')->willReturn($this->identificationCodes([]));
        $publicationFormat->method('getData')->willReturn(null);
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

    public function testUpdateRejectsAmbiguousSameTypePublications()
    {
        $publicationFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods([
                'getId',
                'getPhysicalFormat',
                'getEntryKey',
                'getLocalizedName',
                'getRemoteUrl',
                'getIdentificationCodes',
                'getData',
            ])
            ->getMock();
        $publicationFormat->method('getId')->willReturn(1);
        $publicationFormat->method('getPhysicalFormat')->willReturn(true);
        $publicationFormat->method('getEntryKey')->willReturn('DA');
        $publicationFormat->method('getLocalizedName')->willReturn('PDF');
        $publicationFormat->method('getRemoteUrl')->willReturn(null);
        $publicationFormat->method('getIdentificationCodes')->willReturn($this->identificationCodes([]));
        $publicationFormat->method('getData')->willReturn(null);

        $factory = new ThothPublicationFactory();
        $locationService = $this->createMock(ThothLocationService::class);
        $locationService->method('getDesiredByPublicationFormat')->willReturn([]);
        $repository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['add', 'edit', 'delete'])
            ->getMock();
        $repository->expects($this->never())->method('add');
        $repository->expects($this->never())->method('edit');
        $repository->expects($this->never())->method('delete');

        $this->expectException(UnexpectedValueException::class);

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

    private function identificationCode(string $value): IdentificationCode
    {
        $identificationCode = new IdentificationCode();
        $identificationCode->setCode('15');
        $identificationCode->setValue($value);
        return $identificationCode;
    }

    public function testIsbnPublicationValidationFails()
    {
        $mockRepository = $this->getMockBuilder(ThothPublicationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->getMock();

        $mockPubFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods([
                'getEntryKey',
                'getLocalizedName',
                'getRemoteUrl',
                'getIdentificationCodes',
                'getData',
            ])
            ->getMock();
        $mockPubFormat->method('getEntryKey')->willReturn('DA');
        $mockPubFormat->method('getLocalizedName')->willReturn('PDF');
        $mockPubFormat->method('getRemoteUrl')->willReturn(null);
        $mockPubFormat->method('getIdentificationCodes')->willReturn($this->identificationCodes([
            $this->identificationCode('978395796140'),
        ]));
        $mockPubFormat->method('getData')->willReturn(null);

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
            ->setMethods([
                'getEntryKey',
                'getLocalizedName',
                'getRemoteUrl',
                'getIdentificationCodes',
                'getData',
            ])
            ->getMock();
        $mockPubFormat->method('getEntryKey')->willReturn('DA');
        $mockPubFormat->method('getLocalizedName')->willReturn('PDF');
        $mockPubFormat->method('getRemoteUrl')->willReturn(null);
        $mockPubFormat->method('getIdentificationCodes')->willReturn($this->identificationCodes([
            $this->identificationCode('978-3-16-148410-0'),
        ]));
        $mockPubFormat->method('getData')->willReturn(null);

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
