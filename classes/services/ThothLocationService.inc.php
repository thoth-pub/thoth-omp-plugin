<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothLocationService.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth locations
 */

import('plugins.generic.thoth.thoth.models.ThothLocation');

class ThothLocationService
{
    public function newByPublicationFormat($publicationFormat, $fileId = null)
    {
        $request = Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();
        $context = $request->getContext();
        $publication = Services::get('publication')->get($publicationFormat->getData('publicationId'));
        $submission = Services::get('submission')->get($publication->getData('submissionId'));

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

        return $this->new($params);
    }

    public function new($params)
    {
        $thothLocation = new ThothLocation();
        $thothLocation->setLandingPage($params['landingPage']);
        $thothLocation->setFullTextUrl($params['fullTextUrl']);
        $thothLocation->setLocationPlatform($params['locationPlatform']);

        return $thothLocation;
    }

    public function register($thothClient, $publicationFormat, $thothPublicationId, $fileId = null, $canonical = true)
    {
        $thothLocation = $this->newByPublicationFormat($publicationFormat, $fileId);
        $thothLocation->setPublicationId($thothPublicationId);
        $thothLocation->setCanonical($canonical);

        $thothLocationId = $thothClient->createLocation($thothLocation);
        $thothLocation->setId($thothLocationId);

        return $thothLocation;
    }
}
