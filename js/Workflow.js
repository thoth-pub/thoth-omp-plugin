/**
 * @file plugins/generic/thoth/js/Workflow.js
 * 
 * Copyright (c) 2024 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Workflow
 * @ingroup thoth
 *
 * @brief Show notifications when submission is published
 */
(function () {
    if (typeof pkp === 'undefined' || typeof pkp.eventBus === 'undefined') {
        return;
    }

    $.pkp.plugins.generic.thothplugin.showNotification = function (responseObject) {
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

    pkp.eventBus.$on('form-success', (formId, data) => {
        if (formId !== pkp.const.FORM_PUBLISH) {
            return;
        }

        if (data.status !== pkp.const.STATUS_PUBLISHED) {
            return;
        }

        $.ajax({
            type: 'POST',
            url: $.pkp.plugins.generic.thothplugin.notificationUrl,
            success: $.pkp.plugins.generic.thothplugin.showNotification,
            dataType: 'json',
            async: false
        });
    });
}());