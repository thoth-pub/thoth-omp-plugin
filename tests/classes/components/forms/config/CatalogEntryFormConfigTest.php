<?php

/**
 * @file plugins/generic/thoth/tests/classes/components/forms/config/CatalogEntryFormConfigTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CatalogEntryFormConfigTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see CatalogEntryFormConfig
 *
 * @brief Test class for the CatalogEntryFormConfig class
 */

use APP\publication\Repository as PublicationRepository;
use APP\submission\Repository as SubmissionRepository;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.components.forms.config.CatalogEntryFormConfig');

class CatalogEntryFormConfigTest extends PKPTestCase
{
    protected function getMockedContainerKeys(): array
    {
        return [...parent::getMockedContainerKeys(), PublicationRepository::class, SubmissionRepository::class];
    }

    public function testGetsPublicationThroughRepository(): void
    {
        $publication = Mockery::mock(\APP\publication\Publication::class);
        $publicationRepository = Mockery::mock(app(PublicationRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->once()
            ->with(1)
            ->andReturn($publication)
            ->getMock();
        app()->instance(PublicationRepository::class, $publicationRepository);

        $config = new class () extends CatalogEntryFormConfig {
            public function getPublicationById($publicationId)
            {
                return $this->getPublication($publicationId);
            }
        };

        $this->assertSame($publication, $config->getPublicationById(1));
    }

    public function testGetsSubmissionThroughRepositoryBeforeCheckingCdnPermission(): void
    {
        $submission = Mockery::mock(\APP\submission\Submission::class)
            ->shouldReceive('getData')
            ->once()
            ->with('contextId')
            ->andReturn(2)
            ->getMock();
        $submissionRepository = Mockery::mock(app(SubmissionRepository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->once()
            ->with(1)
            ->andReturn($submission)
            ->getMock();
        app()->instance(SubmissionRepository::class, $submissionRepository);

        $publication = Mockery::mock(\APP\publication\Publication::class)
            ->shouldReceive('getData')
            ->once()
            ->with('submissionId')
            ->andReturn(1)
            ->getMock();
        $config = new class () extends CatalogEntryFormConfig {
            public ?int $contextId = null;

            public function canUploadFilesForPublication($publication): bool
            {
                return $this->canUploadFiles($publication);
            }

            protected function hasCdnWritePermission($contextId): bool
            {
                $this->contextId = $contextId;
                return true;
            }
        };

        $config->canUploadFilesForPublication($publication);

        $this->assertSame(2, $config->contextId);
    }

    public function testAddsFrontcoverUploadFieldAfterCoverImage(): void
    {
        $form = $this->getCatalogEntryForm();
        $config = $this->getConfig(true, true);

        $config->addConfig('Form::config::before', $form);

        $field = $form->fields['thothUploadFrontcover'];
        $this->assertInstanceOf(\PKP\components\forms\FieldOptions::class, $field);
        $this->assertSame(['after', 'coverImage'], $form->positions['thothUploadFrontcover']);
        $this->assertSame([true], $field->value);
        $this->assertFalse($field->options[0]['disabled'] ?? false);
    }

    public function testDisablesFrontcoverUploadFieldWithoutCdnWritePermission(): void
    {
        $form = $this->getCatalogEntryForm();
        $config = $this->getConfig(false, false);

        $config->addConfig('Form::config::before', $form);

        $field = $form->fields['thothUploadFrontcover'];
        $this->assertInstanceOf(\PKP\components\forms\FieldOptions::class, $field);
        $this->assertSame([], $field->value);
        $this->assertTrue($field->options[0]['disabled']);
    }

    private function getCatalogEntryForm(): object
    {
        return new class () {
            public string $id = 'catalogEntry';
            public array $errors = [];
            public string $action = 'https://example.test/publications/1';
            public array $fields = [];
            public array $positions = [];

            public function addField($field, $position = [])
            {
                $this->fields[$field->name] = $field;
                $this->positions[$field->name] = $position;
                return $this;
            }
        };
    }

    private function getConfig(bool $canUploadFiles, bool $uploadFrontcover): CatalogEntryFormConfig
    {
        return new class ($canUploadFiles, $uploadFrontcover) extends CatalogEntryFormConfig {
            private bool $canUploadFiles;
            private bool $uploadFrontcover;

            public function __construct(bool $canUploadFiles, bool $uploadFrontcover)
            {
                $this->canUploadFiles = $canUploadFiles;
                $this->uploadFrontcover = $uploadFrontcover;
            }

            protected function getPublication($publicationId)
            {
                return new class ($this->uploadFrontcover) {
                    private bool $uploadFrontcover;

                    public function __construct(bool $uploadFrontcover)
                    {
                        $this->uploadFrontcover = $uploadFrontcover;
                    }

                    public function getData($key)
                    {
                        $values = [
                            'place' => null,
                            'pageCount' => null,
                            'imageCount' => null,
                            'thothUploadFrontcover' => $this->uploadFrontcover,
                        ];

                        return $values[$key] ?? null;
                    }
                };
            }

            protected function canUploadFiles($publication): bool
            {
                return $this->canUploadFiles;
            }
        };
    }
}
