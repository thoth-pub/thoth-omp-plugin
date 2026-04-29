<?php

/**
 * @file plugins/generic/thoth/tests/classes/formModifiers/PublicationFormatFormModifierTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationFormatFormModifierTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see PublicationFormatFormModifier
 *
 * @brief Test class for the PublicationFormatFormModifier class
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.formModifiers.PublicationFormatFormModifier');

class PublicationFormatFormModifierTest extends PKPTestCase
{
    public function testInvalidAccessibilityReportUrlAddsFormError()
    {
        $form = new class () {
            public $errors = [];

            public function getData($key)
            {
                return $key === 'accessibilityReportUrl' ? 'not-a-url' : null;
            }

            public function addError($field, $message)
            {
                $this->errors[$field] = $message;
            }
        };
        $modifier = new PublicationFormatFormModifier($this->createMock(GenericPlugin::class));

        $modifier->handleFormValidate('publicationformatform::validate', [$form, null]);

        $this->assertArrayHasKey('accessibilityReportUrl', $form->errors);
    }

    public function testAccessibilityFieldNamesAreRegisteredForPublicationFormatSettings()
    {
        $fieldNames = ['pub-id::publisher-id'];
        $args = [new stdClass(), &$fieldNames];
        $modifier = new PublicationFormatFormModifier($this->createMock(GenericPlugin::class));

        $modifier->addAccessibilityFieldNames(
            'publicationformatdao::getAdditionalFieldNames',
            $args
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
