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
import('plugins.generic.thoth.thoth.ThothClient');

class ThothSettingsForm extends Form
{
    private $contextId;

    private $plugin;

    public function __construct($plugin, $journalId)
    {
        $this->journalId = $journalId;
        $this->plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $form = $this;
        $this->addCheck(new FormValidatorCustom(
            $this,
            'password',
            'required',
            'plugins.generic.thoth.settings.invalidCredentials',
            function ($password) use ($form) {
                $email = trim($this->getData('email'));
                $thothClient = new ThothClient();
                try {
                    $thothClient->login(
                        $email,
                        $password
                    );
                } catch (Exception $e) {
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
        $this->_data = [
            'email' => $this->plugin->getSetting($this->contextId, 'email'),
            'password' => $this->plugin->getSetting($this->contextId, 'password')
        ];
    }

    public function readInputData()
    {
        $this->readUserVars(['email', 'password']);
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        $this->plugin->updateSetting($this->contextId, 'email', trim($this->getData('email')), 'string');
        $this->plugin->updateSetting($this->contextId, 'password', trim($this->getData('password')), 'string');
        parent::execute(...$functionArgs);
    }

    public function validateAPICredentials(): bool
    {
        $email = trim($this->getData('email'));
        $password = trim($this->getData('password'));

        $thothClient = new ThothClient();

        try {
            $thothClient->login(
                $email,
                $password
            );
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}
