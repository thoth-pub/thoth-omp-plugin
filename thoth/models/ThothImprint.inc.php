<?php

/**
 * @file plugins/generic/thoth/thoth/models/ThothImprint.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothImprint
 * @ingroup plugins_generic_thoth
 *
 * @brief Class for a Thoth imprint.
 */

import('plugins.generic.thoth.thoth.models.ThothModel');

class ThothImprint extends ThothModel
{
    private $imprintId;

    private $publisherId;

    private $imprintName;

    private $imprintUrl;

    private $crossmarkDoi;

    public function getReturnValue()
    {
        return 'imprintId';
    }

    public function getId()
    {
        return $this->imprintId;
    }

    public function setId($imprintId)
    {
        $this->imprintId = $imprintId;
    }

    public function getPublisherId()
    {
        return $this->publisherId;
    }

    public function setPublisherId($publisherId)
    {
        $this->publisherId = $publisherId;
    }

    public function getImprintName()
    {
        return $this->imprintName;
    }

    public function setImprintName($imprintName)
    {
        $this->imprintName = $imprintName;
    }

    public function getImprintUrl()
    {
        return $this->imprintUrl;
    }

    public function setImprintUrl($imprintUrl)
    {
        $this->imprintUrl = $imprintUrl;
    }

    public function getCrossmarkDoi()
    {
        return $this->crossmarkDoi;
    }

    public function setCrossmarkDoi($crossmarkDoi)
    {
        $this->crossmarkDoi = $crossmarkDoi;
    }
}
