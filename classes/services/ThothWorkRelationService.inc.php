<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothWorkRelationService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkRelationService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth work relations
 */

use ThothApi\GraphQL\Enums\RelationType;
use ThothApi\GraphQL\Enums\WorkType;

import('plugins.generic.thoth.classes.exceptions.MetadataSynchronizationException');

class ThothWorkRelationService
{
    public $repository;
    public $chapterService;

    public function __construct($repository, $chapterService)
    {
        $this->repository = $repository;
        $this->chapterService = $chapterService;
    }

    public function register($chapter, $thothRelatedWorkId, $thothImprintId)
    {
        $thothChapterId = $this->chapterService->register($chapter, $thothImprintId);

        $thothWorkRelation = $this->repository->new([
            'relatorWorkId' => $thothChapterId,
            'relatedWorkId' => $thothRelatedWorkId,
            'relationType' => RelationType::IS_CHILD_OF,
            'relationOrdinal' => ($chapter->getSequence() + 1)
        ]);

        return $this->repository->add($thothWorkRelation);
    }

    public function registerByPublication($publication, $thothImprintId)
    {
        $thothBookId = $publication->getData('thothBookId');
        $chapters = DAORegistry::getDAO('ChapterDAO')
            ->getByPublicationId($publication->getId())
            ->toArray();
        foreach ($chapters as $chapter) {
            $this->register($chapter, $thothBookId, $thothImprintId);
        }
    }

    public function synchronizeByPublication($publication, $thothBookId)
    {
        $chapters = DAORegistry::getDAO('ChapterDAO')
            ->getByPublicationId($publication->getId())
            ->toArray();
        $thothBook = $this->repository->getByWorkId($thothBookId);

        return $this->update(
            $chapters,
            $thothBookId,
            $thothBook['imprintId'],
            $thothBook['relations'] ?? []
        );
    }

    public function update(
        array $chapters,
        $thothBookId,
        $thothImprintId,
        array $existingRelations
    ) {
        $existingRelations = array_values(array_filter(
            $existingRelations,
            function (array $relation) {
                return ($relation['relationType'] ?? null) === RelationType::HAS_CHILD
                    && ($relation['relatedWork']['workType'] ?? null) === WorkType::BOOK_CHAPTER;
            }
        ));
        $desiredChapters = $this->getDesiredChapters($chapters, $thothImprintId);
        $this->assertUniqueDesiredDois($desiredChapters);
        $desiredLandingPageCounts = $this->countDesiredValues($desiredChapters, 'landingPage');
        $desiredTitleCounts = $this->countDesiredValues($desiredChapters, 'title');

        $remainingRelations = $existingRelations;
        $matchedChapters = [];
        $newChapters = [];
        foreach ($desiredChapters as $desiredChapter) {
            $relationKey = $this->findMatchingRelationKey(
                $desiredChapter,
                $remainingRelations,
                $desiredLandingPageCounts,
                $desiredTitleCounts
            );
            if ($relationKey === null) {
                $newChapters[] = $desiredChapter;
                continue;
            }

            $matchedChapters[] = [
                'desired' => $desiredChapter,
                'relation' => $remainingRelations[$relationKey],
            ];
            unset($remainingRelations[$relationKey]);
        }

        foreach ($remainingRelations as $relation) {
            $this->repository->delete($relation['workRelationId']);
            $this->chapterService->delete($relation['relatedWork']['workId']);
        }

        $relationsToReorder = array_filter(
            $matchedChapters,
            function (array $match) {
                return (int) $match['relation']['relationOrdinal'] !== $match['desired']['ordinal'];
            }
        );
        $temporaryOrdinal = $this->getTemporaryOrdinal($existingRelations, $desiredChapters);
        foreach ($relationsToReorder as $match) {
            $this->editRelationOrdinal($match['relation'], $temporaryOrdinal++);
        }

        $deletionsSkipped = false;
        foreach ($matchedChapters as $match) {
            $deletionsSkipped = $this->chapterService->update(
                $match['desired']['chapter'],
                $match['relation']['relatedWork'],
                $thothImprintId,
                $match['desired']['work']
            ) || $deletionsSkipped;
        }

        foreach ($newChapters as $desiredChapter) {
            $thothChapterId = $this->chapterService->register(
                $desiredChapter['chapter'],
                $thothImprintId,
                $desiredChapter['work']
            );
            $relation = $this->repository->new([
                'relatorWorkId' => $thothBookId,
                'relatedWorkId' => $thothChapterId,
                'relationType' => RelationType::HAS_CHILD,
                'relationOrdinal' => $desiredChapter['ordinal'],
            ]);
            $this->repository->add($relation);
        }

        foreach ($relationsToReorder as $match) {
            $this->editRelationOrdinal($match['relation'], $match['desired']['ordinal']);
        }

        return $deletionsSkipped;
    }

