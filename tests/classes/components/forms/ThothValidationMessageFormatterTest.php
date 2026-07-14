<?php

require_once(__DIR__ . '/../../../../vendor/autoload.php');

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.components.forms.ThothValidationMessageFormatter');

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
