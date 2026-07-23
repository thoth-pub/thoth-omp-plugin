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
 * @ingroup plugins_generic_thoth_tests
 * @see ThothLocationService
 *
 * @brief Test class for the ThothLocationService class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Enums\LocationPlatform;
use ThothApi\GraphQL\Inputs\PatchLocation as ThothLocation;

import('classes.press.Press');
import('classes.press.PressDAO');
import('classes.publication.Publication');
import('classes.publication.PublicationDAO');
import('classes.publicationFormat.PublicationFormat');
import('classes.submission.Submission');
import('classes.submission.SubmissionDAO');
import('lib.pkp.classes.core.Dispatcher');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.submission.SubmissionFile');
import('plugins.generic.thoth.classes.exceptions.MetadataSynchronizationException');
import('plugins.generic.thoth.classes.factories.ThothLocationFactory');
import('plugins.generic.thoth.classes.repositories.ThothLocationRepository');
import('plugins.generic.thoth.classes.services.ThothLocationService');

class ThothLocationServiceTest extends PKPTestCase
{
    protected function getMockedDAOs()
    {
        return ['SubmissionDAO', 'PublicationDAO', 'PressDAO'];
    }

    protected function getMockedRegistryKeys()
    {
        return ['request'];
    }

    public function testRegisterLocation()
    {
        $publicationFormat = $this->setUpLocationFactoryEnvironment();

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

        $thothPublicationId = '75ce9d60-1397-439c-90ad-80ee49630a70';
        $fileId = 1;

        $service = new ThothLocationService(new ThothLocationFactory(), $mockRepository);
        $thothLocationId = $service->register($publicationFormat, $thothPublicationId, $fileId);

        $this->assertSame('6f40cf3f-c7eb-437a-9c09-08a7f6923ec0', $thothLocationId);
    }

    public function testUpdateReconcilesLocationsAndDeletesRemoteDifferences()
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

    public function testUpdateReusesCanonicalLocationWhenUrlChanges()
    {
        $repository = $this->createMock(ThothLocationRepository::class);
        $repository->expects($this->never())->method('add');
        $repository->expects($this->once())
            ->method('edit')
            ->with($this->callback(function (ThothLocation $location) {
                return $location->getLocationId() === 'canonical-location-id'
                    && $location->getPublicationId() === 'publication-id'
                    && $location->getFullTextUrl() === 'https://publisher.example/new-book.pdf'
                    && $location->getCanonical() === true;
            }));
        $repository->expects($this->never())->method('delete');

        $service = new ThothLocationService(new ThothLocationFactory(), $repository);
        $service->update('publication-id', [
            new ThothLocation([
                'landingPage' => 'https://publisher.example/book',
                'fullTextUrl' => 'https://publisher.example/new-book.pdf',
                'locationPlatform' => LocationPlatform::OTHER,
            ]),
        ], [
            [
                'locationId' => 'canonical-location-id',
                'landingPage' => 'https://publisher.example/book',
                'fullTextUrl' => 'https://publisher.example/old-book.pdf',
                'locationPlatform' => LocationPlatform::OTHER,
                'canonical' => true,
            ],
        ]);
    }

    public function testGetDesiredLocationsCreatesOneLocationPerSubmissionFile()
    {
        $publicationFormat = $this->setUpLocationFactoryEnvironment();
        $firstFile = new SubmissionFile();
        $firstFile->setId(10);
        $secondFile = new SubmissionFile();
        $secondFile->setId(11);

        $service = new ThothLocationService(
            new ThothLocationFactory(),
            $this->createMock(ThothLocationRepository::class)
        );

        $this->assertEquals(
            [
                new ThothLocation([
                    'landingPage' => 'https://omp.example/press/catalog/book/12',
                    'fullTextUrl' => 'https://omp.example/press/catalog/view/12/1/10',
                    'locationPlatform' => LocationPlatform::OTHER,
                ]),
                new ThothLocation([
                    'landingPage' => 'https://omp.example/press/catalog/book/12',
                    'fullTextUrl' => 'https://omp.example/press/catalog/view/12/1/11',
                    'locationPlatform' => LocationPlatform::OTHER,
                ]),
            ],
            $service->getDesiredByPublicationFormat($publicationFormat, [$firstFile, $secondFile])
        );
    }

    public function testUpdateRejectsDuplicateRemoteLocations()
    {
        $repository = $this->createMock(ThothLocationRepository::class);
        $repository->expects($this->never())->method('add');
        $repository->expects($this->never())->method('edit');
        $repository->expects($this->never())->method('delete');
        $service = new ThothLocationService(new ThothLocationFactory(), $repository);

        $this->expectException(MetadataSynchronizationException::class);

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

    private function setUpLocationFactoryEnvironment()
    {
        $publication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData'])
            ->getMock();
        $publication->method('getData')->willReturnMap([
            ['submissionId', null, 12],
        ]);

        $submission = $this->getMockBuilder(Submission::class)
            ->setMethods(['getData', 'getBestId'])
            ->getMock();
        $submission->method('getData')->willReturnMap([
            ['contextId', null, 99],
        ]);
        $submission->method('getBestId')->willReturn(12);

        $context = $this->getMockBuilder(Press::class)
            ->setMethods(['getPath'])
            ->getMock();
        $context->method('getPath')->willReturn('press');

        $publicationDao = $this->getMockBuilder(PublicationDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $publicationDao->method('getById')->willReturn($publication);
        DAORegistry::registerDAO('PublicationDAO', $publicationDao);

        $submissionDao = $this->getMockBuilder(SubmissionDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $submissionDao->method('getById')->willReturn($submission);
        DAORegistry::registerDAO('SubmissionDAO', $submissionDao);

        $contextDao = $this->getMockBuilder(PressDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $contextDao->method('getById')->willReturn($context);
        DAORegistry::registerDAO('PressDAO', $contextDao);

        $request = $this->getMockBuilder(PKPRequest::class)
            ->setMethods(['getDispatcher'])
            ->getMock();
        $dispatcher = $this->getMockBuilder(Dispatcher::class)
            ->setMethods(['url'])
            ->getMock();
        $dispatcher->method('url')->willReturnCallback(function (
            $request,
            $route,
            $contextPath,
            $page,
            $operation,
            $path
        ) {
            if ($operation === 'book') {
                return 'https://omp.example/press/catalog/book/12';
            }

            return 'https://omp.example/press/catalog/view/' . implode('/', $path);
        });
        $request->method('getDispatcher')->willReturn($dispatcher);
        Registry::set('request', $request);

        $publicationFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods(['getData', 'getRemoteUrl', 'getBestId'])
            ->getMock();
        $publicationFormat->method('getData')->willReturnMap([
            ['publicationId', null, 99],
        ]);
        $publicationFormat->method('getRemoteUrl')->willReturn(null);
        $publicationFormat->method('getBestId')->willReturn(1);

        return $publicationFormat;
    }
}
