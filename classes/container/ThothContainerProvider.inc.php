<?php

/**
 * @file plugins/generic/thoth/tests/classes/container/ContainerProvider.inc.php
 *
 * Copyright (c) 2025 Lepidus Tecnologia
 * Copyright (c) 2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContainerProvider
 * @ingroup plugins_generic_thoth
 *
 * @brief Utility class to package all plugin container bindings
 */

require_once(__DIR__ . '/../../vendor/autoload.php');

use ThothApi\GraphQL\Client;

import('plugins.generic.thoth.classes.container.ContainerProvider');
import('plugins.generic.thoth.classes.services.ThothAffiliationService');
import('plugins.generic.thoth.classes.services.ThothContributionService');
import('plugins.generic.thoth.classes.services.ThothContributorService');
import('plugins.generic.thoth.classes.services.ThothInstitutionService');
import('plugins.generic.thoth.classes.services.ThothLanguageService');
import('plugins.generic.thoth.classes.services.ThothLocationService');
import('plugins.generic.thoth.classes.services.ThothPublicationService');
import('plugins.generic.thoth.classes.services.ThothReferenceService');
import('plugins.generic.thoth.classes.services.ThothSubjectService');
import('plugins.generic.thoth.classes.services.ThothWorkService');
import('plugins.generic.thoth.lib.APIKeyEncryption.APIKeyEncryption');

class ThothContainerProvider implements ContainerProvider
{
    public function register($container)
    {
        $container->set('config', function ($container) {
            $pluginSettingsDao = & DAORegistry::getDAO('PluginSettingsDAO');
            $contextId = Application::get()->getRequest()->getContext()->getId();

            $testEnvironment = $pluginSettingsDao->getSetting($contextId, 'ThothPlugin', 'testEnvironment');
            $email = $pluginSettingsDao->getSetting($contextId, 'ThothPlugin', 'email');
            $password = $pluginSettingsDao->getSetting($contextId, 'ThothPlugin', 'password');

            return [
                'testEnvironment' => $testEnvironment,
                'email' => $email,
                'password' => APIKeyEncryption::decryptString($password)
            ];
        });

        $container->set('client', function ($container) {
            $config = $container->get('config');

            $httpConfig = [];
            if ($config['testEnvironment']) {
                $httpConfig['base_uri'] = 'http://localhost:8000/';
            }

            $client = new Client($httpConfig);
            return $client->login($config['email'], $config['password']);
        });

        $container->set('affiliationService', function ($container) {
            return new ThothAffiliationService();
        });

        $container->set('contributionService', function ($container) {
            return new ThothContributionService();
        });

        $container->set('contributorService', function ($container) {
            return new ThothContributorService();
        });

        $container->set('institutionService', function ($container) {
            return new ThothInstitutionService();
        });

        $container->set('languageService', function ($container) {
            return new ThothLanguageService();
        });

        $container->set('locationService', function ($container) {
            return new ThothLocationService();
        });

        $container->set('publicationService', function ($container) {
            return new ThothPublicationService();
        });

        $container->set('referenceService', function ($container) {
            return new ThothReferenceService();
        });

        $container->set('subjectService', function ($container) {
            return new ThothSubjectService();
        });

        $container->set('workService', function ($container) {
            return new ThothWorkService();
        });
    }
}
