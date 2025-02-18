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
    public function updateThothBook($hookName, $args)
    {
        $publication = $args[0];
        $request = $args[3];
        $submission = Repo::submission()->get($publication->getData('submissionId'));

        $thothBookId = $submission->getData('thothWorkId');
        if ($thothBookId === null) {
            return false;
        }

        $thothNotification = new ThothNotification();
        try {
            ThothService::book()->update($publication, $thothBookId);
            $thothNotification->notifySuccess($request, $submission);
        } catch (QueryException $e) {
            $thothNotification->notifyError($request, $submission, $e->getMessage());
        }

        return false;
    }
}
