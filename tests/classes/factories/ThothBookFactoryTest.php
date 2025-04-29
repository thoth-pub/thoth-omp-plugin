<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothBookFactoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookFactoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothBookFactory
 *
 * @brief Test class for the ThothBookFactory class
 */

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\submission\Repository as SubmissionRepository;
use Mockery;
use PKP\db\DAORegistry;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Models\Work as ThothWork;

import('plugins.generic.thoth.classes.factories.ThothBookFactory');

class ThothBookFactoryTest extends PKPTestCase
{
    protected function getMockedContainerKeys(): array
    {
        return [...parent::getMockedContainerKeys(), SubmissionRepository::class];
    }

    protected function getMockedDAOs(): array
    {
        return ['PressDAO', 'PublicationFormatDAO'];
    }

    protected function getMockedRegistryKeys(): array
    {
        return ['request'];
    }

    private function setUpMockEnvironment()
    {
        $submissionRepoMock = Mockery::mock(app(SubmissionRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn(
                Mockery::mock(\APP\submission\Submission::class)
                    ->shouldReceive('getData')
                    ->with('workType')
                    ->andReturn(\APP\submission\Submission::WORK_TYPE_AUTHORED_WORK)
                    ->shouldReceive('getData')
                    ->with('contextId')
                    ->andReturn(99)
                    ->shouldReceive('getBestId')
                    ->withAnyArgs()
                    ->andReturn(3)
                    ->getMock()
            )
            ->getMock();
        app()->instance(SubmissionRepository::class, $submissionRepoMock);

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

        $mockRequest = Mockery::mock(\PKP\core\PKPRequest::class)
            ->shouldReceive('getDispatcher')
            ->withAnyArgs()
            ->andReturn(
                Mockery::mock(\PKP\core\Dispatcher::class)
                    ->shouldReceive('url')
                    ->withAnyArgs()
                    ->andReturn('https://omp.publicknowledgeproject.org/index.php/press/catalog/book/3')
                    ->getMock()
            )
            ->shouldReceive('getUserVar')
            ->with('thothWorkType')
            ->andReturn(null)
            ->getMock();
        Registry::set('request', $mockRequest);

        $mockPublication = Mockery::mock(\APP\publication\Publication::class)
            ->shouldReceive('getData')
            ->with('submissionId')
            ->andReturn(3)
            ->shouldReceive('getData')
            ->with('datePublished')
            ->andReturn('2020-01-01')
            ->shouldReceive('getLocalizedFullTitle')
            ->withAnyArgs()
            ->andReturn('My book title: My book subtitle')
            ->shouldReceive('getLocalizedTitle')
            ->withAnyArgs()
            ->andReturn('My book title')
            ->shouldReceive('getLocalizedData')
            ->with('subtitle')
            ->andReturn('My book subtitle')
            ->shouldReceive('getLocalizedData')
            ->with('abstract')
            ->andReturn('This is my book abstract')
            ->shouldReceive('getData')
            ->with('version')
            ->andReturn(1)
            ->shouldReceive('getData')
            ->with('doiObject')
            ->andReturn(
                Mockery::mock(\PKP\doi\Doi::class)
                    ->makePartial()
                    ->shouldReceive('getResolvingUrl')
                    ->withAnyArgs()
                    ->andReturn('https://doi.org/10.12345/0101010101')
                    ->getMock()
            )
            ->shouldReceive('getData')
            ->with('licenseUrl')
            ->andReturn('https://creativecommons.org/licenses/by-nc/4.0/')
            ->shouldReceive('getLocalizedData')
            ->with('copyrightHolder')
            ->andReturn('Public Knowledge Press')
            ->shouldReceive('getLocalizedCoverImageUrl')
            ->withAnyArgs()
            ->andReturn('https://omp.publicknowledgeproject.org/templates/images/book-default.png')
            ->shouldReceive('getData')
            ->with('place')
            ->andReturn('Salvador, BR')
            ->shouldReceive('getData')
            ->with('pageCount')
            ->andReturn(64)
            ->shouldReceive('getData')
            ->with('imageCount')
            ->andReturn(32)
            ->getMock();

        $this->mocks = [];
        $this->mocks['publication'] = $mockPublication;
    }

    public function testCreateThothBookFromPublication()
    {
        $this->setUpMockEnvironment();
        $mockPublication = $this->mocks['publication'];

        $factory = new ThothBookFactory();
        $thothWork = $factory->createFromPublication($mockPublication);

        $this->assertEquals(new ThothWork([
            'workType' => ThothWork::WORK_TYPE_MONOGRAPH,
            'workStatus' => ThothWork::WORK_STATUS_ACTIVE,
            'fullTitle' => 'My book title: My book subtitle',
            'title' => 'My book title',
            'subtitle' => 'My book subtitle',
            'edition' => 1,
            'publicationDate' => '2020-01-01',
            'place' => 'Salvador, BR',
            'pageCount' => 64,
            'imageCount' => 32,
            'doi' => 'https://doi.org/10.12345/0101010101',
            'license' => 'https://creativecommons.org/licenses/by-nc/4.0/',
            'copyrightHolder' => 'Public Knowledge Press',
            'landingPage' => 'https://omp.publicknowledgeproject.org/index.php/press/catalog/book/3',
            'coverUrl' => 'https://omp.publicknowledgeproject.org/templates/images/book-default.png',
            'longAbstract' => 'This is my book abstract',
        ]), $thothWork);
    }

    public function testGetWorkTypeBySubmissionWorkType()
    {
        $factory = new ThothBookFactory();
        $workType = $factory->getWorkTypeBySubmissionWorkType(WORK_TYPE_AUTHORED_WORK);
        $this->assertEquals(ThothWork::WORK_TYPE_MONOGRAPH, $workType);

        $workType = $factory->getWorkTypeBySubmissionWorkType(WORK_TYPE_EDITED_VOLUME);
        $this->assertEquals(ThothWork::WORK_TYPE_EDITED_BOOK, $workType);
    }

    public function testGetWorkStatusByDatePublished()
    {
        $factory = new ThothBookFactory();
        $workStatus = $factory->getWorkStatusByDatePublished('2020-01-01');
        $this->assertEquals(ThothWork::WORK_STATUS_ACTIVE, $workStatus);

        $workStatus = $factory->getWorkStatusByDatePublished('2050-12-12');
        $this->assertEquals(ThothWork::WORK_STATUS_FORTHCOMING, $workStatus);
    }

    public function testGetDoiFromPublication()
    {
        $mockPublication = Mockery::mock(\APP\publication\Publication::class)
            ->shouldReceive('getData')
            ->with('doiObject')
            ->andReturn(
                Mockery::mock(\PKP\doi\Doi::class)
                    ->makePartial()
                    ->shouldReceive('getResolvingUrl')
                    ->withAnyArgs()
                    ->andReturn('https://doi.org/10.12345/1111122222')
                    ->getMock()
            )
            ->getMock();

        $factory = new ThothBookFactory();
        $doi = $factory->getDoi($mockPublication);
        $this->assertEquals('https://doi.org/10.12345/1111122222', $doi);
    }

    public function testGetDoiFromPublicationFormat()
    {
        $mockIdentificationCode = $this->createMock(\APP\publicationFormat\IdentificationCode::class);
        $mockIdentificationCode->expects($this->once())
            ->method('getCode')
            ->willReturn('06');
        $mockIdentificationCode->expects($this->once())
            ->method('getValue')
            ->willReturn('10.12345/123456789');

        $mockIdCodeResult = $this->createMock(\PKP\db\DAOResultFactory::class);
        $mockIdCodeResult->expects($this->once())
            ->method('toArray')
            ->willReturn([$mockIdentificationCode]);

        $mockPubFormat = $this->createMock(\APP\publicationFormat\PublicationFormat::class);
        $mockPubFormat->expects($this->once())
            ->method('getIdentificationCodes')
            ->willReturn($mockIdCodeResult);

        $mockPublicationFormatDao = $this->createMock(\APP\publicationFormat\PublicationFormatDAO::class);
        $mockPublicationFormatDao->expects($this->any())
            ->method('getByPublicationId')
            ->willReturn([9999 => $mockPubFormat]);
        DAORegistry::registerDAO('PublicationFormatDAO', $mockPublicationFormatDao);

        $mockPublication = $this->createMock(\APP\publication\Publication::class);
        $mockPublication->expects($this->once())
            ->method('getId')
            ->willReturn(9999);

        $factory = new ThothBookFactory();
        $doi = $factory->getDoi($mockPublication);
        $this->assertEquals('https://doi.org/10.12345/123456789', $doi);
    }
}
