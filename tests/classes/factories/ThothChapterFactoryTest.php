<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothChapterFactoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothChapterFactoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothChapterFactory
 *
 * @brief Test class for the ThothChapterFactory class
 */

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\publication\Repository as PublicationRepository;
use APP\submission\Repository as SubmissionRepository;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Models\Work as ThothWork;

import('plugins.generic.thoth.classes.factories.ThothChapterFactory');

class ThothChapterFactoryTest extends PKPTestCase
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

    private function setUpMockEnvironment()
    {
        $publicationRepoMock = Mockery::mock(app(PublicationRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn(
                Mockery::mock(\APP\publication\Publication::class)
                    ->shouldReceive('getData')
                    ->with('submissionId')
                    ->andReturn(17)
                    ->getMock()
            )
            ->getMock();
        app()->instance(PublicationRepository::class, $publicationRepoMock);

        $submissionRepoMock = Mockery::mock(app(SubmissionRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn(
                Mockery::mock(\APP\submission\Submission::class)
                    ->shouldReceive('getData')
                    ->with('contextId')
                    ->andReturn(99)
                    ->shouldReceive('getBestId')
                    ->withAnyArgs()
                    ->andReturn(17)
                    ->getMock()
            )
            ->getMock();
        app()->instance(SubmissionRepository::class, $submissionRepoMock);

        $mockContext = $this->getMockBuilder(\APP\press\Press::class)
            ->setMethods(['getPath'])
            ->getMock();
        $mockContext->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue('press'));

        $mockContextDao = $this->getMockBuilder(\APP\press\PressDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $mockContextDao->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($mockContext));
        DAORegistry::registerDAO('PressDAO', $mockContextDao);

        $mockDispatcher = $this->getMockBuilder(\PKP\core\Dispatcher::class)
            ->setMethods(['url'])
            ->getMock();
        $mockDispatcher->expects($this->once())
            ->method('url')
            ->will($this->returnValue('https://omp.publicknowledgeproject.org/index.php/press/catalog/book/17'));

        $mockRequest = $this->getMockBuilder(\PKP\core\PKPRequest::class)
            ->setMethods(['getDispatcher'])
            ->getMock();
        $mockRequest->expects($this->any())
            ->method('getDispatcher')
            ->will($this->returnValue($mockDispatcher));
        Registry::set('request', $mockRequest);

        $mockChapter = Mockery::mock(\APP\monograph\Chapter::class)
            ->shouldReceive('getData')
            ->with('publicationId')
            ->andReturn(17)
            ->shouldReceive('getDatePublished')
            ->with()
            ->andReturn('2024-01-01')
            ->shouldReceive('getLocalizedFullTitle')
            ->withAnyArgs()
            ->andReturn('My chapter title: My chapter subtitle')
            ->shouldReceive('getLocalizedTitle')
            ->withAnyArgs()
            ->andReturn('My chapter title')
            ->shouldReceive('getLocalizedData')
            ->with('subtitle')
            ->andReturn('My chapter subtitle')
            ->shouldReceive('getLocalizedData')
            ->with('abstract')
            ->andReturn('This is my chapter abstract')
            ->shouldReceive('getData')
            ->with('doiObject')
            ->andReturn(
                Mockery::mock(\PKP\doi\Doi::class)
                    ->makePartial()
                    ->shouldReceive('getResolvingUrl')
                    ->withAnyArgs()
                    ->andReturn('https://doi.org/10.12345/11112222')
                    ->getMock()
            )
            ->shouldReceive('getPages')
            ->withAnyArgs()
            ->andReturn('31')
            ->getMock();

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
