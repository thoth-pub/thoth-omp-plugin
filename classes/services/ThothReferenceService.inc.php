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
        return $this->repository->add(
            $this->createReference($this->getCitationReference($citation), $thothWorkId)
        );
    }

    public function registerByPublication($publication)
    {
        $thothBookId = $publication->getData('thothBookId');
        $citations = DAORegistry::getDAO('CitationDAO')
            ->getByPublicationId($publication->getId())
            ->toArray();
        foreach ($citations as $citation) {
            $this->register($citation, $thothBookId);
        }
    }

    public function synchronizeByPublication($publication, $thothWorkId)
    {
        $citations = DAORegistry::getDAO('CitationDAO')
            ->getByPublicationId($publication->getId())
            ->toArray();
        $references = [];
        foreach ($citations as $citation) {
            $reference = $this->getCitationReference($citation);
            if ($reference['unstructuredCitation'] !== '') {
                $references[] = $reference;
            }
        }

        $this->update($references, $thothWorkId, $this->repository->getByWorkId($thothWorkId));
    }

    public function update(array $references, $thothWorkId, array $existingReferences)
    {
        $remainingReferences = $existingReferences;
        $matchedReferences = [];
        $newReferences = [];

        foreach ($references as $reference) {
            $existingKey = $this->findMatchingReferenceKey($reference, $remainingReferences);
            if ($existingKey === null) {
                $newReferences[] = $reference;
                continue;
            }

            $matchedReferences[] = [
                'reference' => $reference,
                'existingReference' => $remainingReferences[$existingKey],
            ];
            unset($remainingReferences[$existingKey]);
        }

        foreach ($remainingReferences as $existingReference) {
            if (isset($existingReference['referenceId'])) {
                $this->repository->delete($existingReference['referenceId']);
            }
        }

        $hasOrdinalCollisions = $this->hasOrdinalCollisions($references, $matchedReferences);
        $temporaryOrdinal = $this->getNextAvailableOrdinal($references, $existingReferences);
        if ($hasOrdinalCollisions) {
            foreach ($matchedReferences as $matchedReference) {
                if ($this->referenceNeedsUpdate(
                    $matchedReference['reference'],
                    $matchedReference['existingReference']
                )) {
                    $this->editReference(
                        $matchedReference['reference'],
                        $matchedReference['existingReference'],
                        $thothWorkId,
                        $temporaryOrdinal++
                    );
                }
            }
        }

        foreach ($newReferences as $reference) {
            $this->repository->add($this->createReference($reference, $thothWorkId));
        }

        foreach ($matchedReferences as $matchedReference) {
            if ($this->referenceNeedsUpdate(
                $matchedReference['reference'],
                $matchedReference['existingReference']
            )) {
                $this->editReference(
                    $matchedReference['reference'],
                    $matchedReference['existingReference'],
                    $thothWorkId
                );
            }
        }
    }

    private function createReference(array $reference, $thothWorkId)
    {
        $data = [
            'workId' => $thothWorkId,
            'referenceOrdinal' => $reference['referenceOrdinal'],
            'unstructuredCitation' => $reference['unstructuredCitation'],
        ];
        $doi = $this->getReferenceDoi($reference);
        if ($doi !== null) {
            $data['doi'] = $doi;
        }

        return $this->repository->new($data);
    }

    private function getCitationReference($citation)
    {
        return [
            'referenceOrdinal' => (int) $citation->getSequence(),
            'unstructuredCitation' => trim((string) $citation->getRawCitation()),
        ];
    }

    private function editReference(
        array $reference,
        array $existingReference,
        $thothWorkId,
        $referenceOrdinal = null
    ) {
        if ($referenceOrdinal !== null) {
            $reference['referenceOrdinal'] = $referenceOrdinal;
        }
        $thothReference = $this->createReference($reference, $thothWorkId);
        $thothReference->setReferenceId($existingReference['referenceId']);
        $this->repository->edit($thothReference);
    }

    private function findMatchingReferenceKey(array $reference, array $existingReferences)
    {
        $doi = $this->getReferenceDoi($reference);
        if ($doi !== null) {
            foreach ($existingReferences as $key => $existingReference) {
                if ($this->getReferenceDoi($existingReference) === $doi) {
                    return $key;
                }
            }
        }

        $citation = $this->normalizeCitation($reference['unstructuredCitation'] ?? '');
        foreach ($existingReferences as $key => $existingReference) {
            if ($this->normalizeCitation($existingReference['unstructuredCitation'] ?? '') === $citation) {
                return $key;
            }
        }

        return null;
    }

    private function referenceNeedsUpdate(array $reference, array $existingReference)
    {
        if ((int) ($existingReference['referenceOrdinal'] ?? 0) !== (int) $reference['referenceOrdinal']) {
            return true;
        }

        if (
            $this->normalizeCitation($existingReference['unstructuredCitation'] ?? '')
            !== $this->normalizeCitation($reference['unstructuredCitation'] ?? '')
        ) {
            return true;
        }

        $doi = $this->getReferenceDoi($reference);
        return $doi !== null && $doi !== $this->normalizeDoi($existingReference['doi'] ?? null);
    }

    private function hasOrdinalCollisions(array $references, array $matchedReferences)
    {
        $occupiedOrdinals = [];
        $matchedReferenceIds = [];
        foreach ($matchedReferences as $matchedReference) {
            $reference = $matchedReference['reference'];
            $existingReference = $matchedReference['existingReference'];
            $occupiedOrdinals[$existingReference['referenceOrdinal']] = $existingReference['referenceId'];
            $matchedReferenceIds[$reference['referenceOrdinal']] = $existingReference['referenceId'];
        }

        foreach ($references as $reference) {
            $ordinal = $reference['referenceOrdinal'];
            $occupyingReferenceId = $occupiedOrdinals[$ordinal] ?? null;
            $matchedReferenceId = $matchedReferenceIds[$ordinal] ?? null;
            if ($occupyingReferenceId !== null && $occupyingReferenceId !== $matchedReferenceId) {
                return true;
            }
        }

        return false;
    }

    private function getNextAvailableOrdinal(array $references, array $existingReferences)
    {
        $highestOrdinal = 0;
        foreach (array_merge($references, $existingReferences) as $reference) {
            $highestOrdinal = max($highestOrdinal, (int) ($reference['referenceOrdinal'] ?? 0));
        }

        return $highestOrdinal + 1;
    }

    private function getReferenceDoi(array $reference)
    {
        $doi = $this->normalizeDoi($reference['doi'] ?? null);
        if ($doi !== null) {
            return $doi;
        }

        $citation = (string) ($reference['unstructuredCitation'] ?? '');
        if (!preg_match('~10\.\d{4,9}/[-._;()/:a-z0-9]+~i', $citation, $matches)) {
            return null;
        }

        return $this->normalizeDoi($matches[0]);
    }

    private function normalizeDoi($doi)
    {
        $doi = trim((string) $doi);
        if ($doi === '') {
            return null;
        }

        $doi = preg_replace('~^(?:https?://(?:dx\.)?doi\.org/|doi:\s*)~i', '', $doi);
        $doi = rtrim($doi, " \t\n\r\0\x0B.,;:");
        while (substr($doi, -1) === ')' && substr_count($doi, '(') < substr_count($doi, ')')) {
            $doi = substr($doi, 0, -1);
        }

        return $doi === '' ? null : strtolower($doi);
    }

    private function normalizeCitation($citation)
    {
        $citation = preg_replace('/\s+/u', ' ', trim((string) $citation));
        return mb_strtolower($citation, 'UTF-8');
    }
}
