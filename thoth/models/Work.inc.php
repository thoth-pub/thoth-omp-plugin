<?php

/**
 * @file plugins/generic/thoth/thoth/models/Work.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Work
 * @ingroup plugins_generic_thoth
 *
 * @brief Class for a Thoth work.
 */

import('plugins.generic.thoth.thoth.models.ThothModel');

class Work extends ThothModel
{
    private $workId;

    private $workType;

    private $workStatus;

    private $fullTitle;

    private $title;

    private $subtitle;

    private $edition;

    private $imprintId;

    private $doi;

    private $publicationDate;

    private $pageCount;

    private $license;

    private $copyrightHolder;

    private $landingPage;

    private $longAbstract;

    private $coverUrl;

    public const WORK_TYPE_BOOK_CHAPTER = 'BOOK_CHAPTER';

    public const WORK_TYPE_MONOGRAPH = 'MONOGRAPH';

    public const WORK_TYPE_EDITED_BOOK = 'EDITED_BOOK';

    public const WORK_STATUS_ACTIVE = 'ACTIVE';

    public function getReturnValue()
    {
        return 'workId';
    }

    public function getId()
    {
        return $this->workId;
    }

    public function setId($workId)
    {
        $this->workId = $workId;
    }

    public function getWorkType()
    {
        return $this->workType;
    }

    public function setWorkType($workType)
    {
        $this->workType = $workType;
    }

    public function getWorkStatus()
    {
        return $this->workStatus;
    }

    public function setWorkStatus($workStatus)
    {
        $this->workStatus = $workStatus;
    }

    public function getFullTitle()
    {
        return $this->fullTitle;
    }

    public function setFullTitle($fullTitle)
    {
        $this->fullTitle = $fullTitle;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getSubtitle()
    {
        return $this->subtitle;
    }

    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }

    public function getEdition()
    {
        return $this->edition;
    }

    public function setEdition($edition)
    {
        $this->edition = $edition;
    }

    public function getImprintId()
    {
        return $this->imprintId;
    }

    public function setImprintId($imprintId)
    {
        $this->imprintId = $imprintId;
    }

    public function getDoi()
    {
        return $this->doi;
    }

    public function setDoi($doi)
    {
        $this->doi = $doi;
    }

    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    public function setPublicationDate($publicationDate)
    {
        $this->publicationDate = $publicationDate;
    }

    public function getPageCount()
    {
        return $this->pageCount;
    }

    public function setPageCount($pageCount)
    {
        $this->pageCount = $pageCount;
    }

    public function getLicense()
    {
        return $this->license;
    }

    public function setLicense($license)
    {
        $this->license = $license;
    }

    public function getCopyrightHolder()
    {
        return $this->copyrightHolder;
    }

    public function setCopyrightHolder($copyrightHolder)
    {
        $this->copyrightHolder = $copyrightHolder;
    }

    public function getLandingPage()
    {
        return $this->landingPage;
    }

    public function setLandingPage($landingPage)
    {
        $this->landingPage = $landingPage;
    }

    public function getLongAbstract()
    {
        return $this->longAbstract;
    }

    public function setLongAbstract($longAbstract)
    {
        $this->longAbstract = $longAbstract;
    }

    public function getCoverUrl()
    {
        return $this->coverUrl;
    }

    public function setCoverUrl($coverUrl)
    {
        $this->coverUrl = $coverUrl;
    }
}
