<?php

/**
 * @file plugins/generic/thoth/lib/thothAPI/models/ThothSubject.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSubject
 * @ingroup plugins_generic_thoth
 *
 * @brief Class for a Thoth's subject.
 */

import('plugins.generic.thoth.lib.thothAPI.models.ThothModel');

class ThothSubject extends ThothModel
{
    private $subjectId;

    private $workId;

    private $subjectType;

    private $subjectCode;

    private $subjectOrdinal;

    public const SUBJECT_TYPE_KEYWORD = 'KEYWORD';

    public function getEnumeratedValues()
    {
        return parent::getEnumeratedValues() + [
            'subjectType'
        ];
    }

    public function getReturnValue()
    {
        return 'subjectId';
    }

    public function getId()
    {
        return $this->subjectId;
    }

    public function setId($subjectId)
    {
        $this->subjectId = $subjectId;
    }

    public function getWorkId()
    {
        return $this->workId;
    }

    public function setWorkId($workId)
    {
        $this->workId = $workId;
    }

    public function getSubjectType()
    {
        return $this->subjectType;
    }

    public function setSubjectType($subjectType)
    {
        $this->subjectType = $subjectType;
    }

    public function getSubjectCode()
    {
        return $this->subjectCode;
    }

    public function setSubjectCode($subjectCode)
    {
        $this->subjectCode = $subjectCode;
    }

    public function getSubjectOrdinal()
    {
        return $this->subjectOrdinal;
    }

    public function setSubjectOrdinal($subjectOrdinal)
    {
        $this->subjectOrdinal = $subjectOrdinal;
    }
}
