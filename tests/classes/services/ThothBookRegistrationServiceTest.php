<?php

/**
 * @file plugins/generic/thoth/tests/classes/services/ThothBookRegistrationServiceTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookRegistrationServiceTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Test class for the ThothBookRegistrationService class
 */

namespace APP\plugins\generic\thoth\tests\classes\services;

require_once __DIR__ . '/../../../vendor/autoload.php';

use APP\plugins\generic\thoth\classes\exceptions\ThothRegistrationException;
use APP\plugins\generic\thoth\classes\factories\ThothBookFactory;
use APP\plugins\generic\thoth\classes\repositories\ThothBookRepository;
use APP\plugins\generic\thoth\classes\services\ThothAbstractService;
use APP\plugins\generic\thoth\classes\services\ThothBookRegistrationService;
use APP\plugins\generic\thoth\classes\services\ThothContributionService;
use APP\plugins\generic\thoth\classes\services\ThothFrontcoverService;
use APP\plugins\generic\thoth\classes\services\ThothLanguageService;
use APP\plugins\generic\thoth\classes\services\ThothPublicationService;
use APP\plugins\generic\thoth\classes\services\ThothReferenceService;
use APP\plugins\generic\thoth\classes\services\ThothSubjectService;
use APP\plugins\generic\thoth\classes\services\ThothTitleService;
use APP\plugins\generic\thoth\classes\services\ThothWorkRelationService;
use APP\publication\Publication;
use APP\submission\Repository as SubmissionRepository;
use APP\submission\Submission;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PKP\tests\PKPTestCase;
use RuntimeException;
use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Inputs\PatchWork;

