<?php

/**
 * @file plugins/generic/thoth/tests/classes/container/providers/ThothServiceProvider.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothServiceProvider
 * @ingroup plugins_generic_thoth
 *
 * @brief Utility class to package all plugin container bindings for services
 */

import('plugins.generic.thoth.classes.container.providers.ContainerProvider');
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

class ThothServiceProvider implements ContainerProvider
{
    public function register($container)
    {
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
