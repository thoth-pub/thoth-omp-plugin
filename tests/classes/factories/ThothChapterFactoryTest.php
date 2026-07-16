<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');
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

use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Enums\WorkType;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

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

    private function setUpMockEnvironment($emptyOptionalMetadata = false)
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
            ->will($this->returnValue($emptyOptionalMetadata ? '' : '10.12345/11112222'));
        $mockChapter->expects($this->any())
            ->method('getPages')
            ->will($this->returnValue($emptyOptionalMetadata ? '' : '31 - 50'));
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
            'workType' => WorkType::BOOK_CHAPTER,
            'workStatus' => WorkStatus::ACTIVE,
            'publicationDate' => '2024-01-01',
            'doi' => 'https://doi.org/10.12345/11112222',
            'pageInterval' => '31 - 50',
            'firstPage' => '31',
            'lastPage' => '50',
            'landingPage' => 'https://omp.publicknowledgeproject.org/index.php/press/catalog/book/17'
        ]), $thothChapter);
    }

    public function testGetWorkStatusByDatePublished()
    {
        $mockChapter = $this->getMockBuilder(Chapter::class)
            ->setMethods(['getDatePublished'])
            ->getMock();

        $mockChapter->expects($this->any())
            ->method('getDatePublished')
            ->will($this->onConsecutiveCalls('2024-01-01', '2050-01-01'));

        $factory = new ThothChapterFactory();
        $workStatus = $factory->getWorkStatusByDatePublished($mockChapter, null);
        $this->assertEquals(WorkStatus::ACTIVE, $workStatus);

        $workStatus = $factory->getWorkStatusByDatePublished($mockChapter, null);
        $this->assertEquals(WorkStatus::FORTHCOMING, $workStatus);
    }

    public function testCreateThothChapterOmitsEmptyOptionalMetadata()
    {
        $this->setUpMockEnvironment(true);

        $factory = new ThothChapterFactory();
        $data = $factory->createFromChapter($this->mocks['chapter'])->getAllData();

        foreach (['doi', 'pageInterval', 'firstPage', 'lastPage'] as $fieldName) {
            $this->assertArrayNotHasKey($fieldName, $data);
        }
    }
}
