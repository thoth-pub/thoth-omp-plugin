<?php

/**
 * @file plugins/generic/thoth/thoth/models/WorkRelation.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WorkRelation
 * @ingroup plugins_generic_thoth
 *
 * @brief Class for a Thoth WorkRelation.
 */

import('plugins.generic.thoth.thoth.models.ThothModel');

class WorkRelation extends ThothModel
{
    private $workRelationId;

    private $relatorWorkId;

    private $relatedWorkId;

    private $relationType;

    private $relationOrdinal;

    public const RELATION_TYPE_IS_CHILD_OF = 'IS_CHILD_OF';

    public function getReturnValue()
    {
        return 'workRelationId';
    }

    public function getEnumeratedValues()
    {
        return parent::getEnumeratedValues() + [
            'relationType'
        ];
    }

    public function getId()
    {
        return $this->workRelationId;
    }

    public function setId($workRelationId)
    {
        $this->workRelationId = $workRelationId;
    }

    public function getRelatorWorkId()
    {
        return $this->relatorWorkId;
    }

    public function setRelatorWorkId($relatorWorkId)
    {
        $this->relatorWorkId = $relatorWorkId;
    }

    public function getRelatedWorkId()
    {
        return $this->relatedWorkId;
    }

    public function setRelatedWorkId($relatedWorkId)
    {
        $this->relatedWorkId = $relatedWorkId;
    }

    public function getRelationType()
    {
        return $this->relationType;
    }

    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;
    }

    public function getRelationOrdinal()
    {
        return $this->relationOrdinal;
    }

    public function setRelationOrdinal($relationOrdinal)
    {
        $this->relationOrdinal = $relationOrdinal;
    }
}