    private function getDesiredChapters(array $chapters, $thothImprintId)
    {
        $desiredChapters = [];
        foreach ($chapters as $chapter) {
            $work = $this->chapterService->getDesiredWork($chapter, $thothImprintId);
            $desiredChapters[] = [
                'chapter' => $chapter,
                'work' => $work,
                'ordinal' => (int) $chapter->getSequence() + 1,
                'doi' => $this->normalizeDoi($work->getDoi()),
                'landingPage' => $this->normalizeUrl($work->getLandingPage()),
                'title' => $this->normalizeTitle($chapter->getLocalizedFullTitle()),
            ];
        }

        return $desiredChapters;
    }

    private function findMatchingRelationKey(
        array $desiredChapter,
        array $relations,
        array $desiredLandingPageCounts,
        array $desiredTitleCounts
    ) {
        $key = $this->findUniqueMatch(
            $relations,
            function (array $relation) use ($desiredChapter) {
                return $desiredChapter['doi'] !== null
                    && $this->normalizeDoi($relation['relatedWork']['doi'] ?? null) === $desiredChapter['doi'];
            },
            'DOI'
        );
        if ($key !== null) {
            return $key;
        }

        $landingPage = $desiredChapter['landingPage'];
        if ($landingPage !== null && ($desiredLandingPageCounts[$landingPage] ?? 0) === 1) {
            $key = $this->findUniqueMatch(
                $relations,
                function (array $relation) use ($landingPage) {
                    return $this->normalizeUrl(
                        $relation['relatedWork']['landingPage'] ?? null
                    ) === $landingPage;
                },
                'landing page'
            );
            if ($key !== null) {
                return $key;
            }
        }

        $title = $desiredChapter['title'];
        if ($title !== '' && ($desiredTitleCounts[$title] ?? 0) === 1) {
            $key = $this->findUniqueMatch(
                $relations,
                function (array $relation) use ($title) {
                    return $this->normalizeTitle(
                        $relation['relatedWork']['fullTitle'] ?? null
                    ) === $title;
                },
                'title'
            );
            if ($key !== null) {
                return $key;
            }
        }

        return $this->findUniqueMatch(
            $relations,
            function (array $relation) use ($desiredChapter) {
                return (int) ($relation['relationOrdinal'] ?? 0) === $desiredChapter['ordinal'];
            },
            'relation ordinal'
        );
    }

    private function findUniqueMatch(array $relations, callable $matches, $identity)
    {
        $matchingKeys = [];
        foreach ($relations as $key => $relation) {
            if ($matches($relation)) {
                $matchingKeys[] = $key;
            }
        }

        if (count($matchingKeys) > 1) {
            throw new MetadataSynchronizationException(
                "Ambiguous Thoth chapters with the same {$identity}"
            );
        }

        return $matchingKeys[0] ?? null;
    }

    private function assertUniqueDesiredDois(array $desiredChapters)
    {
        $counts = $this->countDesiredValues($desiredChapters, 'doi');
        foreach ($counts as $count) {
            if ($count > 1) {
                throw new MetadataSynchronizationException('OMP chapters have the same DOI');
            }
        }
    }

    private function countDesiredValues(array $desiredChapters, $key)
    {
        $counts = [];
        foreach ($desiredChapters as $desiredChapter) {
            $value = $desiredChapter[$key];
            if ($value === null || $value === '') {
                continue;
            }
            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        return $counts;
    }

    private function getTemporaryOrdinal(array $existingRelations, array $desiredChapters)
    {
        $ordinals = array_map(
            function (array $relation) {
                return (int) ($relation['relationOrdinal'] ?? 0);
            },
            $existingRelations
        );
        $ordinals = array_merge($ordinals, array_column($desiredChapters, 'ordinal'));

        return max(array_merge([0], $ordinals)) + count($existingRelations) + 1;
    }

    private function editRelationOrdinal(array $relation, $ordinal)
    {
        $thothRelation = $this->repository->new([
            'workRelationId' => $relation['workRelationId'],
            'relatorWorkId' => $relation['relatorWorkId'],
            'relatedWorkId' => $relation['relatedWorkId'],
            'relationType' => $relation['relationType'],
            'relationOrdinal' => $ordinal,
        ]);
        $this->repository->edit($thothRelation);
    }

    private function normalizeDoi($doi)
    {
        if (empty($doi)) {
            return null;
        }

        $doi = preg_replace('#^(?:https?://(?:dx\.)?doi\.org/|doi:\s*)#i', '', trim($doi));
        return strtolower(rtrim($doi, '/'));
    }

    private function normalizeUrl($url)
    {
        if (empty($url)) {
            return null;
        }

        return rtrim(trim($url), '/');
    }

    private function normalizeTitle($title)
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim((string) $title)));
    }
}
