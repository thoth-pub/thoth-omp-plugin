<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
/**
 * @file plugins/generic/thoth/tests/classes/services/ThothLocationServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothLocationService
 *
 * @brief Test class for the ThothLocationService class
 */

use APP\publication\Repository as PublicationRepository;
use APP\submission\Repository as SubmissionRepository;
use PKP\core\Registry;
use PKP\db\DAORegistry;
use PKP\submissionFile\SubmissionFile;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\LocationPlatform;
use ThothApi\GraphQL\Inputs\PatchLocation as ThothLocation;

import('plugins.generic.thoth.classes.repositories.ThothLocationRepository');
import('plugins.generic.thoth.classes.factories.ThothLocationFactory');
import('plugins.generic.thoth.classes.services.ThothLocationService');

class ThothLocationServiceTest extends PKPTestCase
{
    protected function getMockedContainerKeys(): array
    {
        return [...parent::getMockedContainerKeys(), SubmissionRepository::class, PublicationRepository::class];
    }

    protected function getMockedDAOs(): array
    {
        return ['PressDAO'];
    }

    protected function getMockedRegistryKeys(): array
    {
        return ['request'];
    }

    public function testRegisterLocation()
    {
        $mockFactory = $this->getMockBuilder(ThothLocationFactory::class)
            ->setMethods(['createFromPublicationFormat'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromPublicationFormat')
            ->will($this->returnValue(new ThothLocation()));

        $mockRepository = $this->getMockBuilder(ThothLocationRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['hasCanonical', 'add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('hasCanonical')
            ->will($this->returnValue(true));
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('6f40cf3f-c7eb-437a-9c09-08a7f6923ec0'));

        $mockPubFormat = $this->getMockBuilder(PublicationFormat::class)->getMock();

        $thothPublicationId = '75ce9d60-1397-439c-90ad-80ee49630a70';
        $fileId = 1;

        $service = new ThothLocationService($mockFactory, $mockRepository);
        $thothLocationId = $service->register($mockPubFormat, $thothPublicationId, $fileId);

        $this->assertSame('6f40cf3f-c7eb-437a-9c09-08a7f6923ec0', $thothLocationId);
    }

    public function testUpdateReconcilesLocationsAndDeletesRemainingRemoteLocations()
    {
        $repository = $this->createMock(ThothLocationRepository::class);
        $repository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function (ThothLocation $location) {
                return $location->getLocationId() === 'existing-location-id'
                    && $location->getPublicationId() === 'publication-id'
                    && $location->getLandingPage() === 'https://publisher.example/book'
                    && $location->getFullTextUrl() === 'https://publisher.example/book.pdf'
                    && $location->getCanonical() === true;
            }));
        $repository->expects($this->once())
            ->method('add')
            ->with($this->callback(function (ThothLocation $location) {
                return $location->getPublicationId() === 'publication-id'
                    && $location->getFullTextUrl() === 'https://publisher.example/book.epub'
                    && $location->getCanonical() === false;
            }));
        $repository->expects($this->once())
            ->method('delete')
            ->with('removed-location-id');

        $service = new ThothLocationService(new ThothLocationFactory(), $repository);
        $service->update('publication-id', [
            new ThothLocation([
                'landingPage' => 'https://publisher.example/book',
                'fullTextUrl' => 'https://publisher.example/book.pdf',
                'locationPlatform' => LocationPlatform::OTHER,
            ]),
            new ThothLocation([
                'landingPage' => 'https://publisher.example/book',
                'fullTextUrl' => 'https://publisher.example/book.epub',
                'locationPlatform' => LocationPlatform::OTHER,
            ]),
        ], [
            [
                'locationId' => 'existing-location-id',
                'landingPage' => 'https://old.example/book',
                'fullTextUrl' => 'https://publisher.example/book.pdf',
                'locationPlatform' => LocationPlatform::OTHER,
                'canonical' => true,
            ],
            [
                'locationId' => 'removed-location-id',
                'landingPage' => 'https://publisher.example/book',
                'fullTextUrl' => 'https://publisher.example/removed.pdf',
                'locationPlatform' => LocationPlatform::OTHER,
                'canonical' => false,
            ],
        ]);
    }

    public function testGetDesiredLocationsCreatesOneLocationPerSubmissionFile()
    {
        $publicationFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods(['getData', 'getRemoteUrl', 'getBestId'])
            ->getMock();
        $publicationFormat->method('getData')->willReturnCallback(function ($key) {
            return $key === 'publicationId' ? 99 : null;
        });
        $publicationFormat->method('getRemoteUrl')->willReturn(null);
        $publicationFormat->method('getBestId')->willReturn(1);
        $this->setUpLocationFactoryEnvironment();

        $firstFile = new SubmissionFile();
        $firstFile->setId(10);
        $secondFile = new SubmissionFile();
        $secondFile->setId(11);

        $service = new ThothLocationService(
            new ThothLocationFactory(),
            $this->createMock(ThothLocationRepository::class)
        );
        $locations = $service->getDesiredByPublicationFormat($publicationFormat, [$firstFile, $secondFile]);

        $this->assertCount(2, $locations);
        $this->assertSame('https://example.com/catalog/view/12/1/10', $locations[0]->getFullTextUrl());
        $this->assertSame('https://example.com/catalog/view/12/1/11', $locations[1]->getFullTextUrl());
    }

    public function testUpdateRejectsDuplicateRemoteLocations()
    {
        $repository = $this->createMock(ThothLocationRepository::class);
        $repository->expects($this->never())->method('add');
        $repository->expects($this->never())->method('edit');
        $repository->expects($this->never())->method('delete');
        $service = new ThothLocationService(new ThothLocationFactory(), $repository);

        $this->expectException(UnexpectedValueException::class);

        $service->update('publication-id', [
            new ThothLocation([
                'fullTextUrl' => 'https://publisher.example/book.pdf',
                'locationPlatform' => LocationPlatform::OTHER,
            ]),
        ], [
            [
                'locationId' => 'first-location-id',
                'fullTextUrl' => 'https://publisher.example/book.pdf',
                'locationPlatform' => LocationPlatform::OTHER,
            ],
            [
                'locationId' => 'second-location-id',
                'fullTextUrl' => 'https://publisher.example/book.pdf',
                'locationPlatform' => LocationPlatform::OTHER,
            ],
        ]);
    }

    private function setUpLocationFactoryEnvironment(): void
    {
        $publicationRepository = Mockery::mock(app(PublicationRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->with(99)
            ->andReturn(
                Mockery::mock(\APP\publication\Publication::class)
                    ->shouldReceive('getData')
                    ->with('submissionId')
                    ->andReturn(12)
                    ->getMock()
            )
            ->getMock();
        app()->instance(PublicationRepository::class, $publicationRepository);

        $submissionRepository = Mockery::mock(app(SubmissionRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->with(12)
            ->andReturn(
                Mockery::mock(\APP\submission\Submission::class)
                    ->shouldReceive('getData')
                    ->with('contextId')
                    ->andReturn(99)
                    ->shouldReceive('getBestId')
                    ->andReturn(12)
                    ->getMock()
            )
            ->getMock();
        app()->instance(SubmissionRepository::class, $submissionRepository);

        $context = $this->createMock(\APP\press\Press::class);
        $context->method('getPath')->willReturn('press');
        $contextDao = $this->createMock(\APP\press\PressDAO::class);
        $contextDao->method('getById')->with(99)->willReturn($context);
        DAORegistry::registerDAO('PressDAO', $contextDao);

        $request = $this->getMockBuilder(\PKP\core\PKPRequest::class)
            ->setMethods(['getDispatcher'])
            ->getMock();
        $dispatcher = $this->createMock(\PKP\core\Dispatcher::class);
        $dispatcher->method('url')->willReturnCallback(function (...$arguments): string {
            $operation = $arguments[4];
            $path = $arguments[5];
            return $operation === 'book'
                ? 'https://example.com/catalog/book/12'
                : 'https://example.com/catalog/view/' . implode('/', $path);
        });
        $request->method('getDispatcher')->willReturn($dispatcher);
        Registry::set('request', $request);
    }
}
