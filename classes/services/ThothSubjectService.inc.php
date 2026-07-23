<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothSubjectService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSubjectService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth Subjects
 */

use ThothApi\GraphQL\Enums\SubjectType;

import('plugins.generic.thoth.classes.services.ThothSubjectClassifier');

class ThothSubjectService
{
    public $repository;
    private $classifier;

    public function __construct($repository, $classifier = null)
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

    public function synchronizeByPublication($publication, $thothWorkId)
    {
        $this->update(
            $this->getPublicationSubjects($publication),
            $thothWorkId,
            $this->repository->getByWorkId($thothWorkId)
        );
    }

    public function update(array $subjects, $thothWorkId, array $existingSubjects)
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

    private function getPublicationSubjects($publication)
    {
        $locale = $publication->getData('locale');
        $publicationSubjects = $publication->getData('subjects');
        $keywords = $publication->getData('keywords');
        $subjects = [];
        $seenSubjects = [];

        foreach ($publicationSubjects[$locale] ?? [] as $subject) {
            $this->appendSubject($subjects, $seenSubjects, $this->classifier->classify($subject));
        }
        foreach ($keywords[$locale] ?? [] as $keyword) {
            $this->appendSubject($subjects, $seenSubjects, [
                'subjectType' => SubjectType::KEYWORD,
                'subjectCode' => trim((string) ($keyword['name'] ?? $keyword)),
            ]);
        }

        return $subjects;
    }

    private function appendSubject(array &$subjects, array &$seenSubjects, array $subject)
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

    private function createSubject(array $subject, $thothWorkId)
    {
        return $this->repository->new([
            'workId' => $thothWorkId,
            'subjectType' => $subject['subjectType'],
            'subjectCode' => $subject['subjectCode'],
            'subjectOrdinal' => $subject['subjectOrdinal'],
        ]);
    }

    private function findMatchingSubjectKey(array $subject, array $existingSubjects)
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

    private function subjectNeedsUpdate(array $subject, array $existingSubject)
    {
        return ($existingSubject['subjectOrdinal'] ?? null) !== $subject['subjectOrdinal'];
    }
}
