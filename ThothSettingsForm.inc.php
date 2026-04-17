<?php

/**
 * @file plugins/generic/thoth/ThothSettingsForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSettingsForm
 * @ingroup plugins_generic_thoth
 *
 * @brief Form for managers to modify Thoth plugin settings
 */

use ThothApi\Exception\QueryException;
use ThothApi\GraphQL\Client;

import('lib.pkp.classes.form.Form');
import('plugins.generic.thoth.classes.encryption.DataEncryption');

class ThothSettingsForm extends Form
{
    private $contextId;

    private $plugin;

    private const SETTINGS = [
        'token',
        'customThothApi',
        'customThothApiUrl',
    ];

    public function __construct($plugin, $contextId)
    {
        $this->contextId = $contextId;
        $this->plugin = $plugin;

        $encryption = new DataEncryption();
        $template = $encryption->secretConfigExists() ? 'settingsForm.tpl' : 'tokenError.tpl';
        parent::__construct($plugin->getTemplateResource($template));

        $form = $this;
        $this->addCheck(new FormValidatorCustom(
            $this,
            'customThothApiUrl',
            'required',
            'plugins.generic.thoth.settings.customThothApiUrl.required',
            function ($customThothApiUrl) {
                if (!$this->getData('customThothApi')) {
                    return true;
                }
                return !empty(trim($customThothApiUrl));
            }
        ));

        $this->addCheck(new FormValidatorCustom(
            $this,
            'customThothApiUrl',
            'optional',
            'plugins.generic.thoth.settings.customThothApiUrl.invalid',
            function ($customThothApiUrl) {
                if (!$this->getData('customThothApi') || !trim($customThothApiUrl)) {
                    return true;
                }
                return filter_var(trim($customThothApiUrl), FILTER_VALIDATE_URL) !== false;
            }
        ));

        $this->addCheck(new FormValidatorCustom(
            $this,
            'customThothApiUrl',
            'optional',
            'plugins.generic.thoth.settings.customThothApiUrl.unreachable',
            function ($customThothApiUrl) {
                if (!$this->getData('customThothApi')) {
                    return true;
                }
                return $this->validateCustomThothApiUrl(trim($customThothApiUrl));
            }
        ));

        $this->addCheck(new FormValidatorCustom(
            $this,
            'token',
            'required',
            'plugins.generic.thoth.settings.invalidCredentials',
            function ($token) use ($form) {
                $httpConfig = [];
                if ($this->getData('customThothApi') && $this->getData('customThothApiUrl')) {
                    $httpConfig['base_uri'] = trim($this->getData('customThothApiUrl'));
                }

                $client = new Client($httpConfig);

                try {
                    $client->setToken(trim($token))->me();
                } catch (QueryException $e) {
                    return false;
                }
                return true;
            }
        ));

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    public function initData()
    {
        $encryption = new DataEncryption();

        foreach (self::SETTINGS as $setting) {
            if ($setting == 'token') {
                $token = $this->plugin->getSetting($this->contextId, $setting);
                if ($encryption->secretConfigExists() && $token) {
                    try {
                        $this->_data[$setting] = $encryption->decryptString($token);
                    } catch (Exception $e) {
                        $this->_data[$setting] = '';
                    }
                } else {
                    $this->_data[$setting] = null;
                }
                continue;
            }
            $this->_data[$setting] = $this->plugin->getSetting($this->contextId, $setting);
        }
    }

    public function readInputData()
    {
        $this->readUserVars(self::SETTINGS);
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        $this->encryptToken();
        foreach (self::SETTINGS as $setting) {
            $this->plugin->updateSetting($this->contextId, $setting, trim($this->getData($setting)), 'string');
        }
        parent::execute(...$functionArgs);
    }

    private function encryptToken()
    {
        $encryption = new DataEncryption();
        $token = trim($this->getData('token'));

        if (!$encryption->textIsEncrypted($token)) {
            $encryptedToken = $encryption->encryptString($token);
            $this->setData('token', $encryptedToken);
        }
    }

    private function validateCustomThothApiUrl($customThothApiUrl)
    {
        if (!$customThothApiUrl) {
            return false;
        }

        try {
            (new Client(['base_uri' => $customThothApiUrl]))->publisherCount();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
