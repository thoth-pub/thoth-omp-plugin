<?php


/**
 * @file plugins/generic/thoth/tests/classes/container/providers/ThothServiceProvider.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothServiceProvider
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Utility class to package all plugin container bindings for services
 */

namespace APP\plugins\generic\thoth\classes\container\providers;

use APP\plugins\generic\thoth\classes\factories\ThothAbstractFactory;
use APP\plugins\generic\thoth\classes\factories\ThothBiographyFactory;
use APP\plugins\generic\thoth\classes\factories\ThothBookFactory;
use APP\plugins\generic\thoth\classes\factories\ThothChapterFactory;
use APP\plugins\generic\thoth\classes\factories\ThothContributionFactory;
use APP\plugins\generic\thoth\classes\factories\ThothContributorFactory;
use APP\plugins\generic\thoth\classes\factories\ThothLocationFactory;
use APP\plugins\generic\thoth\classes\factories\ThothPublicationFactory;
use APP\plugins\generic\thoth\classes\factories\ThothTitleFactory;
use APP\plugins\generic\thoth\classes\services\ThothAbstractService;
use APP\plugins\generic\thoth\classes\services\ThothAffiliationService;
use APP\plugins\generic\thoth\classes\services\ThothBiographyService;
use APP\plugins\generic\thoth\classes\services\ThothBookRegistrationService;
use APP\plugins\generic\thoth\classes\services\ThothBookService;
use APP\plugins\generic\thoth\classes\services\ThothChapterService;
use APP\plugins\generic\thoth\classes\services\ThothContributionService;
use APP\plugins\generic\thoth\classes\services\ThothContributorService;
use APP\plugins\generic\thoth\classes\services\ThothLanguageService;
use APP\plugins\generic\thoth\classes\services\ThothLocationService;
use APP\plugins\generic\thoth\classes\services\ThothMeService;
use APP\plugins\generic\thoth\classes\services\ThothPublicationService;
use APP\plugins\generic\thoth\classes\services\ThothReferenceService;
use APP\plugins\generic\thoth\classes\services\ThothSubjectService;
use APP\plugins\generic\thoth\classes\services\ThothTitleService;
use APP\plugins\generic\thoth\classes\services\ThothWorkRelationService;

class ThothServiceProvider implements ContainerProvider
{
    public function register($container)
    {
        $container->set('affiliationService', function ($container) {
            return new ThothAffiliationService($container->get('affiliationRepository'));
        });

        $container->set('abstractService', function ($container) {
            return new ThothAbstractService(
                new ThothAbstractFactory(),
                $container->get('abstractRepository')
            );
        });

        $container->set('biographyService', function ($container) {
            return new ThothBiographyService(
                new ThothBiographyFactory(),
                $container->get('biographyRepository')
            );
        });

        $container->set('bookService', function ($container) {
            return new ThothBookService(
                new ThothBookFactory(),
                $container->get('bookRepository')
            );
        });

        $container->set('bookRegistrationService', function ($container) {
            return new ThothBookRegistrationService(
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

        $container->set('meService', function ($container) {
            return new ThothMeService($container->get('meRepository'));
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

        $container->set('titleService', function ($container) {
            return new ThothTitleService(
                new ThothTitleFactory(),
                $container->get('titleRepository')
            );
        });

        $container->set('workRelationService', function ($container) {
            return new ThothWorkRelationService($container->get('workRelationRepository'));
        });
    }
}
