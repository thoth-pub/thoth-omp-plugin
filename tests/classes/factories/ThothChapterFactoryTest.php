<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothChapterFactoryTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
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

namespace APP\plugins\generic\thoth\tests\classes\factories;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\monograph\Chapter;
use APP\plugins\generic\thoth\classes\factories\ThothChapterFactory;
use APP\publication\Repository as PublicationRepository;
use APP\submission\Repository as SubmissionRepository;
use Mockery;
use PKP\core\Registry;
use PKP\db\DAORegistry;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Models\Work as ThothWork;

class ThothChapterFactoryTest extends PKPTestCase
{
    protected array $mocks = [];
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
            ->onlyMethods(['getPath'])
            ->getMock();
        $mockContext->expects($this->any())
            ->method('getPath')
            ->willReturn('press');

        $mockContextDao = $this->getMockBuilder(\APP\press\PressDAO::class)
            ->onlyMethods(['getById'])
            ->getMock();
        $mockContextDao->expects($this->any())
            ->method('getById')
            ->willReturn($mockContext);
        DAORegistry::registerDAO('PressDAO', $mockContextDao);

        $mockDispatcher = $this->getMockBuilder(\PKP\core\Dispatcher::class)
            ->onlyMethods(['url'])
            ->getMock();
        $mockDispatcher->expects($this->once())
            ->method('url')
            ->willReturn('https://omp.publicknowledgeproject.org/index.php/press/catalog/book/17');

        $mockRequest = $this->getMockBuilder(\APP\core\Request::class)
            ->onlyMethods(['getDispatcher'])
            ->getMock();
        $mockRequest->expects($this->any())
            ->method('getDispatcher')
            ->willReturn($mockDispatcher);
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
            ->andReturn('31 - 50')
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
            'firstPage' => '31',
            'lastPage' => '50',
            'pageInterval' => '31 - 50',
            'landingPage' => 'https://omp.publicknowledgeproject.org/index.php/press/catalog/book/17'
        ]), $thothChapter);
    }

    public function testGetWorkStatusByDatePublished()
    {
        $mockChapter = $this->getMockBuilder(Chapter::class)
            ->onlyMethods(['getDatePublished'])
            ->getMock();

        $mockChapter->expects($this->any())
            ->method('getDatePublished')
            ->willReturnOnConsecutiveCalls('2024-01-01', '2050-01-01');

        $factory = new ThothChapterFactory();
        $workStatus = $factory->getWorkStatusByDatePublished($mockChapter, null);
        $this->assertEquals(ThothWork::WORK_STATUS_ACTIVE, $workStatus);

        $workStatus = $factory->getWorkStatusByDatePublished($mockChapter, null);
        $this->assertEquals(ThothWork::WORK_STATUS_FORTHCOMING, $workStatus);
    }
}
