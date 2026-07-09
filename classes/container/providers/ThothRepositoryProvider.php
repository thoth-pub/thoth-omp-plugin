<?php

/**
 * @file plugins/generic/thoth/tests/classes/container/providers/ThothRepositoryProvider.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
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

use APP\plugins\generic\thoth\classes\config\ThothSettings;
use APP\plugins\generic\thoth\classes\repositories\ThothAbstractRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothAffiliationRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothBiographyRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothBookRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothChapterRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothContributionRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothContributorRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothFrontcoverFileUploadRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothImprintRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothInstitutionRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothLanguageRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothLocationRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothMeRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothPublicationFileUploadRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothPublicationRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothReferenceRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothSubjectRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothTitleRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothWorkRelationRepository;
use APP\plugins\generic\thoth\classes\repositories\ThothWorkRepository;
use ThothApi\GraphQL\Client;

class ThothRepositoryProvider implements ContainerProvider
{
    public function register($container)
    {
        $container->singleton('config', function ($container) {
            return (new ThothSettings())->toArray();
        });

        $container->singleton('client', function ($container) {
            $config = $container->get('config');

            $httpConfig = [];
            if ($config['customThothApi'] && $config['customThothApiUrl']) {
                $httpConfig['base_uri'] = trim($config['customThothApiUrl']);
            }

            $client = new Client($httpConfig);
            return $client->setToken($config['token']);
        });

        $container->singleton('abstractRepository', function ($container) {
            return new ThothAbstractRepository($container->get('client'));
        });

        $container->singleton('affiliationRepository', function ($container) {
            return new ThothAffiliationRepository($container->get('client'));
        });

        $container->singleton('bookRepository', function ($container) {
            return new ThothBookRepository($container->get('client'));
        });

        $container->singleton('biographyRepository', function ($container) {
            return new ThothBiographyRepository($container->get('client'));
        });

        $container->singleton('chapterRepository', function ($container) {
            return new ThothChapterRepository($container->get('client'));
        });

        $container->singleton('contributionRepository', function ($container) {
            return new ThothContributionRepository($container->get('client'));
        });

        $container->singleton('contributorRepository', function ($container) {
            return new ThothContributorRepository($container->get('client'));
        });

        $container->singleton('imprintRepository', function ($container) {
            return new ThothImprintRepository($container->get('client'));
        });

        $container->singleton('institutionRepository', function ($container) {
            return new ThothInstitutionRepository($container->get('client'));
        });

        $container->singleton('languageRepository', function ($container) {
            return new ThothLanguageRepository($container->get('client'));
        });

        $container->singleton('locationRepository', function ($container) {
            return new ThothLocationRepository($container->get('client'));
        });

        $container->singleton('meRepository', function ($container) {
            $contextId = Application::get()->getRequest()->getContext()->getId();
            return new ThothMeRepository($container->get('client'), $contextId);
        });

        $container->singleton('publicationRepository', function ($container) {
            return new ThothPublicationRepository($container->get('client'));
        });

        $container->singleton('publicationFileUploadRepository', function ($container) {
            return new ThothPublicationFileUploadRepository($container->get('client'));
        });

        $container->singleton('frontcoverFileUploadRepository', function ($container) {
            return new ThothFrontcoverFileUploadRepository($container->get('client'));
        });

        $container->singleton('referenceRepository', function ($container) {
            return new ThothReferenceRepository($container->get('client'));
        });

        $container->singleton('subjectRepository', function ($container) {
            return new ThothSubjectRepository($container->get('client'));
        });

        $container->singleton('titleRepository', function ($container) {
            return new ThothTitleRepository($container->get('client'));
        });

        $container->singleton('workRelationRepository', function ($container) {
            return new ThothWorkRelationRepository($container->get('client'));
        });

        $container->singleton('workRepository', function ($container) {
            return new ThothWorkRepository($container->get('client'));
        });
    }
}
