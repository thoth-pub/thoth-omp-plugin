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

class ThothSubjectService
{
    public $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    public function register($subject, $sequence, $thothWorkId)
    {
        $thothSubject = $this->repository->new([
            'workId' => $thothWorkId,
            'subjectType' => SubjectType::KEYWORD,
            'subjectCode' => $subject,
            'subjectOrdinal' => $sequence
        ]);

        return $this->repository->add($thothSubject);
    }

    public function registerByPublication($publication)
    {
        $thothBookId = $publication->getData('thothBookId');
        $locale = $publication->getData('locale');
        $keywords = $publication->getData('keywords');
        foreach ($keywords[$locale] ?? [] as $seq => $keyword) {
            $this->register($keyword, ($seq + 1), $thothBookId);
        }
    }
}
