<?php

/**
 * @file plugins/generic/thoth/ThothPlugin.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPlugin
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Plugin for integration with Thoth for communication and synchronization of book data between the two platforms
 */

use APP\plugins\generic\thoth\classes\APIKeyEncryption;
use PKP\core\JSONMessage;

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.thoth.classes.ThothBadgeRender');
import('plugins.generic.thoth.classes.ThothNotification');
import('plugins.generic.thoth.classes.ThothRegister');
import('plugins.generic.thoth.classes.ThothUpdater');
import('plugins.generic.thoth.lib.thothAPI.exceptions.ThothException');

class ThothPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
            $thothRegister = new ThothRegister($this);
            HookRegistry::register('Schema::get::submission', [$thothRegister, 'addWorkIdToSchema']);
            HookRegistry::register('Schema::get::eventLog', [$thothRegister, 'addReasonToSchema']);
            HookRegistry::register('Form::config::before', [$thothRegister, 'addImprintField']);
            HookRegistry::register('Publication::validatePublish', [$thothRegister, 'validateRegister']);
            HookRegistry::register('TemplateManager::display', [$thothRegister, 'addResources']);
            HookRegistry::register('Publication::publish', [$thothRegister, 'registerOnPublish']);
            HookRegistry::register('LoadHandler', [$thothRegister, 'setupHandler']);
            HookRegistry::register('APIHandler::endpoints', [$thothRegister, 'addThothEndpoint']);

            $thothUpdater = new ThothUpdater($this);
            HookRegistry::register('Publication::edit', [$thothUpdater, 'updateWork']);

            $thothBadgeRender = new ThothBadgeRender($this);
            HookRegistry::register('TemplateManager::display', [$thothBadgeRender, 'addThothBadge']);

            $thothNotification = new ThothNotification($this);
            HookRegistry::register('TemplateManager::display', [$thothNotification, 'addNotificationScript']);
        }

        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.thoth.name');
    }

    public function getDescription()
    {
        return __('plugins.generic.thoth.description');
    }

    public function getActions($request, $verb)
    {
        $parentActions = parent::getActions($request, $verb);

        if (!$this->getEnabled()) {
            return $parentActions;
        }

        $router = $request->getRouter();

        import('lib.pkp.classes.linkAction.request.AjaxModal');
        $linkAction = new LinkAction(
            'settings',
            new AjaxModal(
                $router->url(
                    $request,
                    null,
                    null,
                    'manage',
                    null,
                    [
                        'verb' => 'settings',
                        'plugin' => $this->getName(),
                        'category' => 'generic'
                    ]
                ),
                $this->getDisplayName()
            ),
            __('manager.plugins.settings'),
            null
        );

        array_unshift($parentActions, $linkAction);

        return $parentActions;
    }

    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();

                $this->import('ThothSettingsForm');
                $form = new ThothSettingsForm($this, $context->getId());

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }
                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    public function getThothClient($contextId = null)
    {
        $contextId = $contextId ?? Application::get()->getRequest()->getContext()->getId();

        $email = $this->getSetting($contextId, 'email');
        $password = $this->getSetting($contextId, 'password');

        if (!$email || !$password) {
            throw new ThothException('Credentials not configured', 0);
        }

        $password = APIKeyEncryption::decryptString($password);
        $testEnvironment = $this->getSetting($contextId, 'testEnvironment');

        import('plugins.generic.thoth.lib.thothAPI.ThothClient');
        $thothClient = new ThothClient($testEnvironment);
        $thothClient->login($email, $password);

        return $thothClient;
    }
}
