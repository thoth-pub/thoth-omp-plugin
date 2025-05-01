<?php

/**
 * @file plugins/generic/thoth/classes/formModifiers/AuthorFormModifier.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AuthorFormModifier
 * @ingroup plugins_generic_thoth
 *
 * @brief Additional fields to the author form
 */

class AuthorFormModifier
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function handleFormConstructor($hookName, $args)
    {
        $form = & $args[0];
        $form->setTemplate($this->plugin->getTemplateResource('form/authorForm.tpl'));

        return false;
    }

    public function handleFormDisplay($hookName, $args)
    {
        $request = PKPApplication::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);

        $authorForm = & $args[0];
        $author = $authorForm->getAuthor();

        if ($author) {
            $templateMgr->assign(['mainContribution' => $author->getData('mainContribution')]);
        }

        return false;
    }

    public function handleFormExecute($hookName, $args)
    {
        $form = & $args[0];
        $form->readUserVars(['mainContribution']);

        $author = $form->getAuthor();
        $mainContribution = $form->getData('mainContribution');

        $author->setData('mainContribution', $mainContribution);

        return false;
    }
}
