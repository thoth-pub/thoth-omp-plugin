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

import('classes.core.Application');
import('classes.press.Press');
import('classes.submission.Submission');
import('lib.pkp.classes.core.Dispatcher');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.services.ThothLocationService');

class ThothLocationServiceTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->locationService = new ThothLocationService();
        $this->setUpMockEnvironment();
    }

    protected function tearDown(): void
    {
        unset($this->locationService);
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
            ->will($this->onConsecutiveCalls('https://omp.publicknowledgeproject.org/press/catalog/book/23', 'https://omp.publicknowledgeproject.org/press/catalog/view/23/5/17'));

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

    public function testGetLocationPropsByPublicationFormat()
    {
        $publicationFormat = DAORegistry::getDAO('PublicationFormatDAO')->newDataObject();
        $publicationFormat->setId(5);

        $locationProps = $this->locationService->getPropertiesByPublicationFormat($publicationFormat, 17);

        $this->assertEquals([
            'landingPage' => 'https://omp.publicknowledgeproject.org/press/catalog/book/23',
            'fullTextUrl' => 'https://omp.publicknowledgeproject.org/press/catalog/view/23/5/17',
            'locationPlatform' => ThothLocation::LOCATION_PLATFORM_OTHER
        ], $locationProps);
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
}
