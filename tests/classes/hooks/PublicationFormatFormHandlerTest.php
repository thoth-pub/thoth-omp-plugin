<?php

/**
 * @file plugins/generic/thoth/tests/classes/hooks/PublicationFormatFormHandlerTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationFormatFormHandlerTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see PublicationFormatFormHandler
 *
 * @brief Test class for the PublicationFormatFormHandler class
 */

namespace APP\plugins\generic\thoth\tests\classes\hooks;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\hooks\PublicationFormatFormHandler;
use PKP\plugins\GenericPlugin;
use PKP\tests\PKPTestCase;

class PublicationFormatFormHandlerTest extends PKPTestCase
{
    public function testInvalidAccessibilityReportUrlAddsFormError(): void
    {
        $form = new class () {
            public array $errors = [];

            public function getData($key)
            {
                return $key === 'accessibilityReportUrl' ? 'not-a-url' : null;
            }

            public function addError($field, $message): void
            {
                $this->errors[$field] = $message;
            }
        };
        $handler = new PublicationFormatFormHandler($this->createMock(GenericPlugin::class));

        $handler->validateAccessibilityFields('publicationformatform::validate', [$form, null]);

        $this->assertArrayHasKey('accessibilityReportUrl', $form->errors);
    }

    public function testAccessibilityFieldNamesAreRegisteredForPublicationFormatSettings(): void
    {
        $fieldNames = ['pub-id::publisher-id'];
        $handler = new PublicationFormatFormHandler($this->createMock(GenericPlugin::class));

        $handler->addAccessibilityFieldNames(
            'publicationformatdao::getAdditionalFieldNames',
            new \stdClass(),
            $fieldNames
        );

        $this->assertSame(
            [
                'pub-id::publisher-id',
                'accessibilityStandard',
                'accessibilityAdditionalStandard',
                'accessibilityException',
                'accessibilityReportUrl',
            ],
            $fieldNames
        );
    }
}
