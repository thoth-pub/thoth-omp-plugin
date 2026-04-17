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
        $container->set('abstractService', function ($container) {
            return new ThothAbstractService(new ThothAbstractFactory(), $container->get('abstractRepository'));
        });

        $container->set('affiliationService', function ($container) {
            return new ThothAffiliationService($container->get('affiliationRepository'));
        });

        $container->set('biographyService', function ($container) {
            return new ThothBiographyService(new ThothBiographyFactory(), $container->get('biographyRepository'));
        });

        $container->set('bookService', function ($container) {
            return new ThothBookService(new ThothBookFactory(), $container->get('bookRepository'));
        });

        $container->set('chapterService', function ($container) {
            return new ThothChapterService(new ThothChapterFactory(), $container->get('chapterRepository'));
        });

        $container->set('contributionService', function ($container) {
            return new ThothContributionService(new ThothContributionFactory(), $container->get('contributionRepository'));
        });

        $container->set('contributorService', function ($container) {
            return new ThothContributorService(new ThothContributorFactory(), $container->get('contributorRepository'));
        });

        $container->set('languageService', function ($container) {
            return new ThothLanguageService($container->get('languageRepository'));
        });

        $container->set('locationService', function ($container) {
            return new ThothLocationService(new ThothLocationFactory(), $container->get('locationRepository'));
        });

        $container->set('publicationService', function ($container) {
            return new ThothPublicationService(new ThothPublicationFactory(), $container->get('publicationRepository'));
        });

        $container->set('referenceService', function ($container) {
            return new ThothReferenceService($container->get('referenceRepository'));
        });

        $container->set('subjectService', function ($container) {
            return new ThothSubjectService($container->get('subjectRepository'));
        });

        $container->set('titleService', function ($container) {
            return new ThothTitleService(new ThothTitleFactory(), $container->get('titleRepository'));
        });

        $container->set('workRelationService', function ($container) {
            return new ThothWorkRelationService($container->get('workRelationRepository'));
        });
    }
}
