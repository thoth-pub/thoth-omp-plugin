<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/WorkServiceTest.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WorkServiceTest
 * @ingroup plugins_generic_thoth_tests
 * @see WorkService
 *
 * @brief Test class for the WorkService class
 */

import('classes.core.Application');
import('classes.press.Press');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.classes.core.PKPRouter');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.services.WorkService');

class WorkServiceTest extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->workService = new WorkService();
    }

    protected function tearDown(): void
    {
        unset($this->workService);
        parent::tearDown();
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

        return $mockRequest;
    }

    public function testGetWorkTypeBySubmissionWorkType()
    {
        $this->assertEquals(
            Work::WORK_TYPE_EDITED_BOOK,
            $this->workService->getWorkTypeBySubmissionWorkType(WORK_TYPE_EDITED_VOLUME)
        );
        $this->assertEquals(
            Work::WORK_TYPE_MONOGRAPH,
            $this->workService->getWorkTypeBySubmissionWorkType(WORK_TYPE_AUTHORED_WORK)
        );
    }

    public function testGetWorkPropsBySubmission()
    {
        $expectedProps = [
            'workType' => Work::WORK_TYPE_MONOGRAPH,
            'workStatus' => Work::WORK_STATUS_ACTIVE,
            'fullTitle' => 'Accessible Elements: Teaching Science Online and at a Distance',
            'title' => 'Accessible Elements',
            'subtitle' => 'Teaching Science Online and at a Distance',
            'edition' => 1,
            'doi' => 'https://doi.org/10.1234/0000af0000',
            'publicationDate' => '2024-07-16',
            'license' => 'https://creativecommons.org/licenses/by-nc/4.0/',
            'copyrightHolder' => 'Public Knowledge Press',
            'landingPage' => 'https://omp.publicknowledgeproject.org/index.php/press_path/catalog/book/3',
            'coverUrl' => 'https://omp.publicknowledgeproject.org/templates/images/book-default.png',
            'longAbstract' => 'Accessible Elements informs science educators about current practices in online ' .
                'and distance education: distance-delivered methods for laboratory coursework, the requisite ' .
                'administrative and institutional aspects of online and distance teaching, and the relevant ' .
                'educational theory.',
        ];

        $publication = DAORegistry::getDAO('PublicationDAO')->newDataObject();
        $publication->setId(4);
        $publication->setData(
            'title',
            'Accessible Elements',
            'en_US'
        );
        $publication->setData(
            'subtitle',
            'Teaching Science Online and at a Distance',
            'en_US'
        );
        $publication->setData(
            'version',
            1
        );
        $publication->setData(
            'pub-id::doi',
            'https://doi.org/10.1234/0000af0000'
        );
        $publication->setData(
            'datePublished',
            '2024-07-16'
        );
        $publication->setData(
            'licenseUrl',
            'https://creativecommons.org/licenses/by-nc/4.0/'
        );
        $publication->setData(
            'copyrightHolder',
            'Public Knowledge Press',
            'en_US'
        );
        $publication->setData(
            'abstract',
            'Accessible Elements informs science educators about current practices in online and distance education: ' .
            'distance-delivered methods for laboratory coursework, the requisite administrative and institutional ' .
            'aspects of online and distance teaching, and the relevant educational theory.',
            'en_US'
        );

        $submission = DAORegistry::getDAO('SubmissionDAO')->newDataObject();
        $submission->setData('id', 3);
        $submission->setData('locale', 'en_US');
        $submission->setData('workType', WORK_TYPE_AUTHORED_WORK);
        $submission->setData('currentPublicationId', 4);
        $submission->setData('publications', [$publication]);

        $request = $this->setUpMockEnvironment();

        $workProps = $this->workService->getPropertiesBySubmission($submission, $request);
        $this->assertEquals($expectedProps, $workProps);
    }

    public function testCreateNewWork()
    {
        $expectedWork = new Work();
        $expectedWork->setWorkType(Work::WORK_TYPE_EDITED_BOOK);
        $expectedWork->setWorkStatus(Work::WORK_TYPE_EDITED_BOOK);
        $expectedWork->setFullTitle('Bomb Canada and Other Unkind Remarks in the American Media');
        $expectedWork->setTitle('Bomb Canada and Other Unkind Remarks in the American Media');

        $params = [
            'workType' => Work::WORK_TYPE_EDITED_BOOK,
            'workStatus' => Work::WORK_TYPE_EDITED_BOOK,
            'fullTitle' => 'Bomb Canada and Other Unkind Remarks in the American Media',
            'title' => 'Bomb Canada and Other Unkind Remarks in the American Media',
        ];

        $work = $this->workService->new($params);
        $this->assertEquals($expectedWork, $work);
    }
}
