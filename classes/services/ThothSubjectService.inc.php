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

use ThothApi\GraphQL\Models\Subject as ThothSubject;

class ThothSubjectService
{
    public function new($params)
    {
        $thothSubject = new ThothSubject();
        $thothSubject->setSubjectId($params['subjectId'] ?? null);
        $thothSubject->setWorkId($params['workId'] ?? null);
        $thothSubject->setSubjectType($params['subjectType']);
        $thothSubject->setSubjectCode($params['subjectCode']);
        $thothSubject->setSubjectOrdinal($params['subjectOrdinal']);
        return $thothSubject;
    }

    public function registerKeyword($submissionKeyword, $thothWorkId, $seq = 1)
    {
        $thothSubject = $this->new([
            'workId' => $thothWorkId,
            'subjectType' => ThothSubject::SUBJECT_TYPE_KEYWORD,
            'subjectCode' => $submissionKeyword,
            'subjectOrdinal' => $seq
        ]);

        $thothClient = ThothContainer::getInstance()->get('client');
        $thothSubjectId = $thothClient->createSubject($thothSubject);
        $thothSubject->setSubjectId($thothSubjectId);

        return $thothSubject;
    }

    public function updateKeywords($thothKeywords, $publication, $thothWorkId)
    {
        $submission = Services::get('submission')->get($publication->getData('submissionId'));
        $locale = $submission->getLocale();

        $keywords = $publication->getData('keywords');
        if (!isset($keywords[$locale])) {
            return;
        }

        $thothClient = ThothContainer::getInstance()->get('client');

        $localizedKeywords = $keywords[$locale];
        $thothKeywordData = array_column($thothKeywords, 'subjectCode', 'subjectId');
        foreach ($thothKeywordData as $subjectId => $subjectCode) {
            if (!in_array($subjectCode, $localizedKeywords)) {
                $thothClient->deleteSubject($subjectId);
            }
        }

        foreach ($localizedKeywords as $order => $keyword) {
            if (!in_array($keyword, $thothKeywordData)) {
                $this->registerKeyword($keyword, $thothWorkId, $order + 1);
            }
        }
    }
}
