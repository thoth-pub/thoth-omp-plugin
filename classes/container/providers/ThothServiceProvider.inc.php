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
import('plugins.generic.thoth.classes.services.ThothFileUploadService');
import('plugins.generic.thoth.classes.services.FeatureVideoSubmissionService');
import('plugins.generic.thoth.classes.services.ThothFeatureVideoService');
import('plugins.generic.thoth.classes.services.ThothFrontcoverService');
import('plugins.generic.thoth.classes.services.ThothLanguageService');
import('plugins.generic.thoth.classes.services.ThothLocationService');
import('plugins.generic.thoth.classes.services.ThothMetadataSynchronizationService');
import('plugins.generic.thoth.classes.services.ThothPublicationService');
import('plugins.generic.thoth.classes.services.ThothReferenceService');
import('plugins.generic.thoth.classes.services.ThothSubjectService');
import('plugins.generic.thoth.classes.services.ThothTitleService');
import('plugins.generic.thoth.classes.services.ThothWorkRelationService');

class ThothServiceProvider implements ContainerProvider
{
    public function register($container)
    {
        $container->singletonClass('abstractService', ThothAbstractService::class, [
            new ThothAbstractFactory(),
            'abstractRepository',
        ]);

        $container->singletonClass('affiliationService', ThothAffiliationService::class, [
            'affiliationRepository',
            'institutionRepository',
        ]);

        $container->singletonClass('biographyService', ThothBiographyService::class, [
            new ThothBiographyFactory(),
            'biographyRepository',
        ]);

        $container->singletonClass('bookService', ThothBookService::class, [
            new ThothBookFactory(),
            'bookRepository',
            'publicationService',
            'titleService',
            'abstractService',
            'frontcoverService',
        ]);

        $container->singletonClass('bookRegistrationService', ThothBookRegistrationService::class, [
            new ThothBookFactory(),
            'bookRepository',
            'abstractService',
            'contributionService',
            'languageService',
            'publicationService',
            'referenceService',
            'subjectService',
            'titleService',
            'workRelationService',
            'frontcoverService',
        ]);

        $container->singletonClass('chapterService', ThothChapterService::class, [
            new ThothChapterFactory(),
            'chapterRepository',
            'contributionService',
            'publicationService',
            'titleService',
            'abstractService',
        ]);

        $container->singletonClass('contributionService', ThothContributionService::class, [
            new ThothContributionFactory(),
            'contributionRepository',
            'contributorRepository',
            'contributorService',
            'biographyService',
            'affiliationService',
        ]);

        $container->singletonClass('contributorService', ThothContributorService::class, [
            new ThothContributorFactory(),
            'contributorRepository',
        ]);

        $container->singletonClass('fileUploadService', ThothFileUploadService::class);

        $container->singletonClass('featureVideoService', ThothFeatureVideoService::class, [
            'featureVideoRepository',
            'featureVideoFileUploadRepository',
            'fileUploadService',
        ]);

        $container->singletonClass('featureVideoSubmissionService', FeatureVideoSubmissionService::class, [
            'featureVideoService',
        ]);

        $container->singletonClass('frontcoverService', ThothFrontcoverService::class, [
            'frontcoverFileUploadRepository',
            'workRepository',
            'fileUploadService',
        ]);

        $container->singletonClass('languageService', ThothLanguageService::class, [
            'languageRepository',
        ]);

        $container->singletonClass('locationService', ThothLocationService::class, [
            new ThothLocationFactory(),
            'locationRepository',
        ]);

        $container->singletonClass('metadataSynchronizationService', ThothMetadataSynchronizationService::class, [
            'bookService',
            'contributionService',
            'publicationService',
            'languageService',
        ]);

        $container->singletonClass('publicationService', ThothPublicationService::class, [
            new ThothPublicationFactory(),
            'publicationRepository',
            'locationService',
        ]);

        $container->singletonClass('referenceService', ThothReferenceService::class, [
            'referenceRepository',
        ]);

        $container->singletonClass('subjectService', ThothSubjectService::class, [
            'subjectRepository',
        ]);

        $container->singletonClass('titleService', ThothTitleService::class, [
            new ThothTitleFactory(),
            'titleRepository',
        ]);

        $container->singletonClass('workRelationService', ThothWorkRelationService::class, [
            'workRelationRepository',
            'chapterService',
        ]);
    }
}
