<?php

/**
 * @file plugins/generic/thoth/classes/listeners/PublicationEditListener.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationEditListener
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Trigger actions on publication edit event
 */

use APP\facades\Repo;
use ThothApi\Exception\QueryException;

import('plugins.generic.thoth.classes.facades.ThothService');
import('plugins.generic.thoth.classes.notification.ThothNotification');

class PublicationEditListener
{
    private $submissionRepository;
    private $bookService;
    private $notification;

    public function __construct($submissionRepository = null, $bookService = null, $notification = null)
    {
        $this->submissionRepository = $submissionRepository;
        $this->bookService = $bookService;
        $this->notification = $notification;
    }

    public function updateThothBook($hookName, $args)
    {
        $publication = $args[0];
        $params = $args[2];
        $request = $args[3];
        $submissionRepository = $this->submissionRepository ?: Repo::submission();
        $submission = $submissionRepository->get($publication->getData('submissionId'));

        $thothBookId = $submission->getData('thothWorkId');
        if ($thothBookId === null) {
            return false;
        }

        $bookService = $this->bookService ?: ThothService::book();
        $notification = $this->notification ?: new ThothNotification();
        try {
            $warning = $bookService->update(
                $publication,
                $thothBookId,
                $this->isTitleAbstractEdit($params)
            );
            if (!$this->isDoiAssignment($params)) {
                $notification->notifySuccess($request, $submission);
            }
            if ($warning) {
                $notification->notifyWarning($request, $submission, $warning);
            }
        } catch (QueryException $e) {
            $notification->notifyError($request, $submission, $e);
        }

        return false;
    }

    private function isDoiAssignment($params)
    {
        return count($params) === 1 && array_key_exists('doiId', $params);
    }

    private function isTitleAbstractEdit($params)
    {
        return (bool) array_intersect(['prefix', 'title', 'subtitle', 'abstract'], array_keys($params));
    }
}
