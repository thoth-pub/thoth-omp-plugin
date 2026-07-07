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

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.config.ThothSettings');

class ThothSettingsTest extends PKPTestCase
{
    public function testReturnsSettingsWithDecryptedToken()
    {
        $settings = new ThothSettings(
            $this->getPluginSettingsDao([
                'customThothApi' => true,
                'customThothApiUrl' => 'https://api.example.test',
                'token' => 'encrypted-token',
            ]),
            2,
            $this->getEncryption('decrypted-token')
        );

        $this->assertSame([
            'customThothApi' => true,
            'customThothApiUrl' => 'https://api.example.test',
            'token' => 'decrypted-token',
        ], $settings->toArray());
    }

    public function testReturnsEmptyTokenWhenTokenCannotBeDecrypted()
    {
        $settings = new ThothSettings(
            $this->getPluginSettingsDao(['token' => 'invalid-token']),
            2,
            $this->getEncryption(null, true)
        );

        $this->assertSame('', $settings->toArray()['token']);
    }

    private function getPluginSettingsDao(array $settings)
    {
        return new class ($settings) {
            private $settings;

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

    private function getEncryption($token, bool $throws = false)
    {
        return new class ($token, $throws) {
            private $token;
            private $throws;

            public function __construct($token, bool $throws)
            {
                $this->token = $token;
                $this->throws = $throws;
            }

            public function decryptString($encryptedText)
            {
                if ($this->throws) {
                    throw new Exception();
                }

                return $this->token;
            }
        };
    }
}
