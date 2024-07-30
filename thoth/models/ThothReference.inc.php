<?php

/**
 * @file plugins/generic/thoth/thoth/models/ThothReference.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothReference
 * @ingroup plugins_generic_thoth
 *
 * @brief Class for a Thoth's reference.
 */

import('plugins.generic.thoth.thoth.models.ThothModel');

class ThothReference extends ThothModel
{
    private $referenceId;

    private $workId;

    private $referenceOrdinal;

    private $unstructuredCitation;

    public function getReturnValue()
    {
        return 'referenceId';
    }

    public function getId()
    {
        return $this->referenceId;
    }

    public function setId($referenceId)
    {
        $this->referenceId = $referenceId;
    }

    public function getWorkId()
    {
        return $this->workId;
    }

    public function setWorkId($workId)
    {
        $this->workId = $workId;
    }

    public function getReferenceOrdinal()
    {
        return $this->referenceOrdinal;
    }

    public function setReferenceOrdinal($referenceOrdinal)
    {
        $this->referenceOrdinal = $referenceOrdinal;
    }

    public function getUnstructuredCitation()
    {
        return $this->unstructuredCitation;
    }

    public function setUnstructuredCitation($unstructuredCitation)
    {
        $this->unstructuredCitation = $unstructuredCitation;
    }
}