class ThothBookRegistrationServiceTest extends PKPTestCase
{
    private Publication $publication;
    private Submission $submission;
    private array $steps;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publication = new Publication();
        $this->publication->setData('locale', 'en');
        $this->submission = new Submission();
        $this->steps = [];
    }

    public function testRegistersMetadataActivatesAndPersistsLink(): void
    {
        $service = $this->createService();

        $result = $service->register($this->publication, 'imprint', $this->submission);

        self::assertSame('work-id', $result->getWorkId());
        self::assertSame(['cover-warning'], $result->getWarnings());
        self::assertSame(['create', 'metadata', 'activate', 'persist'], $this->steps);
    }

    public function testForthcomingBookDoesNotNeedActivation(): void
    {
        $service = $this->createService(status: WorkStatus::FORTHCOMING);

        $service->register($this->publication, 'imprint', $this->submission);

        self::assertSame(['create', 'metadata', 'persist'], $this->steps);
    }

    #[DataProvider('failureStages')]
    public function testCompensatesFailedRegistration(string $stage, array $expectedSteps): void
    {
        $failure = new RuntimeException('Registration failed');
        $service = $this->createService($stage, $failure);

        try {
            $service->register($this->publication, 'imprint', $this->submission);
            self::fail('The failure must be propagated.');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception);
            self::assertSame($expectedSteps, $this->steps);
            self::assertNull($this->publication->getData('thothBookId'));
            self::assertNull($this->submission->getData('thothWorkId'));
        }
    }

    public static function failureStages(): array
    {
        return [
            'creation' => ['create', ['create']],
            'metadata' => ['metadata', ['create', 'metadata', 'delete']],
            'activation' => ['activate', ['create', 'metadata', 'activate', 'delete']],
            'local persistence' => ['persist', ['create', 'metadata', 'activate', 'persist', 'delete']],
        ];
    }

    public function testKeepsOriginalFailureAndRemoteIdWhenCompensationFails(): void
    {
        $failure = new RuntimeException('Local persistence failed');
        $cleanupFailure = new RuntimeException('Remote deletion failed');
        $service = $this->createService('persist', $failure, $cleanupFailure);

        try {
            $service->register($this->publication, 'imprint', $this->submission);
            self::fail('Incomplete compensation must be reported.');
        } catch (ThothRegistrationException $exception) {
            self::assertSame($failure, $exception->getPrevious());
            self::assertSame($cleanupFailure, $exception->getCompensationFailure());
            self::assertSame('work-id', $exception->getWorkId());
        }
    }

    public function testRollsBackLocalWritesWhenPersistenceFails(): void
    {
        $connection = DB::connection();
        $connection->statement('CREATE TEMPORARY TABLE thoth_registration_test_work_links (work_id TEXT) ENGINE=InnoDB');
        $failure = new RuntimeException('Persistence failed after writing');
        $service = $this->createService(
            'persist',
            $failure,
            connection: $connection,
            persist: fn ($data) => $connection->table('thoth_registration_test_work_links')->insert(['work_id' => $data['thothWorkId']])
        );

        try {
            $service->register($this->publication, 'imprint', $this->submission);
            self::fail('The persistence failure must be propagated.');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception);
            self::assertSame(0, $connection->table('thoth_registration_test_work_links')->count());
            self::assertSame(['create', 'metadata', 'activate', 'persist', 'delete'], $this->steps);
        } finally {
            $connection->statement('DROP TEMPORARY TABLE thoth_registration_test_work_links');
        }
    }

    private function createService(
        ?string $failedStage = null,
        ?RuntimeException $failure = null,
        ?RuntimeException $cleanupFailure = null,
        string $status = WorkStatus::ACTIVE,
        ?ConnectionInterface $connection = null,
        ?\Closure $persist = null
    ): ThothBookRegistrationService {
        $recordStep = function (string $step) use ($failedStage, $failure): void {
            $this->steps[] = $step;
            if ($step === $failedStage) {
                throw $failure;
            }
        };
        $factory = $this->createMock(ThothBookFactory::class);
        $factory->method('createFromPublication')->willReturn(new PatchWork(['workStatus' => $status]));
        $repository = $this->createMock(ThothBookRepository::class);
        $repository->method('add')->willReturnCallback(function ($work) use ($recordStep) {
            self::assertSame(WorkStatus::FORTHCOMING, $work->getWorkStatus());
            $recordStep('create');
            return 'work-id';
        });
        $repository->method('edit')->willReturnCallback(function ($work) use ($recordStep) {
            self::assertSame(WorkStatus::ACTIVE, $work->getWorkStatus());
            self::assertSame('work-id', $work->getWorkId());
            $recordStep('activate');
        });
        $repository->method('delete')->willReturnCallback(function ($id) use ($cleanupFailure) {
            self::assertSame('work-id', $id);
            $this->steps[] = 'delete';
            if ($cleanupFailure) {
                throw $cleanupFailure;
            }
        });
        $abstracts = $this->createMock(ThothAbstractService::class);
        $abstracts->method('registerByPublication')->willReturnCallback(
            function ($publication, $id, $locale) use ($recordStep) {
                self::assertSame($this->publication, $publication);
                self::assertSame('work-id', $id);
                self::assertSame('en', $locale);
                $recordStep('metadata');
            }
        );
        $frontcover = $this->createMock(ThothFrontcoverService::class);
        $frontcover->method('sync')->willReturn('cover-warning');
        $submissions = $this->createMock(SubmissionRepository::class);
        $submissions->method('edit')->willReturnCallback(function ($submission, $data) use ($recordStep, $persist) {
            self::assertSame($this->submission, $submission);
            self::assertSame(['thothWorkId' => 'work-id'], $data);
            if ($persist) {
                $persist($data);
            }
            $recordStep('persist');
        });
        if ($connection === null) {
            $connection = $this->createMock(ConnectionInterface::class);
            $connection->method('transaction')->willReturnCallback(fn ($callback) => $callback());
        }

        $metadataServices = [];
        foreach ([
            ThothContributionService::class,
            ThothLanguageService::class,
            ThothPublicationService::class,
            ThothReferenceService::class,
            ThothSubjectService::class,
            ThothWorkRelationService::class,
        ] as $class) {
            $metadataServices[$class] = $this->createMock($class);
            $metadataServices[$class]->expects(
                in_array($failedStage, ['create', 'metadata'], true) ? $this->never() : $this->once()
            )->method('registerByPublication');
        }

        return new ThothBookRegistrationService(
            $factory,
            $repository,
            $abstracts,
            $metadataServices[ThothContributionService::class],
            $metadataServices[ThothLanguageService::class],
            $metadataServices[ThothPublicationService::class],
            $metadataServices[ThothReferenceService::class],
            $metadataServices[ThothSubjectService::class],
            $this->createMock(ThothTitleService::class),
            $metadataServices[ThothWorkRelationService::class],
            $this->createMock(\APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource::class),
            $frontcover,
            $submissions,
            $connection
        );
    }
}
