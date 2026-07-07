<?php

/**
 * @file plugins/generic/thoth/classes/config/ThothSettings.inc.php
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

import('plugins.generic.thoth.classes.encryption.DataEncryption');

class ThothSettings
{
    private $pluginSettingsDao;
    private $contextId;
    private $encryption;

    public function __construct($pluginSettingsDao = null, $contextId = null, $encryption = null)
    {
        $this->pluginSettingsDao = $pluginSettingsDao;
        $this->contextId = $contextId;
        $this->encryption = $encryption;
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

    private function getContextId()
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
            return $this->getEncryption()->decryptString($token);
        } catch (Exception $exception) {
            return '';
        }
    }

    private function getEncryption()
    {
        if ($this->encryption !== null) {
            return $this->encryption;
        }

        return new DataEncryption();
    }
}
