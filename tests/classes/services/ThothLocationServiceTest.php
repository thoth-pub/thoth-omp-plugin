<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothLocationServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothLocationService
 *
 * @brief Test class for the ThothLocationService class
 */

use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Location as ThothLocation;

import('classes.core.Application');
import('classes.press.Press');
import('classes.submission.Submission');
import('classes.publication.Publication');
import('lib.pkp.classes.core.Dispatcher');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.services.ThothLocationService');

class ThothLocationServiceTest extends PKPTestCase
{
    private $clientFactoryBackup;
    private $configFactoryBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientFactoryBackup = ThothContainer::getInstance()->backup('client');
        $this->locationService = new ThothLocationService();
        $this->setUpMockEnvironment();
    }

    protected function tearDown(): void
    {
        unset($this->locationService);
        ThothContainer::getInstance()->set('client', $this->clientFactoryBackup);
        parent::tearDown();
    }

    protected function getMockedRegistryKeys()
    {
        return ['application', 'request'];
    }

    protected function getMockedDAOs()
    {
        return ['SubmissionDAO'];
    }

    private function setUpMockEnvironment()
    {
        $press = new Press();
        $press->setId(2);
        $press->setPrimaryLocale('en_US');
        $press->setPath('press');

        $mockApplication = $this->getMockBuilder(Application::class)
            ->setMethods(['getContextDepth', 'getContextList'])
            ->getMock();
        $mockApplication->expects($this->any())
            ->method('getContextDepth')
            ->will($this->returnValue(1));
        $mockApplication->expects($this->any())
            ->method('getContextList')
            ->will($this->returnValue(['firstContext']));
        Registry::set('application', $mockApplication);

        $mockDispatcher = $this->getMockBuilder(Dispatcher::class)
            ->setMethods(['url'])
            ->getMock();
        $mockDispatcher->expects($this->any())
            ->method('url')
            ->will($this->onConsecutiveCalls(
                'https://omp.publicknowledgeproject.org/press/catalog/book/23',
                'https://omp.publicknowledgeproject.org/press/catalog/view/23/5/17'
            ));

        $mockRequest = $this->getMockBuilder(PKPRequest::class)
            ->setMethods(['getContext', 'url'])
            ->getMock();
        $mockRequest->setDispatcher($mockDispatcher);
        $mockRequest->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($press));
        Registry::set('request', $mockRequest);

        $submissionDaoMock = $this->getMockBuilder(SubmissionDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $submission = new Submission();
        $submission->setId(23);
        $submissionDaoMock->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($submission));
        DAORegistry::registerDAO('SubmissionDAO', $submissionDaoMock);

        $publicationDaoMock = $this->getMockBuilder(PublicationDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $publication = new Publication();
        $publication->setId(23);
        $publicationDaoMock->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($publication));
        DAORegistry::registerDAO('PublicationDAO', $publicationDaoMock);
    }

    public function testCreateNewLocationByPublicationFormat()
    {
        $expectedLocation = new ThothLocation();
        $expectedLocation->setLandingPage('https://omp.publicknowledgeproject.org/press/catalog/book/23');
        $expectedLocation->setFullTextUrl('https://omp.publicknowledgeproject.org/press/catalog/view/23/5/17');
        $expectedLocation->setLocationPlatform(ThothLocation::LOCATION_PLATFORM_OTHER);

        $publicationFormat = DAORegistry::getDAO('PublicationFormatDAO')->newDataObject();
        $publicationFormat->setId(5);

        $location = $this->locationService->newByPublicationFormat($publicationFormat, 17);

        $this->assertEquals($expectedLocation, $location);
    }

    public function testCreateNewLocation()
    {
        $expectedLocation = new ThothLocation();
        $expectedLocation->setLandingPage('https://omp.publicknowledgeproject.org/press/catalog/book/12');
        $expectedLocation->setFullTextUrl('https://www.bookstore.com/site/books/book34');
        $expectedLocation->setLocationPlatform(ThothLocation::LOCATION_PLATFORM_OTHER);

        $location = $this->locationService->new([
            'landingPage' => 'https://omp.publicknowledgeproject.org/press/catalog/book/12',
            'fullTextUrl' => 'https://www.bookstore.com/site/books/book34',
            'locationPlatform' => ThothLocation::LOCATION_PLATFORM_OTHER,
        ]);

        $this->assertEquals($expectedLocation, $location);
    }

    public function testRegisterLocation()
    {
        $thothPublicationId = '8ac3e585-c32a-42d7-bd36-ef42ee397e6e';

        $expectedLocation = new ThothLocation();
        $expectedLocation->setLocationId('03b0367d-bba3-4e26-846a-4c36d3920db2');
        $expectedLocation->setPublicationId($thothPublicationId);
        $expectedLocation->setLandingPage('https://omp.publicknowledgeproject.org/press/catalog/book/23');
        $expectedLocation->setFullTextUrl('https://www.bookstore.com/site/books/book5');
        $expectedLocation->setLocationPlatform(ThothLocation::LOCATION_PLATFORM_OTHER);
        $expectedLocation->setCanonical(true);

        $publicationFormat = DAORegistry::getDAO('PublicationFormatDAO')->newDataObject();
        $publicationFormat->setId(41);
        $publicationFormat->setRemoteUrl('https://www.bookstore.com/site/books/book5');

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods([
                'createLocation',
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createLocation')
            ->will($this->returnValue('03b0367d-bba3-4e26-846a-4c36d3920db2'));

        ThothContainer::getInstance()->set('client', function () use ($mockThothClient) {
            return $mockThothClient;
        });

        $location = $this->locationService->register($publicationFormat, $thothPublicationId);
        $this->assertEquals($expectedLocation, $location);
    }
}
