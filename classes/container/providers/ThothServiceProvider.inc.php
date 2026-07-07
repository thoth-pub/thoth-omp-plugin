<?php

import('plugins.generic.thoth.classes.container.providers.ContainerProvider');
import('plugins.generic.thoth.classes.factories.ThothAbstractFactory');
import('plugins.generic.thoth.classes.factories.ThothBiographyFactory');
import('plugins.generic.thoth.classes.factories.ThothBookFactory');
import('plugins.generic.thoth.classes.factories.ThothChapterFactory');
import('plugins.generic.thoth.classes.factories.ThothContributionFactory');
import('plugins.generic.thoth.classes.factories.ThothContributorFactory');
import('plugins.generic.thoth.classes.factories.ThothLocationFactory');
import('plugins.generic.thoth.classes.factories.ThothPublicationFactory');
import('plugins.generic.thoth.classes.factories.ThothTitleFactory');
import('plugins.generic.thoth.classes.services.ThothAbstractService');
import('plugins.generic.thoth.classes.services.ThothAffiliationService');
import('plugins.generic.thoth.classes.services.ThothBiographyService');
import('plugins.generic.thoth.classes.services.ThothBookRegistrationService');
import('plugins.generic.thoth.classes.services.ThothBookService');
import('plugins.generic.thoth.classes.services.ThothChapterService');
import('plugins.generic.thoth.classes.services.ThothContributionService');
import('plugins.generic.thoth.classes.services.ThothContributorService');
import('plugins.generic.thoth.classes.services.ThothLanguageService');
import('plugins.generic.thoth.classes.services.ThothLocationService');
import('plugins.generic.thoth.classes.services.ThothPublicationService');
import('plugins.generic.thoth.classes.services.ThothReferenceService');
import('plugins.generic.thoth.classes.services.ThothSubjectService');
import('plugins.generic.thoth.classes.services.ThothTitleService');
import('plugins.generic.thoth.classes.services.ThothWorkRelationService');

class ThothServiceProvider implements ContainerProvider
{
    public function register($container)
    {
        $container->singleton('abstractService', function ($container) {
            return new ThothAbstractService(new ThothAbstractFactory(), $container->get('abstractRepository'));
        });

        $container->singleton('affiliationService', function ($container) {
            return new ThothAffiliationService(
                $container->get('affiliationRepository'),
                $container->get('institutionRepository')
            );
        });

        $container->singleton('biographyService', function ($container) {
            return new ThothBiographyService(new ThothBiographyFactory(), $container->get('biographyRepository'));
        });

        $container->singleton('bookService', function ($container) {
            return new ThothBookService(
                new ThothBookFactory(),
                $container->get('bookRepository'),
                $container->get('publicationService'),
                $container->get('titleService'),
                $container->get('abstractService')
            );
        });

        $container->singleton('bookRegistrationService', function ($container) {
            return new ThothBookRegistrationService(
                new ThothBookFactory(),
                $container->get('bookRepository'),
                $container->get('abstractService'),
                $container->get('contributionService'),
                $container->get('languageService'),
                $container->get('publicationService'),
                $container->get('referenceService'),
                $container->get('subjectService'),
                $container->get('titleService'),
                $container->get('workRelationService')
            );
        });

        $container->singleton('chapterService', function ($container) {
            return new ThothChapterService(
                new ThothChapterFactory(),
                $container->get('chapterRepository'),
                $container->get('contributionService'),
                $container->get('publicationService'),
                $container->get('titleService'),
                $container->get('abstractService')
            );
        });

        $container->singleton('contributionService', function ($container) {
            return new ThothContributionService(
                new ThothContributionFactory(),
                $container->get('contributionRepository'),
                $container->get('contributorRepository'),
                $container->get('contributorService'),
                $container->get('biographyService'),
                $container->get('affiliationService')
            );
        });

        $container->singleton('contributorService', function ($container) {
            return new ThothContributorService(new ThothContributorFactory(), $container->get('contributorRepository'));
        });

        $container->singleton('languageService', function ($container) {
            return new ThothLanguageService($container->get('languageRepository'));
        });

        $container->singleton('locationService', function ($container) {
            return new ThothLocationService(new ThothLocationFactory(), $container->get('locationRepository'));
        });

        $container->singleton('publicationService', function ($container) {
            return new ThothPublicationService(
                new ThothPublicationFactory(),
                $container->get('publicationRepository'),
                $container->get('locationService')
            );
        });

        $container->singleton('referenceService', function ($container) {
            return new ThothReferenceService($container->get('referenceRepository'));
        });

        $container->singleton('subjectService', function ($container) {
            return new ThothSubjectService($container->get('subjectRepository'));
        });

        $container->singleton('titleService', function ($container) {
            return new ThothTitleService(new ThothTitleFactory(), $container->get('titleRepository'));
        });

        $container->singleton('workRelationService', function ($container) {
            return new ThothWorkRelationService(
                $container->get('workRelationRepository'),
                $container->get('chapterService')
            );
        });
    }
}
