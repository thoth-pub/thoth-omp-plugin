<?php

/**
 * @file plugins/generic/thoth/tests/classes/container/providers/ThothRepositoryProvider.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothRepositoryProvider
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Utility class to package all plugin container bindings for repositories
 */

namespace APP\plugins\generic\thoth\classes\container\providers;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\core\Application;
use APP\plugins\generic\thoth\classes\repositories\ThothAccountRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothAffiliationRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothBookRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothChapterRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothContributionRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothContributorRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothImprintRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothInstitutionRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothLanguageRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothLocationRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothPublicationRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothReferenceRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothSubjectRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothWorkRelationRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothWorkRepository;
use Illuminate\Support\Facades\Crypt;
use PKP\db\DAORegistry;
use ThothApi\GraphQL\Client;

class ThothRepositoryProvider implements ContainerProvider
{
    public function register($container)
    {
        $container->set('config', function ($container) {
            $pluginSettingsDao = & DAORegistry::getDAO('PluginSettingsDAO');
            $contextId = Application::get()->getRequest()->getContext()->getId();

            $customThothApi = $pluginSettingsDao->getSetting($contextId, 'ThothPlugin', 'customThothApi');
            $customThothApiUrl = $pluginSettingsDao->getSetting($contextId, 'ThothPlugin', 'customThothApiUrl');
            $email = $pluginSettingsDao->getSetting($contextId, 'ThothPlugin', 'email');
            $password = $pluginSettingsDao->getSetting($contextId, 'ThothPlugin', 'password') ?? '';

            return [
                'customThothApi' => $customThothApi,
                'customThothApiUrl' => $customThothApiUrl,
                'email' => $email,
                'password' => Crypt::decrypt($password)
            ];
        });

        $container->set('client', function ($container) {
            $config = $container->get('config');

            $httpConfig = [];
            if ($config['customThothApi'] && $config['customThothApiUrl']) {
                $httpConfig['base_uri'] = trim($config['customThothApiUrl']);
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
