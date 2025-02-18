<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothChapterServiceTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothChapterServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothChapterService
 *
 * @brief Test class for the ThothChapterService class
 */

use APP\publication\Repository as PublicationRepository;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Work as ThothWork;

import('plugins.generic.thoth.classes.factories.ThothChapterFactory');
import('plugins.generic.thoth.classes.repositories.ThothChapterRepository');
import('plugins.generic.thoth.classes.services.ThothChapterService');

class ThothChapterServiceTest extends PKPTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->backup = ThothContainer::getInstance()->backup('client');
    }

    protected function tearDown(): void
    {
        ThothContainer::getInstance()->set('client', $this->backup);
        parent::tearDown();
    }

    protected function getMockedContainerKeys(): array
    {
        return [...parent::getMockedContainerKeys(), PublicationRepository::class];
    }

    public function testRegisterChapter()
    {
        ThothContainer::getInstance()->set('client', function () {
            return $this->getMockBuilder(ThothClient::class)->getMock();
        });

        $publicationRepoMock = Mockery::mock(app(PublicationRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->withAnyArgs()
            ->andReturn(
                Mockery::mock(\APP\publication\Publication::class)
                    ->shouldReceive('getData')
                    ->with('submissionId')
                    ->andReturn(99)
                    ->getMock()
            )
            ->getMock();
        app()->instance(PublicationRepository::class, $publicationRepoMock);

        $mockFactory = $this->getMockBuilder(ThothChapterFactory::class)
            ->setMethods(['createFromChapter'])
            ->getMock();
        $mockFactory->expects($this->once())
            ->method('createFromChapter')
            ->will($this->returnValue(new ThothWork()));

        $mockRepository = $this->getMockBuilder(ThothChapterRepository::class)
            ->setConstructorArgs([$this->getMockBuilder(ThothClient::class)->getMock()])
            ->setMethods(['add'])
            ->getMock();
        $mockRepository->expects($this->once())
            ->method('add')
            ->will($this->returnValue('fed8b9ee-2537-4a66-a1a1-eeadf4001c59'));

        $mockResult = $this->getMockBuilder(\PKP\db\DAOResultFactory::class)
            ->setMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockResult->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue([]));

        $mockChapter = $this->getMockBuilder(\APP\monograph\Chapter::class)
            ->setMethods(['getAuthors', 'getData'])
            ->getMock();
        $mockChapter->expects($this->once())
            ->method('getAuthors')
            ->will($this->returnValue($mockResult));
        $mockChapter->expects($this->any())
            ->method('getData')
            ->will($this->returnValueMap([
                ['publicationId', null, 99],
                ['thothChapterId', null, 'a518bebb-4a2c-48bb-8781-071ece2f2745']
            ]));

        $thothImprintId = 'd7991bfa-0ed3-432f-b9bd-0c7d0a4a1dec';

        $service = new ThothChapterService($mockFactory, $mockRepository);
        $thothChapterId = $service->register($mockChapter, $thothImprintId);

        $this->assertSame('fed8b9ee-2537-4a66-a1a1-eeadf4001c59', $thothChapterId);
    }
}
