<template>
	<div class="thoth-section border border-light p-4">
		<div class="flex items-center gap-x-3">
			<span class="text-lg-bold">
				{{ t('plugins.generic.thoth.thothBook') }}
			</span>
			<span v-if="submission.thothWorkId" class="flex items-center gap-x-2">
				<a
					class="pkpButton"
					target="_blank"
					:href="'https://thoth.pub/books/' + submission.thothWorkId"
				>
					{{ t('common.view') }}
				</a>
				<PkpButton
					v-if="!isPublished"
					:disabled="isLoading"
					@click="updateMetadata"
				>
					{{ t('plugins.generic.thoth.update') }}
				</PkpButton>
				<PkpSpinner v-if="isLoading" />
			</span>
			<span v-else>
				<PkpButton @click="openRegister">
					{{ t('plugins.generic.thoth.register') }}
				</PkpButton>
			</span>
		</div>
	</div>
</template>

<script setup>
import {ref, computed} from 'vue';

const {useLocalize} = pkp.modules.useLocalize;
const {t} = useLocalize();

const props = defineProps({
	submission: {type: Object, required: true},
	selectedPublicationId: {type: Number, required: true},
	registerUrl: {type: String, required: true},
	publicationUrl: {type: String, required: true},
	registerTitle: {type: String, required: true},
});

const isLoading = ref(false);
const isPublished = computed(
	() => props.submission.status === pkp.const.STATUS_PUBLISHED,
);

function openRegister() {
	const focusEl = document.activeElement;
	const sourceUrl = props.registerUrl.replace(
		'__publicationId__',
		props.selectedPublicationId,
	);

	const opts = {
		title: props.registerTitle,
		url: sourceUrl,
		closeCallback: () => focusEl.focus(),
		closeOnFormSuccessId: 'register',
	};

	$(
		'<div id="' +
			$.pkp.classes.Helper.uuid() +
			'" ' +
			'class="pkp_modal pkpModalWrapper" tabIndex="-1"></div>',
	).pkpHandler('$.pkp.controllers.modal.AjaxModalHandler', opts);
}

function updateMetadata() {
	isLoading.value = true;

	const url = props.publicationUrl.replace(
		'__publicationId__',
		props.selectedPublicationId,
	);

	$.ajax({
		method: 'PUT',
		url: url,
		headers: {
			'X-Csrf-Token': pkp.currentUser.csrfToken,
			'X-Http-Method-Override': 'PUT',
		},
		error: function (r) {
			pkp.eventBus.$emit('notify', r.responseJSON.errorMessage, 'warning');
		},
		complete() {
			if (
				typeof $.pkp.plugins.generic.thothplugin !== 'undefined' &&
				typeof $.pkp.plugins.generic.thothplugin.notification !== 'undefined'
			) {
				$.ajax({
					type: 'POST',
					url: $.pkp.plugins.generic.thothplugin.notification.notificationUrl,
					success:
						$.pkp.plugins.generic.thothplugin.notification.showNotification,
					complete() {
						isLoading.value = false;
					},
					dataType: 'json',
					async: false,
				});
			} else {
				isLoading.value = false;
			}
		},
	});
}

pkp.eventBus.$on('form-success', (formId) => {
	if (formId === 'register') {
		const workflowStore = pkp.registry.getPiniaStore('workflow');
		if (workflowStore) {
			workflowStore.refreshSubmission();
		}
	}
});
</script>
