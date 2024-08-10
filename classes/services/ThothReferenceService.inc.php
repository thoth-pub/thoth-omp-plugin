<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothReferenceService.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothReferenceService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth references
 */

import('plugins.generic.thoth.thoth.models.ThothReference');

class ThothReferenceService
{
    public function new($params)
    {
        $thothReference = new ThothReference();
        $thothReference->setReferenceOrdinal($params['referenceOrdinal']);
        $thothReference->setUnstructuredCitation($params['unstructuredCitation']);
        return $thothReference;
    }

    public function newByCitation($citation)
    {
        $params = [];
        $params['referenceOrdinal'] = $citation->getSequence();
        $params['unstructuredCitation'] = $citation->getRawCitation();
        return $this->new($params);
    }

    public function register($thothClient, $citation, $thothWorkId)
    {
        $thothReference = $this->newByCitation($citation);
        $thothReference->setWorkId($thothWorkId);

        $thothReferenceId = $thothClient->createReference($thothReference);
        $thothReference->setId($thothReferenceId);

        return $thothReference;
    }
}
