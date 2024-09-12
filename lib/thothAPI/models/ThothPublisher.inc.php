<?php

/**
 * @file plugins/generic/thoth/lib/thothAPI/models/ThothPublisher.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublisher
 * @ingroup plugins_generic_thoth
 *
 * @brief Class for a Thoth's publisher.
 */

import('plugins.generic.thoth.lib.thothAPI.models.ThothModel');

class ThothPublisher extends ThothModel
{
    private $publisherId;

    private $publisherName;

    private $publisherShortname;

    private $publisherUrl;

    public function getReturnValue()
    {
        return 'publisherId';
    }

    public function getId()
    {
        return $this->publisherId;
    }

    public function setId($publisherId)
    {
        $this->publisherId = $publisherId;
    }

    public function getPublisherName()
    {
        return $this->publisherName;
    }

    public function setPublisherName($publisherName)
    {
        $this->publisherName = $publisherName;
    }

    public function getPublisherShortname()
    {
        return $this->publisherShortname;
    }

    public function setPublisherShortname($publisherShortname)
    {
        $this->publisherShortname = $publisherShortname;
    }

    public function getPublisherUrl()
    {
        return $this->publisherUrl;
    }

    public function setPublisherUrl($publisherUrl)
    {
        $this->publisherUrl = $publisherUrl;
    }
}
