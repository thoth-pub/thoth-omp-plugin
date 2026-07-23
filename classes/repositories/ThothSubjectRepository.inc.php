<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothSubjectRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSubjectRepository
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth subjects
 */

use ThothApi\GraphQL\Inputs\PatchSubject as ThothSubject;

class ThothSubjectRepository
{
    private const WORK_SUBJECTS_SELECTION = [
        'subjects' => [
            'subjectId',
            'workId',
            'subjectType',
            'subjectCode',
            'subjectOrdinal',
        ],
    ];

    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothSubject($data);
    }

    public function get($thothSubjectId)
    {
        return $this->thothClient->subject($thothSubjectId);
    }

    public function getByWorkId($thothWorkId)
    {
        $thothWork = $this->thothClient->work($thothWorkId, self::WORK_SUBJECTS_SELECTION);

        return array_map(
            fn ($subject) => $subject->toArray(),
            $thothWork->getSubjects() ?? []
        );
    }

    public function add($thothSubject)
    {
        return $this->thothClient->createSubject($thothSubject);
    }

    public function edit($thothPatchSubject)
    {
        return $this->thothClient->updateSubject($thothPatchSubject);
    }

    public function delete($thothSubjectId)
    {
        return $this->thothClient->deleteSubject($thothSubjectId);
    }
}
