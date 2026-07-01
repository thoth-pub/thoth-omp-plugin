<?php

/**
 * @file plugins/generic/thoth/ThothSettingsForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSettingsForm
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Form for managers to modify Thoth plugin settings
 */

use APP\plugins\generic\thoth\classes\encryption\DataEncryption;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorCustom;
use PKP\form\validation\FormValidatorPost;
use ThothApi\Exception\QueryException;
use ThothApi\GraphQL\Client;

import('plugins.generic.thoth.classes.services.ThothMeCacheService');

class ThothSettingsForm extends Form
{
    private $contextId;

    private $plugin;

    private $encryption;

    private const SETTINGS = [
        'token',
        'customThothApi',
        'customThothApiUrl',
    ];

    public function __construct($plugin, $contextId)
    {
        $this->contextId = $contextId;
        $this->plugin = $plugin;
        $this->encryption = new DataEncryption();

        $template = $this->encryption->secretConfigExists() ? 'settingsForm.tpl' : 'tokenError.tpl';
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
        foreach (self::SETTINGS as $setting) {
            if ($setting == 'token') {
                $token = $this->plugin->getSetting($this->contextId, $setting);
                if ($this->encryption->secretConfigExists() && $token) {
                    try {
                        $this->_data[$setting] = $this->encryption->decryptString($token);
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
        (new ThothMeCacheService())->flush($this->contextId);
        parent::execute(...$functionArgs);
    }

    private function encryptToken()
    {
        $token = trim($this->getData('token'));

        if (!$this->encryption->textIsEncrypted($token)) {
            $encryptedToken = $this->encryption->encryptString($token);
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
