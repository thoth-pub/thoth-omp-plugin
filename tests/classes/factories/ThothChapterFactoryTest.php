<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothChapterFactoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothChapterFactoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothChapterFactory
 *
 * @brief Test class for the ThothChapterFactory class
 */

use ThothApi\GraphQL\Models\Work as ThothWork;

import('classes.monograph.Chapter');
import('classes.press.Press');
import('classes.press.PressDAO');
import('classes.publication.Publication');
import('classes.publication.PublicationDAO');
import('classes.submission.Submission');
import('classes.submission.SubmissionDAO');
import('lib.pkp.classes.core.Dispatcher');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothChapterFactory');

class ThothChapterFactoryTest extends PKPTestCase
{
    protected function getMockedDAOs()
    {
        return ['PublicationDAO', 'SubmissionDAO', 'PressDAO'];
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
                ['submissionId', null, 17],
            ]));

        $mockPublicationDao = $this->getMockBuilder(PublicationDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $mockPublicationDao->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($mockPublication));
        DAORegistry::registerDAO('PublicationDAO', $mockPublicationDao);

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
            ->will($this->returnValue(17));

        $mockSubmissionDao = $this->getMockBuilder(SubmissionDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $mockSubmissionDao->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($mockSubmission));
        DAORegistry::registerDAO('SubmissionDAO', $mockSubmissionDao);

        $mockContext = $this->getMockBuilder(Press::class)
            ->setMethods(['getPath'])
            ->getMock();
        $mockContext->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue('press'));

        $mockContextDao = $this->getMockBuilder(PressDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $mockContextDao->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($mockContext));
        DAORegistry::registerDAO('PressDAO', $mockContextDao);

        $mockDispatcher = $this->getMockBuilder(Dispatcher::class)
            ->setMethods(['url'])
            ->getMock();
        $mockDispatcher->expects($this->once())
            ->method('url')
            ->will($this->returnValue('https://omp.publicknowledgeproject.org/index.php/press/catalog/book/17'));

        $mockRequest = $this->getMockBuilder(PKPRequest::class)
            ->setMethods(['getDispatcher'])
            ->getMock();
        $mockRequest->expects($this->any())
            ->method('getDispatcher')
            ->will($this->returnValue($mockDispatcher));
        Registry::set('request', $mockRequest);

        $mockChapter = $this->getMockBuilder(Chapter::class)
            ->setMethods([
                'getLocalizedFullTitle',
                'getLocalizedTitle',
                'getLocalizedData',
                'getStoredPubId',
                'getPages',
                'getDatePublished',
            ])
            ->getMock();
        $mockChapter->expects($this->any())
            ->method('getLocalizedFullTitle')
            ->will($this->returnValue('My chapter title: My chapter subtitle'));
        $mockChapter->expects($this->any())
            ->method('getLocalizedTitle')
            ->will($this->returnValue('My chapter title'));
        $mockChapter->expects($this->any())
            ->method('getLocalizedData')
            ->will($this->returnValueMap([
                ['subtitle', null, 'My chapter subtitle'],
                ['abstract', null, 'This is my chapter abstract']
            ]));
        $mockChapter->expects($this->any())
            ->method('getStoredPubId')
            ->will($this->returnValue('10.12345/11112222'));
        $mockChapter->expects($this->any())
            ->method('getPages')
            ->will($this->returnValue('31'));
        $mockChapter->expects($this->any())
            ->method('getDatePublished')
            ->will($this->returnValue('2024-01-01'));

        $this->mocks = [];
        $this->mocks['chapter'] = $mockChapter;
    }

    public function testCreateThothChapterFromChapter()
    {
        $this->setUpMockEnvironment();
        $mockChapter = $this->mocks['chapter'];

        $factory = new ThothChapterFactory();
        $thothChapter = $factory->createFromChapter($mockChapter);

        $this->assertEquals(new ThothWork([
            'workType' => ThothWork::WORK_TYPE_BOOK_CHAPTER,
            'workStatus' => ThothWork::WORK_STATUS_ACTIVE,
            'fullTitle' => 'My chapter title: My chapter subtitle',
            'title' => 'My chapter title',
            'subtitle' => 'My chapter subtitle',
            'longAbstract' => 'This is my chapter abstract',
            'publicationDate' => '2024-01-01',
            'doi' => 'https://doi.org/10.12345/11112222',
            'pageCount' => 31,
            'landingPage' => 'https://omp.publicknowledgeproject.org/index.php/press/catalog/book/17'
        ]), $thothChapter);
    }
}
