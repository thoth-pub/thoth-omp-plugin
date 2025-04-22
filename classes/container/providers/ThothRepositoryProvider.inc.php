<?php

/**
 * @file plugins/generic/thoth/tests/classes/container/providers/ThothRepositoryProvider.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothRepositoryProvider
 * @ingroup plugins_generic_thoth
 *
 * @brief Utility class to package all plugin container bindings for repositories
 */

require_once(__DIR__ . '/../../../vendor/autoload.php');

use ThothApi\GraphQL\Client;

import('plugins.generic.thoth.classes.container.providers.ContainerProvider');
import('plugins.generic.thoth.classes.factories.ThothBookFactory');
import('plugins.generic.thoth.classes.factories.ThothChapterFactory');
import('plugins.generic.thoth.classes.factories.ThothContributionFactory');
import('plugins.generic.thoth.classes.factories.ThothContributorFactory');
import('plugins.generic.thoth.classes.factories.ThothLocationFactory');
import('plugins.generic.thoth.classes.factories.ThothPublicationFactory');
import('plugins.generic.thoth.classes.repositories.ThothAccountRepository');
import('plugins.generic.thoth.classes.repositories.ThothAffiliationRepository');
import('plugins.generic.thoth.classes.repositories.ThothBookRepository');
import('plugins.generic.thoth.classes.repositories.ThothChapterRepository');
import('plugins.generic.thoth.classes.repositories.ThothContributionRepository');
import('plugins.generic.thoth.classes.repositories.ThothContributorRepository');
import('plugins.generic.thoth.classes.repositories.ThothImprintRepository');
import('plugins.generic.thoth.classes.repositories.ThothInstitutionRepository');
import('plugins.generic.thoth.classes.repositories.ThothLanguageRepository');
import('plugins.generic.thoth.classes.repositories.ThothLocationRepository');
import('plugins.generic.thoth.classes.repositories.ThothPublicationRepository');
import('plugins.generic.thoth.classes.repositories.ThothReferenceRepository');
import('plugins.generic.thoth.classes.repositories.ThothSubjectRepository');
import('plugins.generic.thoth.classes.repositories.ThothWorkRelationRepository');
import('plugins.generic.thoth.classes.repositories.ThothWorkRepository');
import('plugins.generic.thoth.lib.APIKeyEncryption.APIKeyEncryption');

class ThothRepositoryProvider implements ContainerProvider
{
    public function register($container)
    {
        $container->set('config', function ($container) {
            $pluginSettingsDao = & DAORegistry::getDAO('PluginSettingsDAO');
            $contextId = Application::get()->getRequest()->getContext()->getId();

            $testEnvironment = $pluginSettingsDao->getSetting($contextId, 'ThothPlugin', 'testEnvironment');
            $email = $pluginSettingsDao->getSetting($contextId, 'ThothPlugin', 'email');
            $password = $pluginSettingsDao->getSetting($contextId, 'ThothPlugin', 'password') ?? '';

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

        $container->set('accountRepository', function ($container) {
            return new ThothAccountRepository($container->get('client'));
        });

        $container->set('affiliationRepository', function ($container) {
            return new ThothAffiliationRepository($container->get('client'));
        });

        $container->set('bookRepository', function ($container) {
            return new ThothBookRepository($container->get('client'));
        });

        $container->set('chapterRepository', function ($container) {
            return new ThothChapterRepository($container->get('client'));
        });

        $container->set('contributionRepository', function ($container) {
            return new ThothContributionRepository($container->get('client'));
        });

        $container->set('contributorRepository', function ($container) {
            return new ThothContributorRepository($container->get('client'));
        });

        $container->set('imprintRepository', function ($container) {
            return new ThothImprintRepository($container->get('client'));
        });

        $container->set('institutionRepository', function ($container) {
            return new ThothInstitutionRepository($container->get('client'));
        });

        $container->set('languageRepository', function ($container) {
            return new ThothLanguageRepository($container->get('client'));
        });

        $container->set('locationRepository', function ($container) {
            return new ThothLocationRepository($container->get('client'));
        });

        $container->set('publicationRepository', function ($container) {
            return new ThothPublicationRepository($container->get('client'));
        });

        $container->set('referenceRepository', function ($container) {
            return new ThothReferenceRepository($container->get('client'));
        });

        $container->set('subjectRepository', function ($container) {
            return new ThothSubjectRepository($container->get('client'));
        });

        $container->set('workRelationRepository', function ($container) {
            return new ThothWorkRelationRepository($container->get('client'));
        });

        $container->set('workRepository', function ($container) {
            return new ThothWorkRepository($container->get('client'));
        });
    }
}
