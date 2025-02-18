<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothReferenceService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothReferenceService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth References
 */

use PKP\db\DAORegistry;

class ThothReferenceService
{
    public $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    public function register($citation, $thothWorkId)
    {
        $thothReference = $this->repository->new([
            'workId' => $thothWorkId,
            'referenceOrdinal' => $citation->getSequence(),
            'unstructuredCitation' => $citation->getRawCitation()
        ]);

        return $this->repository->add($thothReference);
    }

    public function registerByPublication($publication)
    {
        $thothBookId = $publication->getData('thothBookId');
        $citations = DAORegistry::getDAO('CitationDAO')
            ->getByPublicationId($publication->getId())
            ->toArray();
        foreach ($citations as $citation) {
            ThothService::reference()->register($citation, $thothBookId);
        }
    }
}
