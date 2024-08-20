<?php

class ThothNotification
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public static function notify($request, $notificationType, $message)
    {
        $currentUser = $request->getUser();
        $notificationMgr = new NotificationManager();
        $notificationMgr->createTrivialNotification(
            $currentUser->getId(),
            $notificationType,
            ['contents' => $message]
        );
        return new JSONMessage(false);
    }

    public function addNotificationScript($hookName, $args)
    {
        $templateMgr = $args[0];
        $request = Application::get()->getRequest();

        $data = ['notificationUrl' => $request->url(null, 'notification', 'fetchNotification')];

        $templateMgr->addJavaScript(
            'notificationData',
            '$.pkp.plugins.generic = $.pkp.plugins.generic || {};' .
                '$.pkp.plugins.generic.thothplugin.notification = ' . json_encode($data) . ';',
            [
                'inline' => true,
                'contexts' => 'backend',
            ]
        );

        $templateMgr->addJavaScript(
            'notification',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/js/Notification.js',
            [
                'contexts' => 'backend',
                'priority' => STYLE_SEQUENCE_LATE,
            ]
        );
    }
}
