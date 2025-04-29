<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothBookFactoryTest.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookFactoryTest
 * @ingroup plugins_generic_thoth_tests
 * @see ThothBookFactory
 *
 * @brief Test class for the ThothBookFactory class
 */

use ThothApi\GraphQL\Models\Work as ThothWork;

import('classes.press.Press');
import('classes.press.PressDAO');
import('classes.publication.Publication');
import('classes.publicationFormat.IdentificationCode');
import('classes.publicationFormat.PublicationFormatDAO');
import('classes.publicationFormat.PublicationFormat');
import('classes.submission.Submission');
import('classes.submission.SubmissionDAO');
import('lib.pkp.classes.core.Dispatcher');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.classes.db.DAOResultFactory');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothBookFactory');

class ThothBookFactoryTest extends PKPTestCase
{
    protected function getMockedDAOs()
    {
        return ['PressDAO', 'SubmissionDAO', 'PublicationFormatDAO'];
    }

    protected function getMockedRegistryKeys()
    {
        return ['request'];
    }

    private function setUpMockEnvironment()
    {
        $mockSubmission = $this->getMockBuilder(Submission::class)
            ->setMethods(['getData', 'getBestId'])
            ->getMock();
        $mockSubmission->expects($this->any())
            ->method('getData')
            ->will($this->returnValue(WORK_TYPE_AUTHORED_WORK));
        $mockSubmission->expects($this->any())
            ->method('getBestId')
            ->will($this->returnValue(3));

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
            ->will($this->returnValue('https://omp.publicknowledgeproject.org/index.php/press/catalog/book/3'));

        $mockRequest = $this->getMockBuilder(PKPRequest::class)
            ->setMethods(['getDispatcher'])
            ->getMock();
        $mockRequest->expects($this->any())
            ->method('getDispatcher')
            ->will($this->returnValue($mockDispatcher));
        Registry::set('request', $mockRequest);

        $mockPublication = $this->getMockBuilder(Publication::class)
            ->setMethods([
                'getData',
                'getLocalizedData',
                'getLocalizedFullTitle',
                'getLocalizedTitle',
                'getStoredPubId',
                'getLocalizedCoverImageUrl'
            ])
            ->getMock();
        $mockPublication->expects($this->any())
            ->method('getData')
            ->will($this->returnValueMap([
                ['version', null, 1],
                ['datePublished', null, '2020-01-01'],
                ['place', null, 'Salvador, BR'],
                ['pageCount', null, 64],
                ['imageCount', null, 32],
                ['licenseUrl', null, 'https://creativecommons.org/licenses/by-nc/4.0/']
            ]));
        $mockPublication->expects($this->any())
            ->method('getLocalizedData')
            ->will($this->returnValueMap([
                ['subtitle', null, null, 'My book subtitle'],
                ['abstract', null, null, 'This is my book abstract'],
                ['copyrightHolder', null, null, 'Public Knowledge Press']
            ]));
        $mockPublication->expects($this->once())
            ->method('getLocalizedFullTitle')
            ->will($this->returnValue('My book title: My book subtitle'));
        $mockPublication->expects($this->once())
            ->method('getLocalizedTitle')
            ->will($this->returnValue('My book title'));
        $mockPublication->expects($this->once())
            ->method('getLocalizedCoverImageUrl')
            ->will($this->returnValue('https://omp.publicknowledgeproject.org/templates/images/book-default.png'));
        $mockPublication->expects($this->once())
            ->method('getStoredPubId')
            ->with($this->equalTo('doi'))
            ->will($this->returnValue('10.12345/0101010101'));

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
        $mockPublication = $this->createMock(Publication::class);
        $mockPublication->expects($this->once())
            ->method('getStoredPubId')
            ->with('doi')
            ->willReturn('10.12345/11112222');

        $factory = new ThothBookFactory();
        $doi = $factory->getDoi($mockPublication);
        $this->assertEquals('https://doi.org/10.12345/11112222', $doi);
    }

    public function testGetDoiFromPublicationFormat()
    {
        $mockIdentificationCode = $this->createMock(IdentificationCode::class);
        $mockIdentificationCode->expects($this->once())
            ->method('getCode')
            ->willReturn('06');
        $mockIdentificationCode->expects($this->once())
            ->method('getValue')
            ->willReturn('10.12345/123456789');

        $mockIdCodeResult = $this->createMock(DAOResultFactory::class);
        $mockIdCodeResult->expects($this->once())
            ->method('toArray')
            ->willReturn([$mockIdentificationCode]);

        $mockPubFormat = $this->createMock(PublicationFormat::class);
        $mockPubFormat->expects($this->once())
            ->method('getIdentificationCodes')
            ->willReturn($mockIdCodeResult);

        $mockPubFormatResult = $this->createMock(DAOResultFactory::class);
        $mockPubFormatResult->expects($this->once())
            ->method('toArray')
            ->willReturn([$mockPubFormat]);

        $mockPublicationFormatDao = $this->createMock(PublicationFormatDAO::class);
        $mockPublicationFormatDao->expects($this->any())
            ->method('getByPublicationId')
            ->willReturn($mockPubFormatResult);
        DAORegistry::registerDAO('PublicationFormatDAO', $mockPublicationFormatDao);

        $mockPublication = $this->createMock(Publication::class);
        $mockPublication->expects($this->once())
            ->method('getId')
            ->willReturn(9999);

        $factory = new ThothBookFactory();
        $doi = $factory->getDoi($mockPublication);
        $this->assertEquals('https://doi.org/10.12345/123456789', $doi);
    }
}
