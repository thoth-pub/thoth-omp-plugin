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
import('plugins.generic.thoth.classes.factories.ThothBookFactory');
import('plugins.generic.thoth.classes.factories.ThothChapterFactory');
import('plugins.generic.thoth.classes.factories.ThothContributionFactory');
import('plugins.generic.thoth.classes.factories.ThothContributorFactory');
import('plugins.generic.thoth.classes.factories.ThothLocationFactory');
import('plugins.generic.thoth.classes.factories.ThothPublicationFactory');
import('plugins.generic.thoth.classes.repositories.ThothAffiliationRepository');
import('plugins.generic.thoth.classes.repositories.ThothBookRepository');
import('plugins.generic.thoth.classes.repositories.ThothChapterRepository');
import('plugins.generic.thoth.classes.repositories.ThothContributionRepository');
import('plugins.generic.thoth.classes.repositories.ThothContributorRepository');
import('plugins.generic.thoth.classes.repositories.ThothInstitutionRepository');
import('plugins.generic.thoth.classes.repositories.ThothLanguageRepository');
import('plugins.generic.thoth.classes.repositories.ThothLocationRepository');
import('plugins.generic.thoth.classes.repositories.ThothPublicationRepository');
import('plugins.generic.thoth.classes.repositories.ThothReferenceRepository');
import('plugins.generic.thoth.classes.repositories.ThothSubjectRepository');
import('plugins.generic.thoth.classes.repositories.ThothWorkRelationRepository');
import('plugins.generic.thoth.classes.repositories.ThothWorkRepository');
import('plugins.generic.thoth.classes.services.ThothAffiliationService');
import('plugins.generic.thoth.classes.services.ThothBookService');
import('plugins.generic.thoth.classes.services.ThothChapterService');
import('plugins.generic.thoth.classes.services.ThothContributionService');
import('plugins.generic.thoth.classes.services.ThothContributorService');
import('plugins.generic.thoth.classes.services.ThothLanguageService');
import('plugins.generic.thoth.classes.services.ThothLocationService');
import('plugins.generic.thoth.classes.services.ThothPublicationService');
import('plugins.generic.thoth.classes.services.ThothReferenceService');
import('plugins.generic.thoth.classes.services.ThothSubjectService');
import('plugins.generic.thoth.classes.services.ThothWorkRelationService');
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

        $container->set('affiliationService', function ($container) {
            return new ThothAffiliationService($container->get('affiliationRepository'));
        });
        $container->set('bookService', function ($container) {
            return new ThothBookService(
                new ThothBookFactory(),
                $container->get('bookRepository')
            );
        });
        $container->set('chapterService', function ($container) {
            return new ThothChapterService(
                new ThothChapterFactory(),
                $container->get('chapterRepository')
            );
        });
        $container->set('contributionService', function ($container) {
            return new ThothContributionService(
                new ThothContributionFactory(),
                $container->get('contributionRepository')
            );
        });
        $container->set('contributorService', function ($container) {
            return new ThothContributorService(
                new ThothContributorFactory(),
                $container->get('contributorRepository')
            );
        });
        $container->set('languageService', function ($container) {
            return new ThothLanguageService($container->get('languageRepository'));
        });
        $container->set('locationService', function ($container) {
            return new ThothLocationService(
                new ThothLocationFactory(),
                $container->get('locationRepository')
            );
        });
        $container->set('publicationService', function ($container) {
            return new ThothPublicationService(
                new ThothPublicationFactory(),
                $container->get('publicationRepository')
            );
        });
        $container->set('referenceService', function ($container) {
            return new ThothReferenceService($container->get('referenceRepository'));
        });
        $container->set('subjectService', function ($container) {
            return new ThothSubjectService($container->get('subjectRepository'));
        });
        $container->set('workRelationService', function ($container) {
            return new ThothWorkRelationService($container->get('workRelationRepository'));
        });
    }
}
