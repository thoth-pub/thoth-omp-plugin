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

use APP\plugins\generic\thoth\classes\APIKeyEncryption;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorCustom;
use PKP\form\validation\FormValidatorPost;
use ThothApi\Exception\QueryException;
use ThothApi\GraphQL\Client;

class ThothSettingsForm extends Form
{
    private $contextId;

    private $plugin;

    private const SETTINGS = [
        'email',
        'password',
        'testEnvironment',
    ];

    public function __construct($plugin, $contextId)
    {
        $this->contextId = $contextId;
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
                $testEnvironment = $this->getData('testEnvironment');
                $httpConfig = [];
                if ($testEnvironment) {
                    $httpConfig['base_uri'] = 'http://localhost:8000/';
                }

                $client = new Client($httpConfig);

                try {
                    $client->login($email, $password);
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
            if ($setting == 'password') {
                $password = $this->plugin->getSetting($this->contextId, $setting);
                $this->_data[$setting] = (APIKeyEncryption::secretConfigExists() && $password) ?
                    APIKeyEncryption::decryptString($password) :
                    null;
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
