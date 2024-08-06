<?php

/**
 * @file plugins/generic/thoth/thoth/models/ThothPublisher.inc.php
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

import('plugins.generic.thoth.thoth.models.ThothModel');

class ThothPublisher extends ThothModel
{
    private $publisherId;

    private $publisherName;

    private $publisherShortName;

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

    public function getPublisherShortName()
    {
        return $this->publisherShortName;
    }

    public function setPublisherShortName($publisherShortName)
    {
        $this->publisherShortName = $publisherShortName;
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
