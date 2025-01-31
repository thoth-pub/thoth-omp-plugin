<?php

use ThothApi\GraphQL\Models\Work as ThothWork;

import('classes.press.Press');
import('classes.publication.Publication');
import('classes.submission.Submission');
import('lib.pkp.classes.core.Dispatcher');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.factories.ThothBookFactory');

class ThothBookFactoryTest extends PKPTestCase
{
    public function testCreateThothBookFromSubmission()
    {
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

        $mockSubmission = $this->getMockBuilder(Submission::class)
            ->setMethods([
                'getData',
                'getCurrentPublication'
            ])
            ->getMock();
        $mockSubmission->expects($this->any())
            ->method('getData')
            ->will($this->returnValue(WORK_TYPE_AUTHORED_WORK));
        $mockSubmission->expects($this->any())
            ->method('getCurrentPublication')
            ->will($this->returnValue($mockPublication));

        $mockDispatcher = $this->getMockBuilder(Dispatcher::class)
            ->setMethods(['url'])
            ->getMock();
        $mockDispatcher->expects($this->any())
            ->method('url')
            ->will($this->returnValue('https://omp.publicknowledgeproject.org/index.php/press/catalog/book/3'));

        $mockContext = $this->getMockBuilder(Press::class)->getMock();

        $mockRequest = $this->getMockBuilder(PKPRequest::class)
            ->setMethods(['getDispatcher', 'getContext'])
            ->getMock();
        $mockRequest->expects($this->any())
            ->method('getDispatcher')
            ->will($this->returnValue($mockDispatcher));
        $mockRequest->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($mockContext));

        $factory = new ThothBookFactory();
        $thothWork = $factory->createFromSubmission($mockSubmission, $mockRequest);

        $this->assertEquals(new ThothWork([
            'workType' => ThothWork::WORK_TYPE_MONOGRAPH,
            'workStatus' => ThothWork::WORK_STATUS_ACTIVE,
            'fullTitle' => 'My book title: My book subtitle',
            'title' => 'My book title',
            'subtitle' => 'My book subtitle',
            'edition' => 1,
            'publicationDate' => '2020-01-01',
            'doi' => 'https://doi.org/10.12345/0101010101',
            'license' => 'https://creativecommons.org/licenses/by-nc/4.0/',
            'copyrightHolder' => 'Public Knowledge Press',
            'landingPage' => 'https://omp.publicknowledgeproject.org/index.php/press/catalog/book/3',
            'coverUrl' => 'https://omp.publicknowledgeproject.org/templates/images/book-default.png',
            'longAbstract' => 'This is my book abstract',
        ]), $thothWork);
    }
}
