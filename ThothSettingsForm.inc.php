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

class ThothSettingsForm extends Form
{
    private $contextId;

    private $plugin;

    public function __construct($plugin, $journalId)
    {
        $this->journalId = $journalId;
        $this->plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new FormValidator(
            $this,
            'apiKey',
            'required',
            'plugins.generic.googleAnalytics.manager.settings.googleAnalyticsSiteIdRequired'
        ));

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    public function initData()
    {
        $this->_data = [
            'apiKey' => $this->plugin->getSetting($this->contextId, 'apiKey')
        ];
    }

    public function readInputData()
    {
        $this->readUserVars(['apiKey']);
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        $this->plugin->updateSetting($this->contextId, 'apiKey', trim($this->getData('apiKey')), 'string');
        parent::execute(...$functionArgs);
    }
}
