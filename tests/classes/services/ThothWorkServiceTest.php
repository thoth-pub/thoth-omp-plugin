<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothWorkServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see WorkService
 *
 * @brief Test class for the ThothWorkService class
 */

use APP\core\Application;
use APP\publication\Publication;
use APP\publication\Repository as PublicationRepository;
use APP\submission\Submission;
use PKP\core\PKPRequest;
use PKP\core\Registry;
use PKP\db\DAORegistry;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Client as ThothClient;
use ThothApi\GraphQL\Models\Work as ThothWork;
use ThothApi\GraphQL\Models\WorkRelation as ThothWorkRelation;

import('plugins.generic.thoth.classes.services.ThothWorkService');

class ThothWorkServiceTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->workService = new ThothWorkService();
    }

    protected function tearDown(): void
    {
        unset($this->workService);
        parent::tearDown();
    }

    protected function getMockedRegistryKeys(): array
    {
        return ['application', 'request'];
    }

    protected function getMockedContainerKeys(): array
    {
        return [...parent::getMockedContainerKeys(), PublicationRepository::class];
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
                    ->andReturn(1)
                    ->shouldReceive('getData')
                    ->with('datePublished')
                    ->andReturn('2020-01-01')
                    ->getMock()
            )
            ->getMock();

        app()->instance(PublicationRepository::class, $publicationRepoMock);

        $pressMock = Mockery::mock(\APP\press\Press::class)
            ->makePartial()
            ->shouldReceive([
                'getId' => 2,
                'getPrimaryLocale' => 'en',
                'getPath' => 'press'
            ])
            ->getMock();

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

        $mockRequest = $this->getMockBuilder(PKPRequest::class)
            ->setMethods(['getContext', 'getBaseUrl', 'url'])
            ->getMock();
        $dispatcher = $mockApplication->getDispatcher();
        $mockRequest->setDispatcher($dispatcher);
        $mockRequest->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($pressMock));
        $mockRequest->expects($this->any())
            ->method('getBaseUrl')
            ->will($this->returnValue('https://omp.publicknowledgeproject.org'));
        Registry::set('request', $mockRequest);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods([
                'createWork',
                'updateWork',
                'deleteContribution',
                'createLanguage',
                'createWorkRelation',
                'work',
                'rawQuery'
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createWork')
            ->will($this->returnValue('74fde3e2-ca4e-4597-bb0c-aee90648f5a5'));
        $mockThothClient->expects($this->any())
            ->method('updateWork')
            ->will($this->returnValue('ad3b25d6-44f7-4419-9460-4e170c4ec64f'));
        $mockThothClient->expects($this->any())
            ->method('deleteContribution')
            ->will($this->returnValue('819d8d49-6252-49d0-8f87-6b7487a0eecc'));
        $mockThothClient->expects($this->any())
            ->method('createLanguage')
            ->will($this->returnValue('47b9ecbe-98af-4c01-8b5c-0c222e996429'));
        $mockThothClient->expects($this->any())
            ->method('createWorkRelation')
            ->will($this->returnValue('3e587b61-58f1-4064-bf80-e40e5c924d27'));
        $mockThothClient->expects($this->any())
            ->method('work')
            ->will($this->returnValue(new ThothWork([
                'workId' => '39e399fb-cd40-461d-97cf-cf7f3a14cc48',
                'imprintId' => '145369a6-916a-4107-ba0f-ce28137659c2',
                'workType' => ThothWork::WORK_TYPE_BOOK_CHAPTER,
                'workStatus' => ThothWork::WORK_STATUS_ACTIVE,
                'fullTitle' => '10. Modification and Enhancement of Consciousness',
                'title' => '10. Modification and Enhancement of Consciousness'
            ])));
        $mockThothClient->expects($this->any())
            ->method('rawQuery')
            ->will($this->returnValue(
                [
                    'work' => [
                        'workId' => '39e399fb-cd40-461d-97cf-cf7f3a14cc48',
                        'imprintId' => '145369a6-916a-4107-ba0f-ce28137659c2',
                        'workType' => ThothWork::WORK_TYPE_BOOK_CHAPTER,
                        'workStatus' => ThothWork::WORK_STATUS_ACTIVE,
                        'fullTitle' => '10. Modification and Enhancement of Consciousness',
                        'title' => '10. Modification and Enhancement of Consciousness'
                    ]
                ]
            ));


        return $mockThothClient;
    }

    public function testGetWorkTypeBySubmissionWorkType()
    {
        $this->assertEquals(
            ThothWork::WORK_TYPE_EDITED_BOOK,
            $this->workService->getWorkTypeBySubmissionWorkType(WORK_TYPE_EDITED_VOLUME)
        );
        $this->assertEquals(
            ThothWork::WORK_TYPE_MONOGRAPH,
            $this->workService->getWorkTypeBySubmissionWorkType(WORK_TYPE_AUTHORED_WORK)
        );
    }

    public function testCreateNewWorkBySubmission()
    {
        $expectedThothWork = new ThothWork();
        $expectedThothWork->setWorkType(ThothWork::WORK_TYPE_MONOGRAPH);
        $expectedThothWork->setWorkStatus(ThothWork::WORK_STATUS_ACTIVE);
        $expectedThothWork->setFullTitle('Accessible Elements: Teaching Science Online and at a Distance');
        $expectedThothWork->setTitle('Accessible Elements');
        $expectedThothWork->setSubtitle('Teaching Science Online and at a Distance');
        $expectedThothWork->setEdition(1);
        $expectedThothWork->setPublicationDate('2024-07-16');
        $expectedThothWork->setDoi('https://doi.org/10.1234/0000af0000');
        $expectedThothWork->setLicense('https://creativecommons.org/licenses/by-nc/4.0/');
        $expectedThothWork->setCopyrightHolder('Public Knowledge Press');
        $expectedThothWork->setLandingPage('https://omp.publicknowledgeproject.org/index.php/press/catalog/book/3');
        $expectedThothWork->setCoverUrl('https://omp.publicknowledgeproject.org/templates/images/book-default.png');
        $expectedThothWork->setLongAbstract(
            'Accessible Elements informs science educators about current practices in online ' .
            'and distance education: distance-delivered methods for laboratory coursework, the requisite ' .
            'administrative and institutional aspects of online and distance teaching, and the relevant ' .
            'educational theory.'
        );

        $submissionMock = Mockery::mock(\APP\submission\Submission::class)
            ->makePartial()
            ->shouldReceive('getId')
            ->withAnyArgs()
            ->andReturn(3)
            ->shouldReceive('getLocale')
            ->withAnyArgs()
            ->andReturn('en')
            ->shouldReceive('getData')
            ->with('workType')
            ->andReturn(Submission::WORK_TYPE_AUTHORED_WORK)
            ->shouldReceive('getCurrentPublication')
            ->withAnyArgs()
            ->andReturn(
                Mockery::mock(\APP\publication\Publication::class)
                    ->makePartial()
                    ->shouldReceive('getId')
                    ->withAnyArgs()
                    ->andReturn(4)
                    ->shouldReceive('getLocalizedFullTitle')
                    ->withAnyArgs()
                    ->andReturn('Accessible Elements: Teaching Science Online and at a Distance')
                    ->shouldReceive('getLocalizedTitle')
                    ->withAnyArgs()
                    ->andReturn('Accessible Elements')
                    ->shouldReceive('getLocalizedData')
                    ->with('subtitle')
                    ->andReturn('Teaching Science Online and at a Distance')
                    ->shouldReceive('getLocalizedData')
                    ->with('abstract')
                    ->andReturn(
                        'Accessible Elements informs science educators about current practices in online ' .
                        'and distance education: distance-delivered methods for laboratory coursework, the requisite ' .
                        'administrative and institutional aspects of online and distance teaching, and the relevant ' .
                        'educational theory.'
                    )
                    ->shouldReceive('getLocalizedData')
                    ->with('copyrightHolder')
                    ->andReturn('Public Knowledge Press')
                    ->shouldReceive('getData')
                    ->with('version')
                    ->andReturn(1)
                    ->shouldReceive('getData')
                    ->with('datePublished')
                    ->andReturn('2024-07-16')
                    ->shouldReceive('getData')
                    ->with('licenseUrl')
                    ->andReturn('https://creativecommons.org/licenses/by-nc/4.0/')
                    ->shouldReceive('getData')
                    ->with('doiObject')
                    ->andReturn(
                        Mockery::mock(\PKP\doi\Doi::class)
                            ->makePartial()
                            ->shouldReceive('getResolvingUrl')
                            ->withAnyArgs()
                            ->andReturn('https://doi.org/10.1234/0000af0000')
                            ->getMock()
                    )
                    ->getMock()
            )
            ->getMock();

        $this->setUpMockEnvironment();

        $thothWork = $this->workService->newBySubmission($submissionMock);
        $this->assertEquals($expectedThothWork, $thothWork);
    }

    public function testCreateNewWorkByChapter()
    {
        $expectedThothWork = new ThothWork();
        $expectedThothWork->setWorkType(ThothWork::WORK_TYPE_BOOK_CHAPTER);
        $expectedThothWork->setWorkStatus(ThothWork::WORK_STATUS_ACTIVE);
        $expectedThothWork->setFullTitle('Chapter 1: Interactions Affording Distance Science Education');
        $expectedThothWork->setTitle('Chapter 1: Interactions Affording Distance Science Education');
        $expectedThothWork->setPublicationDate('2024-03-21');
        $expectedThothWork->setPageCount('27');
        $expectedThothWork->setDoi('https://doi.org/10.1234/jpk.14.c54');

        $chapterMock = Mockery::mock(\APP\monograph\Chapter::class)
            ->makePartial()
            ->shouldReceive('getLocalizedFullTitle')
            ->withAnyArgs()
            ->andReturn('Chapter 1: Interactions Affording Distance Science Education')
            ->shouldReceive('getLocalizedTitle')
            ->withAnyArgs()
            ->andReturn('Chapter 1: Interactions Affording Distance Science Education')
            ->shouldReceive('getDatePublished')
            ->withAnyArgs()
            ->andReturn('2024-03-21')
            ->shouldReceive('getPages')
            ->withAnyArgs()
            ->andReturn('27')
            ->shouldReceive('getData')
            ->with('doiObject')
            ->andReturn(
                Mockery::mock(\PKP\doi\Doi::class)
                    ->makePartial()
                    ->shouldReceive('getResolvingUrl')
                    ->withAnyArgs()
                    ->andReturn('https://doi.org/10.1234/jpk.14.c54')
                    ->getMock()
            )
            ->getMock();

        $thothWork = $this->workService->newByChapter($chapterMock);
        $this->assertEquals($expectedThothWork, $thothWork);
    }

    public function testCreateNewWork()
    {
        $expectedWork = new ThothWork();
        $expectedWork->setWorkType(ThothWork::WORK_TYPE_EDITED_BOOK);
        $expectedWork->setWorkStatus(ThothWork::WORK_TYPE_EDITED_BOOK);
        $expectedWork->setFullTitle('Bomb Canada and Other Unkind Remarks in the American Media');
        $expectedWork->setTitle('Bomb Canada and Other Unkind Remarks in the American Media');

        $params = [
            'workType' => ThothWork::WORK_TYPE_EDITED_BOOK,
            'workStatus' => ThothWork::WORK_TYPE_EDITED_BOOK,
            'fullTitle' => 'Bomb Canada and Other Unkind Remarks in the American Media',
            'title' => 'Bomb Canada and Other Unkind Remarks in the American Media',
        ];

        $work = $this->workService->new($params);
        $this->assertEquals($expectedWork, $work);
    }

    public function testGetWork()
    {
        $expectedThothWork = new ThothWork();
        $expectedThothWork->setWorkId('39e399fb-cd40-461d-97cf-cf7f3a14cc48');
        $expectedThothWork->setImprintId('145369a6-916a-4107-ba0f-ce28137659c2');
        $expectedThothWork->setWorkType(ThothWork::WORK_TYPE_BOOK_CHAPTER);
        $expectedThothWork->setWorkStatus(ThothWork::WORK_STATUS_ACTIVE);
        $expectedThothWork->setTitle('10. Modification and Enhancement of Consciousness');
        $expectedThothWork->setFullTitle('10. Modification and Enhancement of Consciousness');

        $mockThothClient = $this->setUpMockEnvironment();
        ThothContainer::getInstance()->set('client', function () use ($mockThothClient) {
            return $mockThothClient;
        });

        $thothWork = $this->workService->get('39e399fb-cd40-461d-97cf-cf7f3a14cc48');

        $this->assertEquals($expectedThothWork, $thothWork);
    }

    public function testGetWorkByDoi()
    {
        $doi = 'https://doi.org/10.12345/12345678';

        $expectedThothWork = new ThothWork([
            'doi' => $doi
        ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods([
                'workByDoi',
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('workByDoi')
            ->will($this->returnValue(new ThothWork([
                'doi' => $doi
            ])));

        ThothContainer::getInstance()->set('client', function () use ($mockThothClient) {
            return $mockThothClient;
        });

        $thothWork = $this->workService->getByDoi($doi);

        $this->assertEquals($expectedThothWork, $thothWork);
    }

    public function testSearchWork()
    {
        $title = 'My book title';

        $expectedThothWorks = [
            new ThothWork([
                'title' => $title
            ])
        ];

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods([
                'works',
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('works')
            ->will($this->returnValue([
                new ThothWork([
                    'title' => $title
                ])
            ]));

        ThothContainer::getInstance()->set('client', function () use ($mockThothClient) {
            return $mockThothClient;
        });

        $thothWorks = $this->workService->search($title);

        $this->assertEquals($expectedThothWorks, $thothWorks);
    }

    public function testRegisterBook()
    {
        $thothImprintId = 'f02786d4-3bcc-473e-8d43-3da66c7e877c';

        $expectedThothBook = new ThothWork();
        $expectedThothBook->setWorkId('74fde3e2-ca4e-4597-bb0c-aee90648f5a5');
        $expectedThothBook->setImprintId($thothImprintId);
        $expectedThothBook->setWorkType(ThothWork::WORK_TYPE_MONOGRAPH);
        $expectedThothBook->setWorkStatus(ThothWork::WORK_STATUS_ACTIVE);
        $expectedThothBook->setTitle('A Designer\'s Log');
        $expectedThothBook->setSubtitle('Case Studies in Instructional Design');
        $expectedThothBook->setLongAbstract('');
        $expectedThothBook->setFullTitle('A Designer\'s Log: Case Studies in Instructional Design');
        $expectedThothBook->setLandingPage('https://omp.publicknowledgeproject.org/index.php/press/catalog/book/999');
        $expectedThothBook->setCoverUrl('https://omp.publicknowledgeproject.org/templates/images/book-default.png');

        $publication = new Publication();
        $publication->setId(999);
        $publication->setData('title', 'A Designer\'s Log', 'en_US');
        $publication->setData('subtitle', 'Case Studies in Instructional Design', 'en_US');

        $submission = new Submission();
        $submission->setData('id', 999);
        $submission->setData('locale', 'en_US');
        $submission->setData('workType', Submission::WORK_TYPE_AUTHORED_WORK);
        $submission->setData('currentPublicationId', 999);
        $submission->setData('publications', [$publication]);

        $mockThothClient = $this->setUpMockEnvironment();
        ThothContainer::getInstance()->set('client', function () use ($mockThothClient) {
            return $mockThothClient;
        });

        $thothBook = $this->workService->registerBook($submission, $thothImprintId);
        $this->assertEquals($expectedThothBook, $thothBook);
    }

    public function testRegisterChapter()
    {
        $thothImprintId = 'f02786d4-3bcc-473e-8d43-3da66c7e877c';

        $expectedThothChapter = new ThothWork();
        $expectedThothChapter->setWorkId('74fde3e2-ca4e-4597-bb0c-aee90648f5a5');
        $expectedThothChapter->setImprintId($thothImprintId);
        $expectedThothChapter->setWorkType(ThothWork::WORK_TYPE_BOOK_CHAPTER);
        $expectedThothChapter->setWorkStatus(ThothWork::WORK_STATUS_ACTIVE);
        $expectedThothChapter->setFullTitle('Chapter 2: Classical Music and the Classical Mind');
        $expectedThothChapter->setTitle('Chapter 2: Classical Music and the Classical Mind');
        $expectedThothChapter->setPublicationDate('2020-01-01');

        $chapter = DAORegistry::getDAO('ChapterDAO')->newDataObject();
        $chapter->setTitle('Chapter 2: Classical Music and the Classical Mind');
        $chapter->setData('publicationId', 9999);

        $mockThothClient = $this->setUpMockEnvironment();
        ThothContainer::getInstance()->set('client', function () use ($mockThothClient) {
            return $mockThothClient;
        });

        $thothChapter = $this->workService->registerChapter($chapter, $thothImprintId);
        $this->assertEquals($expectedThothChapter, $thothChapter);
    }

    public function testRegisterRelation()
    {
        $thothImprintId = 'f02786d4-3bcc-473e-8d43-3da66c7e877c';
        $relatedWorkId = '7d861db5-22f6-4ef8-abbb-b56ab8397624';

        $expectedThothWorkRelation = new ThothWorkRelation();
        $expectedThothWorkRelation->setWorkRelationId('3e587b61-58f1-4064-bf80-e40e5c924d27');
        $expectedThothWorkRelation->setRelatorWorkId('74fde3e2-ca4e-4597-bb0c-aee90648f5a5');
        $expectedThothWorkRelation->setRelatedWorkId($relatedWorkId);
        $expectedThothWorkRelation->setRelationType(ThothWorkRelation::RELATION_TYPE_IS_CHILD_OF);
        $expectedThothWorkRelation->setRelationOrdinal(5);

        $chapter = DAORegistry::getDAO('ChapterDAO')->newDataObject();
        $chapter->setTitle('Epilogue');
        $chapter->setData('publicationId', 9999);
        $chapter->setSequence(4);

        $mockThothClient = $this->setUpMockEnvironment();
        ThothContainer::getInstance()->set('client', function () use ($mockThothClient) {
            return $mockThothClient;
        });

        $thothWorkRelation = $this->workService->registerWorkRelation(
            $chapter,
            $thothImprintId,
            $relatedWorkId
        );
        $this->assertEquals($expectedThothWorkRelation, $thothWorkRelation);
    }

    public function testUpdateBook()
    {
        $thothWork = new ThothWork();
        $thothWork->setWorkId('39e399fb-cd40-461d-97cf-cf7f3a14cc48');
        $thothWork->setImprintId('145369a6-916a-4107-ba0f-ce28137659c2');
        $thothWork->setWorkType(ThothWork::WORK_TYPE_EDITED_BOOK);
        $thothWork->setWorkStatus(ThothWork::WORK_STATUS_ACTIVE);
        $thothWork->setFullTitle('Cuba : Restructuring the Economy');
        $thothWork->setTitle('Cuba : Restructuring the Economy');
        $thothWork->setLongAbstract('');
        $thothWork->setLandingPage('https://omp.publicknowledgeproject.org/index.php/press/catalog/book');
        $thothWork->setCoverUrl('https://omp.publicknowledgeproject.org/templates/images/book-default.png');

        $expectedThothWork = clone $thothWork;
        $expectedThothWork->setFullTitle('Cuba : Restructuring the Economy: A Contribution to the Debate');
        $expectedThothWork->setSubtitle('A Contribution to the Debate');

        $mockThothClient = $this->setUpMockEnvironment();
        ThothContainer::getInstance()->set('client', function () use ($mockThothClient) {
            return $mockThothClient;
        });

        $publication = new Publication();
        $publication->setData('title', 'Cuba : Restructuring the Economy', 'en_US');
        $publication->setData('subtitle', 'A Contribution to the Debate', 'en_US');
        $submission = new Submission();
        $submission->setData('workType', WORK_TYPE_EDITED_VOLUME);

        $thothWorkId = '49e58788-95d6-427f-8726-c24f5b15484c';
        $updatedThothWork = $this->workService->updateBook(
            $thothWorkId,
            $submission,
            $publication
        );

        $this->assertEquals($expectedThothWork, $updatedThothWork);
    }
}
