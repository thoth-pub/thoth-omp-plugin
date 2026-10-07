<?php

/**
 * @file plugins/generic/thoth/tests/classes/pkp/OmpBookMetadataSourceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OmpBookMetadataSourceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Tests OMP metadata retrieval for Thoth books
 */

namespace APP\plugins\generic\thoth\tests\classes\pkp;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\factories\ThothBookFactory;
use APP\press\Press;
use APP\press\PressDAO;
use APP\submission\Repository as SubmissionRepository;
use APP\submission\Submission;
use Mockery;
use PKP\core\Registry;
use PKP\db\DAORegistry;
use PKP\tests\PKPTestCase;
use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Enums\WorkType;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

class OmpBookMetadataSourceTest extends PKPTestCase
{
    protected array $mocks = [];
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

    private function setUpMockEnvironment(
        bool $uploadFrontcover = false,
        ?string $frontcoverUrl = null,
        bool $emptyOptionalMetadata = false
    ) {
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
                    ->shouldReceive('getData')
                    ->with('locale')
                    ->andReturn('en')
                    ->shouldReceive('_getContextLicenseFieldValue')
                    ->withAnyArgs()
                    ->andReturn('')
                    ->shouldReceive('getBestId')
                    ->withAnyArgs()
                    ->andReturn(3)
                    ->getMock()
            )
            ->getMock();
        app()->instance(SubmissionRepository::class, $submissionRepoMock);

        $mockContext = $this->getMockBuilder(Press::class)
            ->onlyMethods(['getPath'])
            ->getMock();
        $mockContext->expects($this->any())
            ->method('getPath')
            ->willReturn('press');

        $mockContextDao = $this->getMockBuilder(PressDAO::class)
            ->onlyMethods(['getById'])
            ->getMock();
        $mockContextDao->expects($this->any())
            ->method('getById')
            ->willReturn($mockContext);
        DAORegistry::registerDAO('PressDAO', $mockContextDao);

