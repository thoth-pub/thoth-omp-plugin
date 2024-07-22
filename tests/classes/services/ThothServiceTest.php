<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothService
 *
 * @brief Test class for the ThothService class
 */

import('classes.core.Application');
import('classes.press.Press');
import('classes.publication.Publication');
import('classes.submission.Submission');
import('classes.monograph.Author');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.classes.core.PKPRouter');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.services.ThothService');
import('plugins.generic.thoth.thoth.models.Contribution');
import('plugins.generic.thoth.thoth.models.Contributor');
import('plugins.generic.thoth.thoth.models.Work');
import('plugins.generic.thoth.thoth.models.WorkRelation');
import('plugins.generic.thoth.thoth.ThothClient');
import('plugins.generic.thoth.ThothPlugin');

class ThothServiceTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->thothService = $this->setUpMockEnvironment();
    }

    protected function tearDown(): void
    {
        unset($this->thothService);
        parent::tearDown();
    }

    protected function getMockedRegistryKeys()
    {
        return ['application', 'request'];
    }

    protected function getMockedDAOs()
    {
        return ['PublicationDAO'];
    }

    private function setUpMockEnvironment()
    {
        $press = new Press();
        $press->setId(2);
        $press->setPrimaryLocale('en_US');
        $press->setPath('press_path');

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
            ->setMethods(['getContext', 'getBaseUrl'])
            ->getMock();
        $dispatcher = $mockApplication->getDispatcher();
        $mockRequest->setDispatcher($dispatcher);
        $mockRequest->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($press));
        $mockRequest->expects($this->any())
            ->method('getBaseUrl')
            ->will($this->returnValue('https://omp.publicknowledgeproject.org'));
        Registry::set('request', $mockRequest);

        $publicationMockDao = $this->getMockBuilder(PublicationDAO::class)
            ->setMethods(['getById'])
            ->getMock();

        $publication = new Publication();
        $publication->setData('primaryContactId', 13);

        $publicationMockDao->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($publication));

        DAORegistry::registerDAO('PublicationDAO', $publicationMockDao);

        $mockPlugin = $this->getMockBuilder(ThothPlugin::class)
            ->setMethods(['getSetting'])
            ->getMock();
        $mockPlugin->expects($this->any())
            ->method('getSetting')
            ->willReturnMap([
                [$press->getId(), 'apiUrl', 'https://api.thoth.test.pub/'],
                [$press->getId(), 'imprintId', 'f02786d4-3bcc-473e-8d43-3da66c7e877c'],
                [$press->getId(), 'email', 'thoth@mailinator.com'],
                [$press->getId(), 'password', 'thoth']
            ]);

        $mockThothClient = $this->getMockBuilder(ThothClient::class)
            ->setMethods(['createWork', 'createContributor', 'createContribution', 'createWorkRelation'])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createWork')
            ->will($this->onConsecutiveCalls('74fde3e2-ca4e-4597-bb0c-aee90648f5a5'));
        $mockThothClient->expects($this->any())
            ->method('createContributor')
            ->will($this->returnValue('f70f709e-2137-4c87-a2e5-d52b263759ec'));
        $mockThothClient->expects($this->any())
            ->method('createContribution')
            ->will($this->returnValue('67afac83-b015-4f32-9576-60b665a9e685'));
        $mockThothClient->expects($this->any())
            ->method('createWorkRelation')
            ->will($this->returnValue('3e587b61-58f1-4064-bf80-e40e5c924d27'));

        $thothService = $this->getMockBuilder(ThothService::class)
            ->setMethods(['getThothClient'])
            ->setConstructorArgs([$mockPlugin, $press->getId()])
            ->getMock();
        $thothService->expects($this->any())
            ->method('getThothClient')
            ->will($this->returnValue($mockThothClient));

        return $thothService;
    }

    public function testRegisterBook()
    {
        $expectedBook = new Work();
        $expectedBook->setId('74fde3e2-ca4e-4597-bb0c-aee90648f5a5');
        $expectedBook->setImprintId('f02786d4-3bcc-473e-8d43-3da66c7e877c');
        $expectedBook->setWorkType(Work::WORK_TYPE_MONOGRAPH);
        $expectedBook->setWorkStatus(Work::WORK_STATUS_ACTIVE);
        $expectedBook->setTitle('A Designer\'s Log');
        $expectedBook->setSubtitle('Case Studies in Instructional Design');
        $expectedBook->setFullTitle('A Designer\'s Log: Case Studies in Instructional Design');
        $expectedBook->setLandingPage('https://omp.publicknowledgeproject.org/index.php/press_path/catalog/book/11');
        $expectedBook->setCoverUrl('https://omp.publicknowledgeproject.org/templates/images/book-default.png');

        $publication = new Publication();
        $publication->setId(12);
        $publication->setData('title', 'A Designer\'s Log', 'en_US');
        $publication->setData('subtitle', 'Case Studies in Instructional Design', 'en_US');

        $submission = new Submission();
        $submission->setData('id', 11);
        $submission->setData('locale', 'en_US');
        $submission->setData('workType', WORK_TYPE_AUTHORED_WORK);
        $submission->setData('currentPublicationId', 12);
        $submission->setData('publications', [$publication]);

        $book = $this->thothService->registerBook($submission);
        $this->assertEquals($expectedBook, $book);
    }

    public function testRegisterContributor()
    {
        $expectedContributor = new Contributor();
        $expectedContributor->setId('f70f709e-2137-4c87-a2e5-d52b263759ec');
        $expectedContributor->setFirstName('Brian');
        $expectedContributor->setLastName('Dupuis');
        $expectedContributor->setFullName('Brian Dupuis');

        $author = new Author();
        $author->setGivenName('Brian', 'en_US');
        $author->setFamilyName('Dupuis', 'en_US');

        $contributor = $this->thothService->registerContributor($author);
        $this->assertEquals($expectedContributor, $contributor);
    }

    public function testRegisterContribution()
    {
        $expectedContribution = new Contribution();
        $expectedContribution->setId('67afac83-b015-4f32-9576-60b665a9e685');
        $expectedContribution->setWorkId('45a6622c-a306-4559-bb77-25367dc881b8');
        $expectedContribution->setContributorId('f70f709e-2137-4c87-a2e5-d52b263759ec');
        $expectedContribution->setContributionType(Contribution::CONTRIBUTION_TYPE_AUTHOR);
        $expectedContribution->setMainContribution(true);
        $expectedContribution->setContributionOrdinal(1);
        $expectedContribution->setFirstName('Michael');
        $expectedContribution->setLastName('Wilson');
        $expectedContribution->setFullName('Michael Wilson');

        $userGroup = new UserGroup();
        $userGroup->setData('nameLocaleKey', 'default.groups.name.author');

        $author = $this->getMockBuilder(Author::class)
            ->setMethods(['getUserGroup'])
            ->getMock();
        $author->expects($this->any())
            ->method('getUserGroup')
            ->will($this->returnValue($userGroup));
        $author->setId(13);
        $author->setGivenName('Michael', 'en_US');
        $author->setFamilyName('Wilson', 'en_US');
        $author->setSequence(0);

        $contribution = $this->thothService->registerContribution($author, '45a6622c-a306-4559-bb77-25367dc881b8');
        $this->assertEquals($expectedContribution, $contribution);
    }

    public function testRegisterChapter()
    {
        $expectedChapter = new Work();
        $expectedChapter->setId('74fde3e2-ca4e-4597-bb0c-aee90648f5a5');
        $expectedChapter->setImprintId('f02786d4-3bcc-473e-8d43-3da66c7e877c');
        $expectedChapter->setWorkType(Work::WORK_TYPE_BOOK_CHAPTER);
        $expectedChapter->setWorkStatus(Work::WORK_STATUS_ACTIVE);
        $expectedChapter->setFullTitle('Chapter 2: Classical Music and the Classical Mind');
        $expectedChapter->setTitle('Chapter 2: Classical Music and the Classical Mind');

        $chapter = DAORegistry::getDAO('ChapterDAO')->newDataObject();
        $chapter->setTitle('Chapter 2: Classical Music and the Classical Mind');

        $chapter = $this->thothService->registerChapter($chapter);
        $this->assertEquals($expectedChapter, $chapter);
    }

    public function testRegisterRelation()
    {
        $relatedWorkId = '7d861db5-22f6-4ef8-abbb-b56ab8397624';

        $expectedRelation = new WorkRelation();
        $expectedRelation->setId('3e587b61-58f1-4064-bf80-e40e5c924d27');
        $expectedRelation->setRelatorWorkId('74fde3e2-ca4e-4597-bb0c-aee90648f5a5');
        $expectedRelation->setRelatedWorkId($relatedWorkId);
        $expectedRelation->setRelationType(WorkRelation::RELATION_TYPE_IS_CHILD_OF);
        $expectedRelation->setRelationOrdinal(5);

        $chapter = DAORegistry::getDAO('ChapterDAO')->newDataObject();
        $chapter->setTitle('Epilogue');
        $chapter->setSequence(4);

        $relation = $this->thothService->registerRelation($chapter, $relatedWorkId);
        $this->assertEquals($expectedRelation, $relation);
    }
}
