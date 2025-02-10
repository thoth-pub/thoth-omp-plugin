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

use ThothApi\GraphQL\Models\Subject as ThothSubject;

class ThothSubjectRepository
{
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
