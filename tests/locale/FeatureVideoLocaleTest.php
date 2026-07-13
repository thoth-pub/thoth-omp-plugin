<?php

import('lib.pkp.tests.PKPTestCase');

class FeatureVideoLocaleTest extends PKPTestCase
{
    /** @dataProvider localeProvider */
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

    public function localeProvider(): array
    {
        return [['en'], ['es'], ['it'], ['pt_BR']];
    }
}
