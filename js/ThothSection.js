/**
 * @file plugins/generic/thoth/js/ThothSection.js
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSection
 * @ingroup thoth
 *
 * @brief Handle Thoth section action in workflow page
 */

(function () {
    if (typeof pkp === 'undefined' || typeof pkp.eventBus === 'undefined') {
        return;
    }

    if (typeof $.pkp.plugins.generic.thothplugin.workflow === 'undefined') {
        return;
    }

    $.pkp.plugins.generic.thothplugin.workflow.loading = false;

    $.pkp.plugins.generic.thothplugin.workflow.openRegister = function (publicationId) {
        const focusEl = document.activeElement;

        const sourceUrl = $.pkp.plugins.generic.thothplugin.workflow.registerUrl.replace(
            '__publicationId__',
            publicationId
        );

        var opts = {
            title: $.pkp.plugins.generic.thothplugin.workflow.registerTitle,
            url: sourceUrl,
            closeCallback: () => focusEl.focus(),
            closeOnFormSuccessId: 'register'
        };

        $(
            '<div id="' +
            $.pkp.classes.Helper.uuid() +
            '" ' +
            'class="pkp_modal pkpModalWrapper" tabIndex="-1"></div>'
        ).pkpHandler('$.pkp.controllers.modal.AjaxModalHandler', opts);
    }

    $.pkp.plugins.generic.thothplugin.workflow.updateMetadata = function (publicationId) {
        $.pkp.plugins.generic.thothplugin.workflow.loading = true;

        const url = $.pkp.plugins.generic.thothplugin.workflow.publicationUrl.replace(
            '__publicationId__',
            publicationId
        );

        $.ajax({
            method: 'PUT',
            url: url,
            headers: {
                'X-Csrf-Token': pkp.currentUser.csrfToken,
                'X-Http-Method-Override': 'PUT'
            },
            error: function(r) {
                pkp.eventBus.$emit('notify', r.responseJSON.errorMessage, 'warning');
            },
            complete() {
                $.ajax({
                    type: 'POST',
                    url: $.pkp.plugins.generic.thothplugin.notification.notificationUrl,
                    success: $.pkp.plugins.generic.thothplugin.notification.showNotification,
                    complete() {
                        $.pkp.plugins.generic.thothplugin.workflow.loading = false;
                    },
                    dataType: 'json',
                    async: false
                });
            }
        });
    }

    pkp.eventBus.$on('form-success', (formId) => {
        if (formId == 'register') {
            pkp.registry._instances.app.refreshSubmission();
        }
    });
}());