<?php

/**
 * @file plugins/generic/thoth/thoth/models/ThothLocation.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocation
 * @ingroup plugins_generic_thoth
 *
 * @brief Class for a Thoth's ThothLocation.
 */

import('plugins.generic.thoth.thoth.models.ThothModel');

class ThothLocation extends ThothModel
{
    private $locationId;

    private $publicationId;

    private $landingPage;

    private $fullTextUrl;

    private $locationPlatform;

    private $canonical;

    public const LOCATION_PLATFORM_PUBLISHER_WEBSITE = 'PUBLISHER_WEBSITE';

    public const LOCATION_PLATFORM_OTHER = 'OTHER';

    public function getEnumeratedValues()
    {
        return parent::getEnumeratedValues() + [
            'locationPlatform'
        ];
    }

    public function getReturnValue()
    {
        return 'locationId';
    }

    public function getId()
    {
        return $this->locationId;
    }

    public function setId($locationId)
    {
        $this->locationId = $locationId;
    }

    public function getPublicationId()
    {
        return $this->publicationId;
    }

    public function setPublicationId($publicationId)
    {
        $this->publicationId = $publicationId;
    }

    public function getLandingPage()
    {
        return $this->landingPage;
    }

    public function setLandingPage($landingPage)
    {
        $this->landingPage = $landingPage;
    }

    public function getFullTextUrl()
    {
        return $this->fullTextUrl;
    }

    public function setFullTextUrl($fullTextUrl)
    {
        $this->fullTextUrl = $fullTextUrl;
    }

    public function getLocationPlatform()
    {
        return $this->locationPlatform;
    }

    public function setLocationPlatform($locationPlatform)
    {
        $this->locationPlatform = $locationPlatform;
    }

    public function getCanonical()
    {
        return $this->canonical;
    }

    public function setCanonical($canonical)
    {
        $this->canonical = $canonical;
    }
}
