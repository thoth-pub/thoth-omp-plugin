<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothSubjectService.inc.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSubjectService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth subjects
 */

import('plugins.generic.thoth.thoth.models.ThothSubject');

class ThothSubjectService
{
    public function new($params)
    {
        $thothSubject = new ThothSubject();
        $thothSubject->setId($params['subjectId'] ?? null);
        $thothSubject->setWorkId($params['workId'] ?? null);
        $thothSubject->setSubjectType($params['subjectType']);
        $thothSubject->setSubjectCode($params['subjectCode']);
        $thothSubject->setSubjectOrdinal($params['subjectOrdinal']);
        return $thothSubject;
    }

    public function registerKeyword($thothClient, $submissionKeyword, $thothWorkId, $seq = 1)
    {
        $thothSubject = $this->new([
            'workId' => $thothWorkId,
            'subjectType' => ThothSubject::SUBJECT_TYPE_KEYWORD,
            'subjectCode' => $submissionKeyword,
            'subjectOrdinal' => $seq
        ]);

        $thothSubjectId = $thothClient->createSubject($thothSubject);
        $thothSubject->setId($thothSubjectId);

        return $thothSubject;
    }
}
