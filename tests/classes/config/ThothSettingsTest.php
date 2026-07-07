<?php

/**
 * @file plugins/generic/thoth/tests/classes/config/ThothSettingsTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSettingsTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @see ThothSettings
 *
 * @brief Test class for the ThothSettings class
 */

namespace APP\plugins\generic\thoth\tests\classes\config;

use APP\plugins\generic\thoth\classes\config\ThothSettings;
use Illuminate\Contracts\Encryption\DecryptException;
use PKP\tests\PKPTestCase;

class ThothSettingsTest extends PKPTestCase
{
    public function testReturnsSettingsWithDecryptedToken(): void
    {
        $settings = new ThothSettings(
            $this->getPluginSettingsDao([
                'customThothApi' => true,
                'customThothApiUrl' => 'https://api.example.test',
                'token' => 'encrypted-token',
            ]),
            2,
            fn (string $token): string => "decrypted-{$token}"
        );

        $this->assertSame([
            'customThothApi' => true,
            'customThothApiUrl' => 'https://api.example.test',
            'token' => 'decrypted-encrypted-token',
        ], $settings->toArray());
    }

    public function testReturnsEmptyTokenWhenTokenCannotBeDecrypted(): void
    {
        $settings = new ThothSettings(
            $this->getPluginSettingsDao(['token' => 'invalid-token']),
            2,
            function (): void {
                throw new DecryptException();
            }
        );

        $this->assertSame('', $settings->toArray()['token']);
    }

    private function getPluginSettingsDao(array $settings)
    {
        return new class ($settings) {
            private array $settings;

            public function __construct(array $settings)
            {
                $this->settings = $settings;
            }

            public function getSetting($contextId, $pluginName, $settingName)
            {
                return $this->settings[$settingName] ?? null;
            }
        };
    }
}
