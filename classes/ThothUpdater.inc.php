<?php

/**
 * @file plugins/generic/thoth/classes/ThothUpdater.php
 *
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothUpdater
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Manage callback functions to update works in Thoth
 */

use APP\facades\Repo;
use APP\notification\Notification;
use ThothApi\Exception\QueryException;

import('plugins.generic.thoth.classes.facades.ThothService');

class ThothUpdater
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function updateWork($hookName, $args)
    {
        $publication = $args[0];
        $request = $args[3];

        $submission = Repo::submission()->get($publication->getData('submissionId'));
        $thothWorkId = $submission->getData('thothWorkId');

        if (!$thothWorkId) {
            return false;
        }

        try {
            ThothService::work()->updateBook($thothWorkId, $submission, $publication);
            ThothNotification::notify(
                $request,
                $submission,
                Notification::NOTIFICATION_TYPE_SUCCESS,
                'plugins.generic.thoth.update.success'
            );
        } catch (QueryException $e) {
            error_log('Failed to send the request to Thoth: ' . $e->getMessage());
            ThothNotification::notify(
                $request,
                $submission,
                Notification::NOTIFICATION_TYPE_ERROR,
                'plugins.generic.thoth.update.error',
                $e->getMessage()
            );
        }

        return false;
    }
}
