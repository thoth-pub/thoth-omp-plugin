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

use ThothApi\GraphQL\Models\Reference as ThothReference;

import('lib.pkp.classes.citation.Citation');
import('lib.pkp.classes.citation.CitationListTokenizerFilter');

class ThothReferenceService
{
    public function new($params)
    {
        $thothReference = new ThothReference();
        $thothReference->setReferenceId($params['referenceId'] ?? null);
        $thothReference->setWorkId($params['workId'] ?? null);
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

    public function register($citation, $thothWorkId)
    {
        $thothReference = $this->newByCitation($citation);
        $thothReference->setWorkId($thothWorkId);

        $thothClient = ThothContainer::getInstance()->get('client');
        $thothReferenceId = $thothClient->createReference($thothReference);
        $thothReference->setReferenceId($thothReferenceId);

        return $thothReference;
    }

    public function updateReferences($thothReferences, $publication, $thothWorkId)
    {
        $oldPublication = Services::get('publication')->get($publication->getId());

        if ($publication->getData('citationsRaw') == $oldPublication->getData('citationsRaw')) {
            return;
        }

        $thothClient = ThothContainer::getInstance()->get('client');

        foreach ($thothReferences as $thothReference) {
            $thothClient->deleteReference($thothReference['referenceId']);
        }

        $citationsRaw = $publication->getData('citationsRaw');
        $citationTokenizer = new CitationListTokenizerFilter();
        $citationStrings = $citationTokenizer->execute($citationsRaw);
        if (!is_array($citationStrings)) {
            return;
        }

        foreach ($citationStrings as $order => $citationString) {
            if (!empty(trim($citationString))) {
                $citation = new Citation($citationString);
                $citation->setSequence($order + 1);
                $this->register($citation, $thothWorkId);
            }
        }
    }
}
