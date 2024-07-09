<?php

/**
 * @file plugins/generic/thoth/ThothSettingsForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSettingsForm
 * @ingroup plugins_generic_thoth
 *
 * @brief Form for managers to modify Thoth plugin settings
 */

import('lib.pkp.classes.form.Form');
import('plugins.generic.thoth.lib.APIKeyEncryption.APIKeyEncryption');
import('plugins.generic.thoth.thoth.ThothClient');

class ThothSettingsForm extends Form
{
    private $contextId;

    private $plugin;

    private const SETTINGS = [
        'apiUrl',
        'imprintId',
        'email',
        'password'
    ];

    public function __construct($plugin, $journalId)
    {
        $this->journalId = $journalId;
        $this->plugin = $plugin;

        $template = APIKeyEncryption::secretConfigExists() ? 'settingsForm.tpl' : 'tokenError.tpl';
        parent::__construct($plugin->getTemplateResource($template));

        $form = $this;
        $this->addCheck(new FormValidatorCustom(
            $this,
            'password',
            'required',
            'plugins.generic.thoth.settings.invalidCredentials',
            function ($password) use ($form) {
                $email = trim($this->getData('email'));
                $apiUrl = trim($this->getData('apiUrl'));
                $thothClient = $apiUrl ? new ThothClient($apiUrl) : new ThothClient();
                try {
                    $thothClient->login(
                        $email,
                        $password
                    );
                } catch (ThothException $e) {
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
            if ($setting == 'password') {
                $password = $this->plugin->getSetting($this->contextId, $setting);
                $this->_data[$setting] = APIKeyEncryption::decryptString($password);
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
        foreach (self::SETTINGS as $setting) {
            if ($setting == 'password') {
                $encryptedPassword = APIKeyEncryption::encryptString(trim($this->getData($setting)));
                $this->plugin->updateSetting($this->contextId, $setting, $encryptedPassword, 'string');
                continue;
            }
            $this->plugin->updateSetting($this->contextId, $setting, trim($this->getData($setting)), 'string');
        }
        parent::execute(...$functionArgs);
    }
}
