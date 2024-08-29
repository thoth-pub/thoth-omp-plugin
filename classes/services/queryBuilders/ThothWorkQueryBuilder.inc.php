<?php

/**
 * @file plugins/generic/thoth/classes/services/queryBuilders/ThothWorkQueryBuilder.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkQueryBuilder
 * @ingroup plugins_generic_thoth
 *
 * @brief Class for building graphQL queries for works
 */

import('plugins.generic.thoth.thoth.models.ThothAffiliation');
import('plugins.generic.thoth.thoth.models.ThothContribution');
import('plugins.generic.thoth.thoth.models.ThothContributor');
import('plugins.generic.thoth.thoth.models.ThothReference');
import('plugins.generic.thoth.thoth.models.ThothSubject');
import('plugins.generic.thoth.thoth.models.ThothWorkRelation');
import('plugins.generic.thoth.thoth.models.ThothWork');

class ThothWorkQueryBuilder
{
    private $thothClient;

    private $fields = [];

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;

        $this->fields = $this->getDefaultFields(ThothWork::class);
    }

    public function includeContributions($withContributors = false, $withAffiliation = false)
    {
        $fields = [
            'contributions' => $this->getDefaultFields(ThothContribution::class)
        ];

        if ($withContributors) {
            $fields['contributions']['contributor'] = $this->getDefaultFields(ThothContributor::class);
        }

        if ($withAffiliation) {
            $fields['contributions']['affiliations'] = $this->getDefaultFields(ThothAffiliation::class);
        }

        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    public function includeRelations($withWork = false)
    {
        $fields = [
            'relations' => $this->getDefaultFields(ThothWorkRelation::class)
        ];

        if ($withWork) {
            $fields['relations']['relatedWork'] = $this->getDefaultFields(ThothWork::class);
        }

        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    public function includeSubjects()
    {
        $fields = [
            'subjects' => $this->getDefaultFields(ThothSubject::class)
        ];

        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    public function includeReferences()
    {
        $fields = [
            'references' => $this->getDefaultFields(ThothReference::class)
        ];

        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    public function get($workId)
    {
        $params = [sprintf('workId:"%s"', $workId)];

        return $this->thothClient->query('work', $params, $this->fields);
    }

    private function getDefaultFields($className)
    {
        $object = new $className();
        return $object->getProperties();
    }
}
