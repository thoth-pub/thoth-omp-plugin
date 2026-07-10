<?php

/**
 * @file plugins/generic/thoth/ThothSettingsForm.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSettingsForm
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Form for managers to modify Thoth plugin settings
 */

namespace APP\plugins\generic\thoth;

use APP\plugins\generic\thoth\classes\security\ThothApiUrlValidator;
use APP\template\TemplateManager;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Encryption\DecryptException;
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
        'token',
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
                if (!$this->getData('customThothApi')) {
                    return true;
                }

                return (new ThothApiUrlValidator())->isSafe(trim($customThothApiUrl));
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
            fn ($token) => $this->validateCredentials(trim($token))
        ));
    }

    public function initData(): void
    {
        foreach (self::SETTINGS as $setting) {
            $value = $this->plugin->getSetting($this->contextId, $setting);
            if ($setting === 'token' && $value) {
                try {
                    $value = Crypt::decrypt($value);
                } catch (DecryptException $exception) {
                    $value = '';
                }
            }
            $this->setData(
                $setting,
                $value
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
        $this->setData('token', Crypt::encrypt(trim($this->getData('token'))));

        foreach (self::SETTINGS as $setting) {
            $this->plugin->updateSetting($this->contextId, $setting, trim($this->getData($setting)), 'string');
        }

        parent::execute(...$functionArgs);
    }

    private function validateCredentials(string $token): bool
    {
        $httpConfig = [];
        if ($this->getData('customThothApi') && $this->getData('customThothApiUrl')) {
            $customThothApiUrl = trim($this->getData('customThothApiUrl'));
            if (!(new ThothApiUrlValidator())->isSafe($customThothApiUrl)) {
                return false;
            }
            $httpConfig['base_uri'] = $customThothApiUrl;
            $httpConfig['allow_redirects'] = false;
        }

        try {
            (new Client($httpConfig))->setToken($token)->me();
            return true;
        } catch (QueryException) {
            return false;
        } catch (GuzzleException) {
            return false;
        }
    }

    private function validateCustomThothApiUrl(string $customThothApiUrl): bool
    {
        if (!(new ThothApiUrlValidator())->isSafe($customThothApiUrl)) {
            return false;
        }

        try {
            (new Client([
                'base_uri' => $customThothApiUrl,
                'allow_redirects' => false,
            ]))->publisherCount();
            return true;
        } catch (GuzzleException) {
            return false;
        }
    }
}
