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
    $.pkp.plugins.generic.thothplugin.workflow.fetchError = false;
    $.pkp.plugins.generic.thothplugin.workflow.workStatus = null;
    $.pkp.plugins.generic.thothplugin.workflow.workStatusLoaded =
        !$.pkp.plugins.generic.thothplugin.workflow.hasLinkedWork;
    $.pkp.plugins.generic.thothplugin.workflow.workNotFound = false;

    $.pkp.plugins.generic.thothplugin.workflow.refreshSection = function () {
        const app = pkp.registry && pkp.registry._instances
            ? pkp.registry._instances.app
            : null;
        if (app && typeof app.$forceUpdate === 'function') {
            app.$forceUpdate();
        }
    }

    $.pkp.plugins.generic.thothplugin.workflow.setWorkNotFound = function (workNotFound) {
        $.pkp.plugins.generic.thothplugin.workflow.workNotFound = workNotFound;
        $.pkp.plugins.generic.thothplugin.workflow.refreshSection();
    }

    $.pkp.plugins.generic.thothplugin.workflow.getWorkStatusLabel = function () {
        const workflow = $.pkp.plugins.generic.thothplugin.workflow;
        if (workflow.workNotFound) {
            return workflow.workStatusNotFound;
        }
        if (workflow.fetchError) {
            return workflow.workStatusError;
        }
        return workflow.workStatusLabels[workflow.workStatus] || workflow.workStatus;
    }

    $.pkp.plugins.generic.thothplugin.workflow.getWorkStatusClass = function () {
        const workflow = $.pkp.plugins.generic.thothplugin.workflow;
        const classes = {
            ACTIVE: 'thothWorkStatus__indicator--active',
            FORTHCOMING: 'thothWorkStatus__indicator--forthcoming',
            WITHDRAWN: 'thothWorkStatus__indicator--declined',
            SUPERSEDED: 'thothWorkStatus__indicator--superseded',
            POSTPONED_INDEFINITELY: 'thothWorkStatus__indicator--postponed',
            CANCELLED: 'thothWorkStatus__indicator--declined'
        };
        if (workflow.workNotFound || workflow.fetchError) {
            return 'thothWorkStatus__indicator--declined';
        }
        return classes[workflow.workStatus] || 'thothWorkStatus__indicator--superseded';
    }

    $.pkp.plugins.generic.thothplugin.workflow.canShowLinkedWorkActions = function () {
        const workflow = $.pkp.plugins.generic.thothplugin.workflow;
        return workflow.workStatusLoaded && !workflow.workNotFound && !workflow.fetchError;
    }

    $.pkp.plugins.generic.thothplugin.workflow.fetchWorkStatus = function () {
        const workflow = $.pkp.plugins.generic.thothplugin.workflow;
        workflow.fetchError = false;
        workflow.workNotFound = false;
        workflow.workStatus = null;
        workflow.workStatusLoaded = false;
        workflow.refreshSection();

        $.ajax({
            method: 'GET',
            url: workflow.workStatusUrl,
            headers: {
                'X-Csrf-Token': pkp.currentUser.csrfToken
            },
            success(response) {
                workflow.workStatus = response.workStatus;
            },
            error(response) {
                workflow.workNotFound =
                    response.status === 404
                    && response.responseJSON
                    && response.responseJSON.workNotFound === true;
                workflow.fetchError = !workflow.workNotFound;
            },
            complete() {
                workflow.workStatusLoaded = true;
                workflow.refreshSection();
            }
        });
    }

    $.pkp.plugins.generic.thothplugin.workflow.unlinkWork = function () {
        const focusEl = document.activeElement;
        const workflow = $.pkp.plugins.generic.thothplugin.workflow;
        var opts = {
            title: workflow.unlinkTitle,
            okButton: workflow.unlinkTitle,
            cancelButton: workflow.unlinkCancel,
            dialogText: workflow.unlinkConfirm,
            callback: workflow.performUnlink,
            closeCallback: () => focusEl.focus(),
            titleIcon: 'modal_confirm',
            width: 'auto'
        };

        $(
            '<div id="' +
            $.pkp.classes.Helper.uuid() +
            '" class="pkp_modal pkpModalWrapper" tabIndex="-1"></div>'
        ).pkpHandler('$.pkp.controllers.modal.ConfirmationModalHandler', opts);
    }

    $.pkp.plugins.generic.thothplugin.workflow.performUnlink = function () {
        $.pkp.plugins.generic.thothplugin.workflow.loading = true;
        $.pkp.plugins.generic.thothplugin.workflow.refreshSection();

        $.ajax({
            method: 'POST',
            url: $.pkp.plugins.generic.thothplugin.workflow.unlinkUrl,
            headers: {
                'X-Csrf-Token': pkp.currentUser.csrfToken,
                'X-Http-Method-Override': 'DELETE'
            },
            success() {
                $.pkp.plugins.generic.thothplugin.workflow.setWorkNotFound(false);
                pkp.registry._instances.app.refreshSubmission();
            },
            error(response) {
                const responseData = response.responseJSON || {};
                const message = responseData.errorMessage
                    || responseData.error
                    || $.pkp.plugins.generic.thothplugin.workflow.unlinkError;
                pkp.eventBus.$emit('notify', message, 'warning');
            },
            complete() {
                $.pkp.plugins.generic.thothplugin.workflow.loading = false;
                $.pkp.plugins.generic.thothplugin.workflow.refreshSection();
            }
        });
    }

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

        const url = $.pkp.plugins.generic.thothplugin.workflow.synchronizeUrl.replace(
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
            $.pkp.plugins.generic.thothplugin.workflow.fetchWorkStatus();
        }
    });

    if ($.pkp.plugins.generic.thothplugin.workflow.hasLinkedWork) {
        $.pkp.plugins.generic.thothplugin.workflow.fetchWorkStatus();
    }
}());
