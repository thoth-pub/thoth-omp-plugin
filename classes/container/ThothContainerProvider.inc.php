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

import('plugins.generic.thoth.classes.container.ContainerProvider');

class ThothContainerProvider implements ContainerProvider
{
    public function register($container)
    {
        $container->set('client', function ($container) {
            return new Client();
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
