<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothLocationService.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth locations
 */

use APP\core\Application;
use APP\facades\Repo;
use ThothApi\GraphQL\Models\Location as ThothLocation;

class ThothLocationService
{
    public function newByPublicationFormat($publicationFormat, $fileId = null)
    {
        return $this->new($this->getDataByPublicationFormat($publicationFormat, $fileId));
    }

    public function getDataByPublicationFormat($publicationFormat, $fileId = null)
    {
        $request = Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();
        $context = $request->getContext();
        $publication = Repo::publication()->get($publicationFormat->getData('publicationId'));
        $submission = Repo::submission()->get($publication->getData('submissionId'));

        $landingPage = $dispatcher->url(
            $request,
            ROUTE_PAGE,
            $context->getPath(),
            'catalog',
            'book',
            [$submission->getBestId()]
        );
        $fullTextUrl = !$fileId ?
            $publicationFormat->getRemoteUrl() :
            $dispatcher->url(
                $request,
                ROUTE_PAGE,
                $context->getPath(),
                'catalog',
                'view',
                [$submission->getBestId(), $publicationFormat->getBestId(), $fileId]
            );

        $params = [];
        $params['landingPage'] = $landingPage;
        $params['fullTextUrl'] = $fullTextUrl;
        $params['locationPlatform'] = ThothLocation::LOCATION_PLATFORM_OTHER;

        return $params;
    }

    public function new($params)
    {
        $thothLocation = new ThothLocation();
        $thothLocation->setLocationId($params['locationId'] ?? null);
        $thothLocation->setPublicationId($params['publicationId'] ?? null);
        $thothLocation->setLandingPage($params['landingPage']);
        $thothLocation->setFullTextUrl($params['fullTextUrl']);
        $thothLocation->setLocationPlatform($params['locationPlatform']);
        $thothLocation->setCanonical($params['canonical'] ?? null);

        return $thothLocation;
    }

    public function register($publicationFormat, $thothPublicationId, $fileId = null, $canonical = true)
    {
        $thothLocation = $this->newByPublicationFormat($publicationFormat, $fileId);
        $thothLocation->setPublicationId($thothPublicationId);
        $thothLocation->setCanonical($canonical);

        $thothClient = ThothContainer::getInstance()->get('client');
        $thothLocationId = $thothClient->createLocation($thothLocation);
        $thothLocation->setLocationId($thothLocationId);

        return $thothLocation;
    }

    public function updateLocations($thothLocations, $publicationFormat, $thothPublicationId, $chapterId = null)
    {
        $thothClient = ThothContainer::getInstance()->get('client');

        $files = array_filter(
            iterator_to_array(Repo::submissionFile()
                ->getCollector()
                ->filterByAssoc(
                    Application::ASSOC_TYPE_PUBLICATION_FORMAT,
                    [$publicationFormat->getId()]
                )
                ->getMany()),
            function ($file) use ($chapterId) {
                return $file->getData('chapterId') == $chapterId;
            }
        );

        if (empty($files) && !$publicationFormat->getRemoteUrl()) {
            foreach ($thothLocations as $thothLocation) {
                $thothClient->deleteLocation($thothLocation['locationId']);
            }
            return;
        }

        $currentThothLocations = [];
        $canonical = !in_array(true, array_column($thothLocations, 'canonical'));
        if ($publicationFormat->getRemoteUrl()) {
            $thothLocationData = $this->getDataByPublicationFormat($publicationFormat);
            $thothLocationData['publicationId'] = $thothPublicationId;
            $thothLocationData['canonical'] = $canonical;
            $currentThothLocations[] = $thothLocationData;
        }

        foreach ($files as $file) {
            if (!$file->getViewable() || !$file->getSalesType()) {
                continue;
            }

            $thothPublicationData = $this->getDataByPublicationFormat($publicationFormat, $file->getId());
            $thothPublicationData['publicationId'] = $thothPublicationId;
            $thothPublicationData['canonical'] = $canonical;
            $currentThothLocations[] = $thothPublicationData;
            $canonical = false;
        }

        $thothLocationsData = array_column($thothLocations, 'fullTextUrl', 'locationId');
        foreach ($thothLocationsData as $locationId => $fullTextUrl) {
            if (!in_array($fullTextUrl, array_column($currentThothLocations, 'fullTextUrl'))) {
                $thothClient->deleteLocation($locationId);
                unset($thothLocationsData[$locationId]);
            }
        }

        foreach ($currentThothLocations as $thothLocation) {
            if (!in_array($thothLocation['fullTextUrl'], $thothLocationsData)) {
                $thothClient->createLocation($this->new($thothLocation));
            }
        }
    }
}
