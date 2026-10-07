<?php

/**
 * @file plugins/generic/thoth/classes/container/providers/ThothServiceProvider.php
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

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\thoth\classes\api\ThothEndpoint;
use APP\plugins\generic\thoth\classes\factories\ThothAbstractFactory;
use APP\plugins\generic\thoth\classes\factories\ThothBiographyFactory;
use APP\plugins\generic\thoth\classes\factories\ThothBookFactory;
use APP\plugins\generic\thoth\classes\factories\ThothChapterFactory;
use APP\plugins\generic\thoth\classes\factories\ThothContributionFactory;
use APP\plugins\generic\thoth\classes\factories\ThothContributorFactory;
use APP\plugins\generic\thoth\classes\factories\ThothLocationFactory;
use APP\plugins\generic\thoth\classes\factories\ThothPublicationFactory;
use APP\plugins\generic\thoth\classes\factories\ThothTitleFactory;
use APP\plugins\generic\thoth\classes\listeners\PublicationEditListener;
use APP\plugins\generic\thoth\classes\listeners\PublicationPublishListener;
use APP\plugins\generic\thoth\classes\notification\ThothNotification;
use APP\plugins\generic\thoth\classes\pkp\OmpMetadataSource;
use APP\plugins\generic\thoth\classes\services\FeatureVideoSubmissionService;
use APP\plugins\generic\thoth\classes\services\ThothAbstractService;
use APP\plugins\generic\thoth\classes\services\ThothAffiliationService;
use APP\plugins\generic\thoth\classes\services\ThothBiographyService;
use APP\plugins\generic\thoth\classes\services\ThothBookRegistrationService;
use APP\plugins\generic\thoth\classes\services\ThothBookService;
use APP\plugins\generic\thoth\classes\services\ThothChapterService;
use APP\plugins\generic\thoth\classes\services\ThothContributionService;
use APP\plugins\generic\thoth\classes\services\ThothContributorService;
use APP\plugins\generic\thoth\classes\services\ThothFeatureVideoService;
use APP\plugins\generic\thoth\classes\services\ThothFileUploadService;
use APP\plugins\generic\thoth\classes\services\ThothFrontcoverService;
use APP\plugins\generic\thoth\classes\services\ThothLanguageService;
use APP\plugins\generic\thoth\classes\services\ThothLocationService;
use APP\plugins\generic\thoth\classes\services\ThothMeService;
use APP\plugins\generic\thoth\classes\services\ThothMetadataSynchronizationService;
use APP\plugins\generic\thoth\classes\services\ThothPublicationService;
use APP\plugins\generic\thoth\classes\services\ThothReferenceService;
use APP\plugins\generic\thoth\classes\services\ThothSubjectService;
use APP\plugins\generic\thoth\classes\services\ThothTitleService;
use APP\plugins\generic\thoth\classes\services\ThothWorkLinkService;
use APP\plugins\generic\thoth\classes\services\ThothWorkRelationService;
use Illuminate\Support\Facades\DB;
use PKP\db\DAORegistry;

class ThothServiceProvider implements ContainerProvider
{
    public function register($container)
    {
        $container->singletonClass('notification', ThothNotification::class);
        $container->singletonClass('workLinkService', ThothWorkLinkService::class, [
            'workRepository',
        ]);
        $container->singletonClass('publicationEditListener', PublicationEditListener::class, [
            fn () => Repo::submission(),
            fn ($container) => fn () => $container->get('bookService'),
            'notification',
        ]);
        $container->singletonClass('publicationPublishListener', PublicationPublishListener::class, [
            fn ($container) => fn () => $container->get('bookRegistrationService'),
            'notification',
            fn () => Application::get()->getRequest(),
        ]);
        $container->singletonClass('endpoint', ThothEndpoint::class, [
            fn ($container) => fn () => $container->get('bookService'),
            fn ($container) => fn () => $container->get('bookRegistrationService'),
            fn ($container) => fn () => $container->get('metadataSynchronizationService'),
            fn ($container) => fn () => $container->get('workLinkService'),
            fn ($container) => fn () => $container->get('meService'),
            fn ($container) => fn () => $container->get('featureVideoSubmissionService'),
            fn ($container) => fn () => $container->get('workRepository'),
            'notification',
        ]);

        $container->singleton('metadataSource', fn () => new OmpMetadataSource(
            Repo::submission(),
            Repo::publication(),
            Application::getContextDAO(),
            DAORegistry::getDAO('PublicationFormatDAO'),
            Application::get()->getRequest()
        ));

        $container->singletonClass('affiliationService', ThothAffiliationService::class, [
            'affiliationRepository',
            'institutionRepository',
        ]);

        $container->singletonClass('abstractService', ThothAbstractService::class, [
            new ThothAbstractFactory(),
            'abstractRepository',
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
            'metadataSource',
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
            'metadataSource',
            'frontcoverService',
            fn () => Repo::submission(),
            fn () => DB::connection(),
        ]);

        $container->singletonClass('chapterService', ThothChapterService::class, [
            new ThothChapterFactory(),
            'chapterRepository',
            'contributionService',
            'publicationService',
            'titleService',
            'abstractService',
            'metadataSource',
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
            'meService',
        ]);

        $container->singletonClass('languageService', ThothLanguageService::class, [
            'languageRepository',
        ]);

        $container->singletonClass('locationService', ThothLocationService::class, [
            new ThothLocationFactory(),
            'locationRepository',
            'metadataSource',
        ]);

        $container->singletonClass('meService', ThothMeService::class, [
            'meRepository',
        ]);

        $container->singletonClass('metadataSynchronizationService', ThothMetadataSynchronizationService::class, [
            'bookService',
            'contributionService',
            'publicationService',
            'languageService',
            'subjectService',
            'referenceService',
            'workRelationService',
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
