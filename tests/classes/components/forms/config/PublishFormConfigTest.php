<?php

/**
 * @file plugins/generic/thoth/tests/classes/components/forms/config/PublishFormConfigTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublishFormConfigTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see PublishFormConfig
 *
 * @brief Test class for the PublishFormConfig class
 */

namespace APP\plugins\generic\thoth\tests\classes\components\forms\config;

require_once(__DIR__ . '/../../../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\components\forms\config\PublishFormConfig;
use Exception;
use PKP\components\forms\FieldHTML;
use PKP\tests\PKPTestCase;

class PublishFormConfigTest extends PKPTestCase
{
    public function testConnectionExceptionShowsWarningWithoutAbortingFormConfig(): void
    {
        $form = new class () {
            public string $id = 'publish';
            public array $errors = [];
            public array $fields = [];
            public $publication;

            public function __construct()
            {
                $this->publication = new class () {
                    public function getData($key)
                    {
                        return $key === 'submissionId' ? 1 : null;
                    }
                };
            }

            public function addField($field)
            {
                $this->fields[] = $field;
                return $this;
            }
        };

        $config = new class () extends PublishFormConfig {
            protected function getSubmission($publication)
            {
                return new class () {
                    public function getData($key)
                    {
                        return $key === 'workType' ? null : null;
                    }
                };
            }

            protected function validatePublication($publication): array
            {
                throw new Exception('Thoth API unavailable');
            }
        };

        $result = $config->addConfig('Form::config::before', $form);

        self::assertFalse($result);
        self::assertCount(1, $form->fields);
        self::assertInstanceOf(FieldHTML::class, $form->fields[0]);
    }
}
