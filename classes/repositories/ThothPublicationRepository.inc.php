<?php

/**
 * @file plugins/generic/thoth/tests/classes/repositories/ThothPublicationRepository.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationRepository
 * @ingroup plugins_generic_thoth
 *
 * @brief A repository to manage Thoth publications
 */

use ThothApi\GraphQL\Inputs\PatchPublication as ThothPublication;

class ThothPublicationRepository
{
    private const WORK_PUBLICATIONS_SELECTION = [
        'workStatus',
        'publications' => [
            'publicationId',
            'publicationType',
            'workId',
            'isbn',
            'accessibilityStandard',
            'accessibilityAdditionalStandard',
            'accessibilityException',
            'accessibilityReportUrl',
            'locations' => [
                'locationId',
                'landingPage',
                'fullTextUrl',
                'locationPlatform',
                'canonical',
            ],
        ],
    ];

    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothPublication($data);
    }

    public function get($thothPublicationId)
    {
        return $this->thothClient->publication($thothPublicationId);
    }

    public function getIdByType($thothWorkId, $thothPublicationType)
    {
        $thothWork = $this->thothClient->work($thothWorkId, [
            'publications' => ['publicationId', 'publicationType'],
        ]);

        foreach ($thothWork->getPublications() ?? [] as $thothPublication) {
            if ($thothPublication->getPublicationType() === $thothPublicationType) {
                return $thothPublication->getPublicationId();
            }
        }

        return null;
    }

    public function getFilesByWorkId($thothWorkId)
    {
        $thothWork = $this->thothClient->work($thothWorkId, [
            'workId',
            'publications' => [
                'publicationId',
                'publicationType',
                'file' => [
                    'fileId',
                    'cdnUrl',
                    'mimeType',
                    'objectKey',
                ],
            ],
        ]);

        $files = [];
        foreach ($thothWork->getPublications() ?? [] as $thothPublication) {
            $file = $thothPublication->getFile();
            if ($file) {
                $files[] = [
                    'publicationType' => $thothPublication->getPublicationType(),
                    'file' => $file,
                ];
            }
        }

        return $files;
    }

    public function getByWorkId($thothWorkId)
    {
        $thothWork = $this->thothClient->work($thothWorkId, self::WORK_PUBLICATIONS_SELECTION);

        return [
            'workStatus' => $thothWork->getWorkStatus(),
            'publications' => array_map(
                function ($publication) {
                    return $publication->toArray();
                },
                $thothWork->getPublications() ?? []
            ),
        ];
    }

    public function find($filter)
    {
        $thothPublications =  $this->thothClient->publications([
            'filter' => $filter,
            'limit' => 1
        ]);

        if (empty($thothPublications)) {
            return null;
        }

        return array_shift($thothPublications);
    }

    public function add($thothPublication)
    {
        return $this->thothClient->createPublication($thothPublication);
    }

    public function edit($thothPatchPublication)
    {
        return $this->thothClient->updatePublication($thothPatchPublication);
    }

    public function delete($thothPublicationId)
    {
        return $this->thothClient->deletePublication($thothPublicationId);
    }
}
