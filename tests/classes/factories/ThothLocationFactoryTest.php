<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothLocationFactoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationFactoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothLocationFactory
 *
 * @brief Test class for the ThothLocationFactory class
 */

use ThothApi\GraphQL\Models\Location as ThothLocation;

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
import('plugins.generic.thoth.classes.factories.ThothLocationFactory');

class ThothLocationFactoryTest extends PKPTestCase
{
    protected function getMockedDAOs()
    {
        return ['SubmissionDAO', 'PublicationDAO', 'PressDAO'];
    }

    protected function getMockedRegistryKeys()
    {
        return ['request'];
    }

    private function setUpMockEnvironment()
    {
        $mockPublication = $this->getMockBuilder(Publication::class)
            ->setMethods(['getData'])
            ->getMock();
        $mockPublication->expects($this->any())
            ->method('getData')
            ->will($this->returnValueMap([
                ['submissionId', null, 12],
            ]));

        $mockSubmission = $this->getMockBuilder(Submission::class)
            ->setMethods(['getData', 'getBestId'])
            ->getMock();
        $mockSubmission->expects($this->any())
            ->method('getData')
            ->will($this->returnValueMap([
                ['contextId', null, 99],
            ]));
        $mockSubmission->expects($this->any())
            ->method('getBestId')
            ->will($this->returnValue(12));

        $mockContext = $this->getMockBuilder(Press::class)
            ->setMethods(['getData', 'getPath'])
            ->getMock();
        $mockContext->expects($this->any())
            ->method('getData')
            ->will($this->returnValueMap([
                ['contextId', null, 99],
            ]));
        $mockContext->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue('press'));

        $mockPublicationDao = $this->getMockBuilder(PublicationDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $mockPublicationDao->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($mockPublication));
        DAORegistry::registerDAO('PublicationDAO', $mockPublicationDao);

        $mockSubmissionDao = $this->getMockBuilder(SubmissionDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $mockSubmissionDao->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($mockSubmission));
        DAORegistry::registerDAO('SubmissionDAO', $mockSubmissionDao);

        $mockContextDao = $this->getMockBuilder(PressDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $mockContextDao->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($mockContext));
        DAORegistry::registerDAO('PressDAO', $mockContextDao);

        $mockRequest = $this->getMockBuilder(PKPRequest::class)
            ->setMethods(['getDispatcher'])
            ->getMock();

        $mockDispatcher = $this->getMockBuilder(Dispatcher::class)
            ->setMethods(['url'])
            ->getMock();
        $mockDispatcher->expects($this->any())
            ->method('url')
            ->will($this->returnValueMap([
                [
                    $mockRequest,
                    ROUTE_PAGE,
                    'press',
                    'catalog',
                    'book',
                    [12],
                    null,
                    null,
                    false,
                    'https://omp.publicknowledgeproject.org/press/catalog/book/12'
                ],
                [
                    $mockRequest,
                    ROUTE_PAGE,
                    'press',
                    'catalog',
                    'view',
                    [12, 1, 1],
                    null,
                    null,
                    false,
                    'https://omp.publicknowledgeproject.org/press/catalog/view/12/1/1'
                ]
            ]));

        $mockRequest->expects($this->any())
            ->method('getDispatcher')
            ->will($this->returnValue($mockDispatcher));
        Registry::set('request', $mockRequest);

        $mockPubFormat = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods(['getData', 'getRemoteUrl', 'getBestId'])
            ->getMock();
        $mockPubFormat->expects($this->any())
            ->method('getData')
            ->will($this->returnValueMap([
                ['publicationId', null, 99],
            ]));
        $mockPubFormat->expects($this->any())
            ->method('getRemoteUrl')
            ->will($this->returnValue(null));
        $mockPubFormat->expects($this->any())
            ->method('getBestId')
            ->will($this->returnValue(1));

        $this->mocks = [];
        $this->mocks['publicationFormat'] = $mockPubFormat;
    }

    public function testCreateThothLocationFromPublicationFormat()
    {
        $this->setUpMockEnvironment();

        $mockPubFormat = $this->mocks['publicationFormat'];
        $fileId = 1;

        $factory = new ThothLocationFactory();
        $thothLocation = $factory->createFromPublicationFormat($mockPubFormat, $fileId);

        $this->assertEquals(new ThothLocation([
            'landingPage' => 'https://omp.publicknowledgeproject.org/press/catalog/book/12',
            'fullTextUrl' => 'https://omp.publicknowledgeproject.org/press/catalog/view/12/1/1',
            'locationPlatform' => ThothLocation::LOCATION_PLATFORM_OTHER
        ]), $thothLocation);
    }
}
