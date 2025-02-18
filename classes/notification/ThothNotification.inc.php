<?php

/**
 * @file plugins/generic/thoth/classes/notification/ThothNotification.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothNotification
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Manage function to display plugin notifications
 */

use APP\core\Application;
use APP\facades\Repo;
use APP\notification\NotificationManager;
use PKP\log\event\PKPSubmissionEventLogEntry;

class ThothNotification
{
    public function notifySuccess($request, $submission)
    {
        $this->notify($request, $submission, NOTIFICATION_TYPE_SUCCESS, 'plugins.generic.thoth.register.success');
    }

    public function notifyError($request, $submission, $error)
    {
        error_log("Failed to send the request to Thoth: {$error}");
        $this->notify($request, $submission, NOTIFICATION_TYPE_ERROR, 'plugins.generic.thoth.register.error', $error);
    }

    public function notify($request, $submission, $notificationType, $messageKey, $error = null)
    {
        $currentUser = $request->getUser();
        $notificationMgr = new NotificationManager();
        $notificationMgr->createTrivialNotification(
            $currentUser->getId(),
            $notificationType,
            ['contents' => __($messageKey)]
        );

        $this->logInfo($request, $submission, $messageKey . '.log', $error);
    }

    public function logInfo($request, $submission, $messageKey, $error = null)
    {
        $currentUser = $request->getUser();
        $eventLog = Repo::eventLog()->newDataObject([
            'assocType' => Application::ASSOC_TYPE_SUBMISSION,
            'assocId' => $submission->getId(),
            'eventType' => PKPSubmissionEventLogEntry::SUBMISSION_LOG_CREATE_VERSION,
            'userId' => $currentUser->getId(),
            'message' => $messageKey,
            'isTranslated' => false,
            'reason' => $error,
            'dateLogged' => Core::getCurrentDate()
        ]);
        Repo::eventLog()->add($eventLog);
    }

    public function addJavaScriptData($request, $templateMgr)
    {
        $data = ['notificationUrl' => $request->url(null, 'notification', 'fetchNotification')];

        $output = '$.pkp.plugins.generic = $.pkp.plugins.generic || {};';
        $output .= '$.pkp.plugins.generic.thothplugin = $.pkp.plugins.generic.thothplugin || {};';
        $output .= '$.pkp.plugins.generic.thothplugin.notification = ' . json_encode($data) . ';';

        $templateMgr->addJavaScript(
            'notificationData',
            $output,
            [
                'inline' => true,
                'contexts' => 'backend',
            ]
        );
    }

    public function addJavaScript($request, $templateMgr, $plugin)
    {
        $templateMgr->addJavaScript(
            'notification',
            $request->getBaseUrl() . '/' . $plugin->getPluginPath() . '/js/Notification.js',
            [
                'contexts' => 'backend',
                'priority' => STYLE_SEQUENCE_LAST,
            ]
        );
    }
}
