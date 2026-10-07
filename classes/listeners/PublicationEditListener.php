<?php

/**
 * @file plugins/generic/thoth/classes/listeners/PublicationEditListener.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationEditListener
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Trigger actions on publication edit event
 */

namespace APP\plugins\generic\thoth\classes\listeners;

use ThothApi\Exception\QueryException;

class PublicationEditListener
{
    private const CATALOG_ENTRY_FIELDS = [
        'datePublished',
        'seriesId',
        'seriesPosition',
        'categoryIds',
        'urlPath',
        'coverImage',
        'place',
        'pageCount',
        'imageCount',
        'thothUploadFrontcover',
    ];

    private $submissionRepository;

    private \Closure $bookService;

    private $notification;

    public function __construct($submissionRepository, \Closure $bookService, $notification)
    {
        $this->submissionRepository = $submissionRepository;
        $this->bookService = $bookService;
        $this->notification = $notification;
    }

    public function updateThothBook($hookName, $args)
    {
        $publication = $args[0];
        $params = $args[2];
        if (!$this->isMetadataEdit($params)) {
            return false;
        }

        $request = $args[3];
        $submission = $this->submissionRepository->get($publication->getData('submissionId'));

        $thothBookId = $submission->getData('thothWorkId');
        if ($thothBookId === null) {
            return false;
        }

        try {
            $warnings = ($this->bookService)()->update(
                $publication,
                $thothBookId,
                $this->isTitleAbstractEdit($params)
            );
            if (!$this->isDoiAssignment($params)) {
                $this->notification->notifySuccess($request, $submission);
            }
            foreach ($warnings as $warning) {
                $this->notification->notifyWarning($request, $submission, $warning);
            }
        } catch (QueryException $e) {
            $this->notification->notifyError($request, $submission, $e);
        }

        return false;
    }

    private function isDoiAssignment($params): bool
    {
        unset($params['id']);
        return count($params) === 1 && array_key_exists('doiId', $params);
    }

    private function isTitleAbstractEdit($params): bool
    {
        return (bool) array_intersect(['prefix', 'title', 'subtitle', 'abstract'], array_keys($params));
    }

    private function isMetadataEdit($params): bool
    {
        return $this->isDoiAssignment($params)
            || $this->isTitleAbstractEdit($params)
            || (bool) array_intersect(self::CATALOG_ENTRY_FIELDS, array_keys($params));
    }
}
