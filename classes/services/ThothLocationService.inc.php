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
    public function getPropertiesByPublicationFormat($publicationFormat, $fileId = null)
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

        $props = [];
        $props['landingPage'] = $landingPage;
        $props['fullTextUrl'] = $fullTextUrl;
        $props['locationPlatform'] = ThothLocation::LOCATION_PLATFORM_OTHER;

        return $props;
    }

    public function new($params)
    {
        $location = new ThothLocation();
        $location->setLandingPage($params['landingPage']);
        $location->setFullTextUrl($params['fullTextUrl']);
        $location->setLocationPlatform($params['locationPlatform']);

        return $location;
    }
}
