<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
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
import('plugins.generic.thoth.thoth.models.ThothContribution');
import('plugins.generic.thoth.thoth.models.ThothContributor');
import('plugins.generic.thoth.thoth.models.ThothWork');
import('plugins.generic.thoth.thoth.models.ThothWorkRelation');
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
        return ['SubmissionDAO', 'PublicationDAO'];
    }

    private function setUpMockEnvironment()
    {
        $press = new Press();
        $press->setId(2);
        $press->setPrimaryLocale('en_US');
        $press->setPath('press');

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
            ->will($this->returnValue($press));
        $mockRequest->expects($this->any())
            ->method('getBaseUrl')
            ->will($this->returnValue('https://omp.publicknowledgeproject.org'));
        Registry::set('request', $mockRequest);

        $submissionDaoMock = $this->getMockBuilder(SubmissionDAO::class)
            ->setMethods(['getById'])
            ->getMock();
        $submission = new Submission();
        $submission->setId(53);
        $submissionDaoMock->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($submission));
        DAORegistry::registerDAO('SubmissionDAO', $submissionDaoMock);

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
            ->setMethods([
                'createWork',
                'createContributor',
                'createContribution',
                'createWorkRelation',
                'createPublication',
                'createLocation',
                'createSubject',
                'createLanguage',
                'createReference'
            ])
            ->getMock();
        $mockThothClient->expects($this->any())
            ->method('createWork')
            ->will($this->returnValue('74fde3e2-ca4e-4597-bb0c-aee90648f5a5'));
        $mockThothClient->expects($this->any())
            ->method('createContributor')
            ->will($this->returnValue('f70f709e-2137-4c87-a2e5-d52b263759ec'));
        $mockThothClient->expects($this->any())
            ->method('createContribution')
            ->will($this->returnValue('67afac83-b015-4f32-9576-60b665a9e685'));
        $mockThothClient->expects($this->any())
            ->method('createWorkRelation')
            ->will($this->returnValue('3e587b61-58f1-4064-bf80-e40e5c924d27'));
        $mockThothClient->expects($this->any())
            ->method('createPublication')
            ->will($this->returnValue('80359118-9b33-4cf4-a4b4-8784e6d4375a'));
        $mockThothClient->expects($this->any())
            ->method('createLocation')
            ->will($this->returnValue('03b0367d-bba3-4e26-846a-4c36d3920db2'));
        $mockThothClient->expects($this->any())
            ->method('createSubject')
            ->will($this->returnValue('6a9cdd5a-5877-433e-8063-9af0617eaa17'));
        $mockThothClient->expects($this->any())
            ->method('createLanguage')
            ->will($this->returnValue('47b9ecbe-98af-4c01-8b5c-0c222e996429'));
        $mockThothClient->expects($this->any())
            ->method('createReference')
            ->will($this->returnValue('c9521541-6676-4cf4-ad6d-06299682718b'));

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
        $expectedBook = new ThothWork();
        $expectedBook->setId('74fde3e2-ca4e-4597-bb0c-aee90648f5a5');
        $expectedBook->setImprintId('f02786d4-3bcc-473e-8d43-3da66c7e877c');
        $expectedBook->setWorkType(ThothWork::WORK_TYPE_MONOGRAPH);
        $expectedBook->setWorkStatus(ThothWork::WORK_STATUS_ACTIVE);
        $expectedBook->setTitle('A Designer\'s Log');
        $expectedBook->setSubtitle('Case Studies in Instructional Design');
        $expectedBook->setFullTitle('A Designer\'s Log: Case Studies in Instructional Design');
        $expectedBook->setLandingPage('https://omp.publicknowledgeproject.org/index.php/press/catalog/book/11');
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

    public function testRegisterChapter()
    {
        $expectedChapter = new ThothWork();
        $expectedChapter->setId('74fde3e2-ca4e-4597-bb0c-aee90648f5a5');
        $expectedChapter->setImprintId('f02786d4-3bcc-473e-8d43-3da66c7e877c');
        $expectedChapter->setWorkType(ThothWork::WORK_TYPE_BOOK_CHAPTER);
        $expectedChapter->setWorkStatus(ThothWork::WORK_STATUS_ACTIVE);
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

        $expectedRelation = new ThothWorkRelation();
        $expectedRelation->setId('3e587b61-58f1-4064-bf80-e40e5c924d27');
        $expectedRelation->setRelatorWorkId('74fde3e2-ca4e-4597-bb0c-aee90648f5a5');
        $expectedRelation->setRelatedWorkId($relatedWorkId);
        $expectedRelation->setRelationType(ThothWorkRelation::RELATION_TYPE_IS_CHILD_OF);
        $expectedRelation->setRelationOrdinal(5);

        $chapter = DAORegistry::getDAO('ChapterDAO')->newDataObject();
        $chapter->setTitle('Epilogue');
        $chapter->setSequence(4);

        $relation = $this->thothService->registerRelation($chapter, $relatedWorkId);
        $this->assertEquals($expectedRelation, $relation);
    }

    public function testRegisterPublication()
    {
        $workId = '2a065323-76cd-4f54-b83b-19f2a925f426';

        $expectedPublication = new ThothPublication();
        $expectedPublication->setId('80359118-9b33-4cf4-a4b4-8784e6d4375a');
        $expectedPublication->setWorkId($workId);
        $expectedPublication->setPublicationType(ThothPublication::PUBLICATION_TYPE_HTML);
        $expectedPublication->setIsbn('978-1-912656-00-4');

        $identificationCode = DAORegistry::getDAO('IdentificationCodeDAO')->newDataObject();
        $identificationCode->setCode('15');
        $identificationCode->setValue('978-1-912656-00-4');

        $mockResult = $this->getMockBuilder(DAOResultFactory::class)
            ->setMethods(['toArray'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockResult->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue([$identificationCode]));

        $publicationFormat = $mockRequest = $this->getMockBuilder(PublicationFormat::class)
            ->setMethods(['getIdentificationCodes'])
            ->getMock();
        $publicationFormat->expects($this->any())
            ->method('getIdentificationCodes')
            ->will($this->returnValue($mockResult));
        $publicationFormat->setEntryKey('DA');
        $publicationFormat->setName('HTML', 'en_US');

        $publication = $this->thothService->registerPublication($publicationFormat, $workId);
        $this->assertEquals($expectedPublication, $publication);
    }

    public function testRegisterKeyword()
    {
        $workId = '1ef03055-2890-429a-b870-f9671711bcc4';

        $expectedThothKeyword = new ThothSubject();
        $expectedThothKeyword->setId('6a9cdd5a-5877-433e-8063-9af0617eaa17');
        $expectedThothKeyword->setWorkId($workId);
        $expectedThothKeyword->setSubjectType(ThothSubject::SUBJECT_TYPE_KEYWORD);
        $expectedThothKeyword->setSubjectCode('Psychology');
        $expectedThothKeyword->setSubjectOrdinal(1);

        $submissionKeyword = 'Psychology';

        $thothKeyword = $this->thothService->registerKeyword($submissionKeyword, $workId);
        $this->assertEquals($expectedThothKeyword, $thothKeyword);
    }

    public function testRegisterLanguage()
    {
        $workId = '0600200b-865b-4706-a7e5-b5861a60dbc4';

        $expectedLanguage = new ThothLanguage();
        $expectedLanguage->setId('47b9ecbe-98af-4c01-8b5c-0c222e996429');
        $expectedLanguage->setWorkId($workId);
        $expectedLanguage->setLanguageCode('ENG');
        $expectedLanguage->setLanguageRelation(ThothLanguage::LANGUAGE_RELATION_ORIGINAL);
        $expectedLanguage->setMainLanguage(true);

        $submissionLocale = 'en_US';

        $language = $this->thothService->registerLanguage($submissionLocale, $workId);
        $this->assertEquals($expectedLanguage, $language);
    }

    public function testRegisterReference()
    {
        $workId = '9a6aab2b-8077-4cd3-9dd1-19c115f2a3ca';
        $rawCitation = 'Fendrick AM, Monto AS, Nightengale B, Sarnes M. The economic burden of non-influenza-related ' .
            'viral respiratory tract infection in the United States. Arch Intern Med. 2003;163(4):487-494. ' .
            'DOI: https://doi.org/10.1001/archinte.163.4.487 PMID: https://www.ncbi.nlm.nih.gov/pubmed/12588210';

        $expectedReference = new ThothReference();
        $expectedReference->setId('c9521541-6676-4cf4-ad6d-06299682718b');
        $expectedReference->setWorkId($workId);
        $expectedReference->setReferenceOrdinal(3);
        $expectedReference->setUnstructuredCitation($rawCitation);

        $citation = DAORegistry::getDAO('CitationDAO')->_newDataObject();
        $citation->setRawCitation($rawCitation);
        $citation->setSequence(3);

        $reference = $this->thothService->registerReference($citation, $workId);
        $this->assertEquals($expectedReference, $reference);
    }
}
