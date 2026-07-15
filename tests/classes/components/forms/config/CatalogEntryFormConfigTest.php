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

namespace APP\plugins\generic\thoth\tests\classes\components\forms\config;

require_once(__DIR__ . '/../../../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\components\forms\config\CatalogEntryFormConfig;
use PKP\components\forms\FieldOptions;
use PKP\tests\PKPTestCase;

class CatalogEntryFormConfigTest extends PKPTestCase
{
    public function testAddsFrontcoverUploadFieldAfterCoverImage(): void
    {
        $form = $this->getCatalogEntryForm();
        $config = $this->getConfig(true, true);

        $config->addConfig('Form::config::before', $form);

        $field = $form->fields['thothUploadFrontcover'];
        $this->assertInstanceOf(FieldOptions::class, $field);
        $this->assertSame(['after', 'coverImage'], $form->positions['thothUploadFrontcover']);
        $this->assertTrue($field->value);
        $this->assertFalse($field->options[0]['disabled'] ?? false);
    }

    public function testDisablesFrontcoverUploadFieldWithoutCdnWritePermission(): void
    {
        $form = $this->getCatalogEntryForm();
        $config = $this->getConfig(false, false);

        $config->addConfig('Form::config::before', $form);

        $field = $form->fields['thothUploadFrontcover'];
        $this->assertInstanceOf(FieldOptions::class, $field);
        $this->assertFalse($field->value);
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

            protected function canUploadFiles(): bool
            {
                return $this->canUploadFiles;
            }
        };
    }
}
