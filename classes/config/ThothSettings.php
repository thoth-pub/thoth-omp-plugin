<?php

/**
 * @file plugins/generic/thoth/classes/config/ThothSettings.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSettings
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Reads Thoth plugin settings for the current context
 */

namespace APP\plugins\generic\thoth\classes\config;

use APP\core\Application;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use PKP\db\DAORegistry;

class ThothSettings
{
    private $pluginSettingsDao;
    private ?int $contextId;
    private $decryptToken;

    public function __construct($pluginSettingsDao = null, ?int $contextId = null, $decryptToken = null)
    {
        $this->pluginSettingsDao = $pluginSettingsDao;
        $this->contextId = $contextId;
        $this->decryptToken = $decryptToken;
    }

    public function toArray(): array
    {
        $pluginSettingsDao = $this->getPluginSettingsDao();
        $contextId = $this->getContextId();
        $token = $pluginSettingsDao->getSetting($contextId, 'ThothPlugin', 'token') ?? '';

        return [
            'customThothApi' => $pluginSettingsDao->getSetting($contextId, 'ThothPlugin', 'customThothApi'),
            'customThothApiUrl' => $pluginSettingsDao->getSetting($contextId, 'ThothPlugin', 'customThothApiUrl'),
            'token' => $this->decryptToken($token)
        ];
    }

    private function getPluginSettingsDao()
    {
        if ($this->pluginSettingsDao !== null) {
            return $this->pluginSettingsDao;
        }

        return DAORegistry::getDAO('PluginSettingsDAO');
    }

    private function getContextId(): int
    {
        if ($this->contextId !== null) {
            return $this->contextId;
        }

        return Application::get()->getRequest()->getContext()->getId();
    }

    private function decryptToken(string $token): string
    {
        if (!$token) {
            return '';
        }

        try {
            if ($this->decryptToken !== null) {
                return call_user_func($this->decryptToken, $token);
            }

            return Crypt::decrypt($token);
        } catch (DecryptException $exception) {
            return '';
        }
    }
}