        $mockRequest = Mockery::mock(\APP\core\Request::class)
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
                    ->andReturn($emptyOptionalMetadata ? '' : 'https://doi.org/10.12345/0101010101')
                    ->getMock()
            )
            ->shouldReceive('getData')
            ->with('licenseUrl')
            ->andReturn($emptyOptionalMetadata ? '' : 'https://creativecommons.org/licenses/by-nc/4.0/')
            ->shouldReceive('getLocalizedData')
            ->with('copyrightHolder')
            ->andReturn($emptyOptionalMetadata ? '' : 'Public Knowledge Press')
            ->shouldReceive('getLocalizedCoverImageUrl')
            ->withAnyArgs()
            ->andReturn(
                $emptyOptionalMetadata ? '' : 'https://omp.publicknowledgeproject.org/templates/images/book-default.png'
            )
            ->shouldReceive('getData')
            ->with('thothUploadFrontcover')
            ->andReturn($uploadFrontcover)
            ->shouldReceive('getData')
            ->with('thothFrontcoverUrl')
            ->andReturn($frontcoverUrl)
            ->shouldReceive('getData')
            ->with('place')
            ->andReturn($emptyOptionalMetadata ? '' : 'Salvador, BR')
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
        $source = new \APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource(
            \APP\facades\Repo::submission(),
            \APP\facades\Repo::publication(),
            \APP\core\Application::getContextDAO(),
            \PKP\db\DAORegistry::getDAO('PublicationFormatDAO'),
            \APP\core\Application::get()->getRequest()
        );
        $thothWork = $factory->createFromPublication($mockPublication, $source->getBookContext($mockPublication));

        $this->assertEquals(new ThothWork([
            'workType' => WorkType::MONOGRAPH,
            'workStatus' => WorkStatus::ACTIVE,
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
        ]), $thothWork);
    }

    public function testCreateThothBookPreservesHostedFrontcoverUrl()
    {
        $frontcoverUrl = 'https://cdn.thoth.pub/frontcover.png';
        $this->setUpMockEnvironment(true, $frontcoverUrl);

        $factory = new ThothBookFactory();
        $source = new \APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource(
            \APP\facades\Repo::submission(),
            \APP\facades\Repo::publication(),
            \APP\core\Application::getContextDAO(),
            \PKP\db\DAORegistry::getDAO('PublicationFormatDAO'),
            \APP\core\Application::get()->getRequest()
        );
        $thothWork = $factory->createFromPublication(
            $this->mocks['publication'],
            $source->getBookContext($this->mocks['publication'])
        );

        $this->assertSame($frontcoverUrl, $thothWork->getCoverUrl());
    }

    public function testCreateThothBookUsesOmpCoverUrlWhenFrontcoverIsNotHosted()
    {
        $this->setUpMockEnvironment(true);

        $factory = new ThothBookFactory();
        $source = new \APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource(
            \APP\facades\Repo::submission(),
            \APP\facades\Repo::publication(),
            \APP\core\Application::getContextDAO(),
            \PKP\db\DAORegistry::getDAO('PublicationFormatDAO'),
            \APP\core\Application::get()->getRequest()
        );
        $thothWork = $factory->createFromPublication(
            $this->mocks['publication'],
            $source->getBookContext($this->mocks['publication'])
        );

        $this->assertSame(
            'https://omp.publicknowledgeproject.org/templates/images/book-default.png',
            $thothWork->getCoverUrl()
        );
    }

    public function testCreateThothBookOmitsEmptyOptionalMetadata()
    {
        $this->setUpMockEnvironment(false, null, true);

        $factory = new ThothBookFactory();
        $source = new \APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource(
            \APP\facades\Repo::submission(),
            \APP\facades\Repo::publication(),
            \APP\core\Application::getContextDAO(),
            \PKP\db\DAORegistry::getDAO('PublicationFormatDAO'),
            \APP\core\Application::get()->getRequest()
        );
        $thothWork = $factory->createFromPublication(
            $this->mocks['publication'],
            $source->getBookContext($this->mocks['publication'])
        );
        $data = $thothWork->getAllData();

        foreach (['doi', 'place', 'license', 'copyrightHolder', 'coverUrl'] as $fieldName) {
            $this->assertArrayNotHasKey($fieldName, $data);
        }
    }

    public function testGetWorkTypeBySubmissionWorkType()
    {
        $factory = new ThothBookFactory();
        $source = new \APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource(
            \APP\facades\Repo::submission(),
            \APP\facades\Repo::publication(),
            \APP\core\Application::getContextDAO(),
            \PKP\db\DAORegistry::getDAO('PublicationFormatDAO'),
            \APP\core\Application::get()->getRequest()
        );
        $workType = $factory->getWorkTypeBySubmissionWorkType(Submission::WORK_TYPE_AUTHORED_WORK);
        $this->assertEquals(WorkType::MONOGRAPH, $workType);

        $workType = $factory->getWorkTypeBySubmissionWorkType(Submission::WORK_TYPE_EDITED_VOLUME);
        $this->assertEquals(WorkType::EDITED_BOOK, $workType);
    }

    public function testGetWorkStatusByDatePublished()
    {
        $factory = new ThothBookFactory();
        $source = new \APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource(
            \APP\facades\Repo::submission(),
            \APP\facades\Repo::publication(),
            \APP\core\Application::getContextDAO(),
            \PKP\db\DAORegistry::getDAO('PublicationFormatDAO'),
            \APP\core\Application::get()->getRequest()
        );
        $workStatus = $factory->getWorkStatusByDatePublished('2020-01-01');
        $this->assertEquals(WorkStatus::ACTIVE, $workStatus);

        $workStatus = $factory->getWorkStatusByDatePublished('2050-12-12');
        $this->assertEquals(WorkStatus::FORTHCOMING, $workStatus);
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
        $source = new \APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource(
            \APP\facades\Repo::submission(),
            \APP\facades\Repo::publication(),
            \APP\core\Application::getContextDAO(),
            \PKP\db\DAORegistry::getDAO('PublicationFormatDAO'),
            \APP\core\Application::get()->getRequest()
        );
        $doi = $factory->getDoi($mockPublication);
        $this->assertEquals('https://doi.org/10.12345/1111122222', $doi);
    }

    public function testGetDoiFromPublicationFormat()
    {
        $this->setUpMockEnvironment(false, null, true);
        $code = new \APP\publicationFormat\IdentificationCode();
        $code->setCode('06');
        $code->setValue('10.12345/123456789');
        $codes = $this->createMock(\PKP\db\DAOResultFactory::class);
        $codes->method('toArray')->willReturn([$code]);
        $format = $this->createMock(\APP\publicationFormat\PublicationFormat::class);
        $format->method('getIdentificationCodes')->willReturn($codes);
        $formats = $this->createMock(\APP\publicationFormat\PublicationFormatDAO::class);
        $formats->method('getByPublicationId')->willReturn([$format]);
        $source = new \APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource(
            \APP\facades\Repo::submission(),
            \APP\facades\Repo::publication(),
            \APP\core\Application::getContextDAO(),
            $formats,
            \APP\core\Application::get()->getRequest()
        );
        $publication = $this->createMock(\APP\publication\Publication::class);
        $publication->method('getData')->willReturnMap([['submissionId', null, 3]]);
        $book = (new ThothBookFactory())->createFromPublication($publication, $source->getBookContext($publication));

        $this->assertSame('https://doi.org/10.12345/123456789', $book->getDoi());
    }
}
