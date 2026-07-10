<?php

/**
 * @file plugins/generic/thoth/tests/classes/components/forms/ThothValidationMessageFormatterTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

namespace APP\plugins\generic\thoth\tests\classes\components\forms;

require_once(__DIR__ . '/../../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\components\forms\ThothValidationMessageFormatter;
use PKP\tests\PKPTestCase;

class ThothValidationMessageFormatterTest extends PKPTestCase
{
    public function testFormatWarningEscapesValidationMessages(): void
    {
        $html = ThothValidationMessageFormatter::formatWarning([
            'Invalid format <img src=x onerror=alert(1)>',
        ]);

        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }
}
