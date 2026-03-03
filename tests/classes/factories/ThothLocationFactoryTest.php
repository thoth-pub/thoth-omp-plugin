<?php


namespace APP\plugins\generic\thoth\tests\classes\factories;
/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothLocationFactoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationFactoryTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothLocationFactory
 *
 * @brief Test class for the ThothLocationFactory class
 */

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\publication\Repository as PublicationRepository;
use APP\submission\Repository as SubmissionRepository;
use Mockery;
use PKP\core\Registry;
use PKP\db\DAORegistry;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Models\Location as ThothLocation;
use APP\plugins\generic\thoth\classes\factories\ThothLocationFactory;

class ThothLocationFactoryTest extends PKPTestCase
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
                    ->andReturn(12)
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
                    ->andReturn(12)
                    ->getMock()
            )
            ->getMock();
        app()->instance(SubmissionRepository::class, $submissionRepoMock);

        $mockContext = $this->getMockBuilder(\APP\press\Press::class)
            ->onlyMethods(['getData', 'getPath'])
            ->getMock();
        $mockContext->expects($this->any())
            ->method('getData')
            ->willReturnMap([
                ['contextId', null, 99],
            ]);
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

        $mockRequest = $this->getMockBuilder(\APP\core\Request::class)
            ->onlyMethods(['getDispatcher'])
            ->getMock();

        $mockDispatcher = $this->getMockBuilder(\PKP\core\Dispatcher::class)
            ->onlyMethods(['url'])
            ->getMock();
        $mockDispatcher->expects($this->any())
            ->method('url')
            ->willReturnMap([
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
                    null,
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
                    null,
                    'https://omp.publicknowledgeproject.org/press/catalog/view/12/1/1'
                ]
            ]);

        $mockRequest->expects($this->any())
            ->method('getDispatcher')
            ->willReturn($mockDispatcher);
        Registry::set('request', $mockRequest);

        $mockPubFormat = $this->getMockBuilder(\APP\publicationFormat\PublicationFormat::class)
            ->onlyMethods(['getData', 'getBestId'])
            ->getMock();
        $mockPubFormat->expects($this->any())
            ->method('getData')
            ->willReturnMap([
                ['publicationId', null, 99],
                ['urlRemote', null, null],
            ]);
        $mockPubFormat->expects($this->any())
            ->method('getBestId')
            ->willReturn(1);

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
