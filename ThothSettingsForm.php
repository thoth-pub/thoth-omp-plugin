<?php

/**
 * @file plugins/generic/thoth/ThothSettingsForm.php
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

namespace APP\plugins\generic\thoth;

use APP\template\TemplateManager;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Crypt;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorCustom;
use PKP\form\validation\FormValidatorPost;
use ThothApi\Exception\QueryException;
use ThothApi\GraphQL\Client;

require_once(dirname(__FILE__) . '/vendor/autoload.php');

class ThothSettingsForm extends Form
{
    private const SETTINGS = [
        'email',
        'password',
        'customThothApi',
        'customThothApiUrl',
    ];

    public function __construct(
        private ThothPlugin $plugin,
        private int $contextId
    ) {
        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));

        $this->addCheck(new FormValidatorCustom(
            $this,
            'password',
            'required',
            'plugins.generic.thoth.settings.invalidCredentials',
            fn ($password) => $this->validateCredentials(trim($this->getData('email')), $password)
        ));

        $this->addCheck(new FormValidatorCustom(
            $this,
            'customThothApiUrl',
            'optional',
            'validator.url',
            function ($customThothApiUrl) {
                $validator = new \PKP\validation\ValidatorUrl();
                return $validator->isValid($customThothApiUrl);
            }
        ));
    }

    public function initData(): void
    {
        foreach (self::SETTINGS as $setting) {
            $value = $this->plugin->getSetting($this->contextId, $setting);
            $this->setData(
                $setting,
                $setting === 'password' && $value ? Crypt::decrypt($value) : $value
            );
        }
    }

    public function readInputData(): void
    {
        $this->readUserVars(self::SETTINGS);
    }

    public function fetch($request, $template = null, $display = false): string
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs): void
    {
        $this->setData('password', Crypt::encrypt($this->getData('password')));

        foreach (self::SETTINGS as $setting) {
            $this->plugin->updateSetting($this->contextId, $setting, trim($this->getData($setting)), 'string');
        }

        parent::execute(...$functionArgs);
    }

    private function validateCredentials(string $email, string $password): bool
    {
        $httpConfig = [];
        if ($this->getData('customThothApi') && $this->getData('customThothApiUrl')) {
            $httpConfig['base_uri'] = trim($this->getData('customThothApiUrl'));
        }

        try {
            (new Client($httpConfig))->login($email, $password);
            return true;
        } catch (QueryException) {
            return false;
        } catch (ConnectException) {
            return false;
        }
    }
}
