/**
 * @file plugins/generic/thoth/js/Notification.js
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Notification
 * @ingroup thoth
 *
 * @brief Handle notifications for Javascript events
 */

(function () {
    if (typeof pkp === 'undefined' || typeof pkp.eventBus === 'undefined') {
        return;
    }

    $.pkp.plugins.generic.thothplugin.notification.showNotification = function (responseObject) {
        const { content } = responseObject;
        if (!content?.general) {
            return;
        }

        const notificationsData = content.general;

        Object.entries(notificationsData).forEach(([levelId, notifications]) => {
            Object.values(notifications).forEach(({ addclass, text }) => {
                let type = 'notice';

                switch (addclass) {
                    case 'notifySuccess':
                        type = 'success';
                        break;
                    case 'notifyWarning':
                    case 'notifyError':
                    case 'notifyFormError':
                    case 'notifyForbidden':
                        type = 'warning';
                        break;
                }

                pkp.eventBus.$emit('notify', text, type);
            });
        });
    }

    $.pkp.plugins.generic.thothplugin.notification.triggerNotifications = function () {
        $.ajax({
            type: 'POST',
            url: $.pkp.plugins.generic.thothplugin.notification.notificationUrl,
            success: $.pkp.plugins.generic.thothplugin.notification.showNotification,
            dataType: 'json',
            async: false
        });
    }

    pkp.eventBus.$on('form-success', () => {
        $.pkp.plugins.generic.thothplugin.notification.triggerNotifications();
    });
}());