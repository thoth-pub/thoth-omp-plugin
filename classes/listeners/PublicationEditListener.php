<?php

/**
 * @file plugins/generic/thoth/classes/listeners/PublicationEditListener.inc.php
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

use APP\facades\Repo;
use APP\plugins\generic\thoth\classes\facades\ThothService;
use APP\plugins\generic\thoth\classes\notification\ThothNotification;
use ThothApi\Exception\QueryException;

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
            $bookService->update($publication, $thothBookId);
            if (!$this->isDoiAssignment($params)) {
                $notification->notifySuccess($request, $submission);
            }
        } catch (QueryException $e) {
            $notification->notifyError($request, $submission, $e);
        }

        return false;
    }

    private function isDoiAssignment($params): bool
    {
        return count($params) === 1 && array_key_exists('doiId', $params);
    }
}
