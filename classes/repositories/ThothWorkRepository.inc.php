<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothWorkRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothWorkRepository
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth works
 */

use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

class ThothWorkRepository
{
    private const WORK_SELECTION = [
        'workId',
        'workType',
        'workStatus',
        'fullTitle',
        'title',
        'subtitle',
        'edition',
        'imprintId',
        'doi',
        'publicationDate',
        'place',
        'pageCount',
        'imageCount',
        'license',
        'copyrightHolder',
        'landingPage',
        'coverUrl',
        'titles' => [
            'titleId',
            'localeCode',
            'canonical',
        ],
        'abstracts' => [
            'abstractId',
            'localeCode',
            'abstractType',
            'canonical',
        ],
    ];

    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothWork($data);
    }

    public function get($thothWorkId)
    {
        return $this->thothClient->work($thothWorkId, self::WORK_SELECTION);
    }

    public function add($thothWork)
    {
        return $this->thothClient->createWork($thothWork);
    }

    public function edit($thothPatchWork)
    {
        return $this->thothClient->updateWork($thothPatchWork);
    }

    public function delete($thothWorkId)
    {
        return $this->thothClient->deleteWork($thothWorkId);
    }
}
