<?php

/**
 * @file plugins/generic/thoth/tests/locale/FeatureVideoLocaleTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

namespace APP\plugins\generic\thoth\tests\locale;

use PHPUnit\Framework\TestCase;

class FeatureVideoLocaleTest extends TestCase
{
    /**
     * @dataProvider localeProvider
     */
    public function testFeatureVideoKeysAreTranslated(string $locale): void
    {
        $contents = file_get_contents(__DIR__ . '/../../locale/' . $locale . '/locale.po');

        foreach ([
            'plugins.generic.thoth.featureVideo',
            'plugins.generic.thoth.featureVideo.file',
            'plugins.generic.thoth.featureVideo.invalidFile',
        ] as $key) {
            $this->assertStringContainsString('msgid "' . $key . '"', $contents);
        }
    }

    public static function localeProvider(): array
    {
        return [['en_US'], ['es_ES'], ['it_IT'], ['pt_BR']];
    }
}
