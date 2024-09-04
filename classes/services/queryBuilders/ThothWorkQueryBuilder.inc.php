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

import('plugins.generic.thoth.thoth.ThothQueryFields');

class ThothWorkQueryBuilder
{
    private $thothClient;

    private $fields = [];

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;

        $this->fields = ThothQueryFields::work();
    }

    public function includeContributions($withContributors = false, $withAffiliation = false)
    {
        $fields = [
            'contributions' => ThothQueryFields::contribution()
        ];

        if ($withContributors) {
            $fields['contributions']['contributor'] = ThothQueryFields::contributor();
        }

        if ($withAffiliation) {
            $fields['contributions']['affiliations'] = ThothQueryFields::affiliation();
        }

        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    public function includeRelations($withWork = false)
    {
        $fields = [
            'relations' => ThothQueryFields::workRelation()
        ];

        if ($withWork) {
            $fields['relations']['relatedWork'] = ThothQueryFields::work();
        }

        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    public function includeSubjects()
    {
        $fields = [
            'subjects' => ThothQueryFields::subject()
        ];

        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    public function includeReferences()
    {
        $fields = [
            'references' => ThothQueryFields::reference()
        ];

        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    public function includePublications($withLocations = false)
    {
        $fields = [
            'publications' => ThothQueryFields::publication()
        ];

        if ($withLocations) {
            $fields['publications']['locations'] = ThothQueryFields::location();
        }

        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    public function get($workId)
    {
        $params = [sprintf('workId:"%s"', $workId)];

        return $this->thothClient->query('work', $params, $this->fields);
    }
}
