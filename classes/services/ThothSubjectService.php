<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothSubjectService.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSubjectService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth Subjects
 */

namespace APP\plugins\generic\thoth\classes\services;

use ThothApi\GraphQL\Enums\SubjectType;

class ThothSubjectService
{
    public $repository;
    private ThothSubjectClassifier $classifier;

    public function __construct($repository, ?ThothSubjectClassifier $classifier = null)
    {
        $this->repository = $repository;
        $this->classifier = $classifier ?? new ThothSubjectClassifier();
    }

    public function register($subject, $sequence, $thothWorkId, $subjectType = SubjectType::KEYWORD)
    {
        $thothSubject = $this->repository->new([
            'workId' => $thothWorkId,
            'subjectType' => $subjectType,
            'subjectCode' => $subject,
            'subjectOrdinal' => $sequence
        ]);

        return $this->repository->add($thothSubject);
    }

    public function registerByPublication($publication)
    {
        $thothBookId = $publication->getData('thothBookId');
        foreach ($this->getPublicationSubjects($publication) as $subject) {
            $this->register(
                $subject['subjectCode'],
                $subject['subjectOrdinal'],
                $thothBookId,
                $subject['subjectType']
            );
        }
    }

    public function synchronizeByPublication($publication, string $thothWorkId): void
    {
        $this->update(
            $this->getPublicationSubjects($publication),
            $thothWorkId,
            $this->repository->getByWorkId($thothWorkId)
        );
    }

    public function update(array $subjects, string $thothWorkId, array $existingSubjects): void
    {
        $remainingSubjects = $existingSubjects;

        foreach ($subjects as $subject) {
            $existingKey = $this->findMatchingSubjectKey($subject, $remainingSubjects);
            if ($existingKey === null) {
                $this->repository->add($this->createSubject($subject, $thothWorkId));
                continue;
            }

            $existingSubject = $remainingSubjects[$existingKey];
            if ($this->subjectNeedsUpdate($subject, $existingSubject)) {
                $thothSubject = $this->createSubject($subject, $thothWorkId);
                $thothSubject->setSubjectId($existingSubject['subjectId']);
                $this->repository->edit($thothSubject);
            }
            unset($remainingSubjects[$existingKey]);
        }

        foreach ($remainingSubjects as $existingSubject) {
            if (isset($existingSubject['subjectId'])) {
                $this->repository->delete($existingSubject['subjectId']);
            }
        }
    }

    private function getPublicationSubjects($publication): array
    {
        $locale = $publication->getData('locale');
        $publicationSubjects = $publication->getData('subjects');
        $keywords = $publication->getData('keywords');
        $subjects = [];
        $seenSubjects = [];

        foreach ($publicationSubjects[$locale] ?? [] as $subject) {
            $classifiedSubject = $this->classifier->classify($subject);
            $this->appendSubject($subjects, $seenSubjects, $classifiedSubject);
        }
        foreach ($keywords[$locale] ?? [] as $keyword) {
            $this->appendSubject($subjects, $seenSubjects, [
                'subjectType' => SubjectType::KEYWORD,
                'subjectCode' => trim((string) ($keyword['name'] ?? $keyword)),
            ]);
        }

        return $subjects;
    }

    private function appendSubject(array &$subjects, array &$seenSubjects, array $subject): void
    {
        if ($subject['subjectCode'] === '') {
            return;
        }

        $key = $subject['subjectType'] . "\0" . $subject['subjectCode'];
        if (isset($seenSubjects[$key])) {
            return;
        }

        $seenSubjects[$key] = true;
        $subject['subjectOrdinal'] = count($subjects) + 1;
        $subjects[] = $subject;
    }

    private function createSubject(array $subject, string $thothWorkId)
    {
        return $this->repository->new([
            'workId' => $thothWorkId,
            'subjectType' => $subject['subjectType'],
            'subjectCode' => $subject['subjectCode'],
            'subjectOrdinal' => $subject['subjectOrdinal'],
        ]);
    }

    private function findMatchingSubjectKey(array $subject, array $existingSubjects): ?int
    {
        foreach ($existingSubjects as $key => $existingSubject) {
            if (
                ($existingSubject['subjectType'] ?? null) === $subject['subjectType']
                && ($existingSubject['subjectCode'] ?? null) === $subject['subjectCode']
            ) {
                return $key;
            }
        }

        return null;
    }

    private function subjectNeedsUpdate(array $subject, array $existingSubject): bool
    {
        return ($existingSubject['subjectOrdinal'] ?? null) !== $subject['subjectOrdinal'];
    }
}
