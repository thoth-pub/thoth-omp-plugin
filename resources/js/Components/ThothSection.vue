<template>
	<div>
		<span class="text-lg-bold">
			{{ t('semicolon', {label: t('plugins.generic.thoth.workStatus')}) }}
		</span>
		<span
			class="ms-1 h-[1em] w-[1em] inline-block align-middle rounded-full"
			:class="statusColor"
			aria-hidden="true"
		/>
		<span class="ms-1 text-lg-normal">{{ statusLabel }}</span>
		<PkpButton
			v-if="actionVisibility.view"
			is-link
			@click="viewWork"
		>
			{{ t('common.view') }}
		</PkpButton>
		<PkpButton
			v-if="actionVisibility.unlink"
			:disabled="isLoading"
			is-link
			@click="confirmUnlinkWork"
		>
			{{ t('plugins.generic.thoth.unlink') }}
			<PkpSpinner v-if="isLoading" class="ms-1" />
		</PkpButton>
		<PkpButton
			v-if="actionVisibility.update"
			:disabled="isLoading"
			is-link
			@click="updateMetadata"
		>
			{{ t('plugins.generic.thoth.update') }}
			<PkpSpinner v-if="isLoading" class="ms-1" />
		</PkpButton>
		<PkpButton
			v-if="actionVisibility.register"
			is-link
			@click="openRegister"
		>
			{{ t('plugins.generic.thoth.register') }}
		</PkpButton>
	</div>
</template>

<script setup>
import {ref, computed, onMounted} from 'vue';
import {getThothActionVisibility} from '../thothActionVisibility.mjs';
import {openUnlinkWorkConfirmation} from '../unlinkWorkConfirmation.mjs';

const {useLocalize} = pkp.modules.useLocalize;
const {useModal} = pkp.modules.useModal;
const {useDataChanged} = pkp.modules.useDataChanged;
const {t} = useLocalize();
const {triggerDataChange} = useDataChanged();

const props = defineProps({
	submission: {type: Object, required: true},
	selectedPublicationId: {type: Number, required: true},
	workStatusUrl: {type: String, required: true},
	unlinkUrl: {type: String, required: true},
	registerUrl: {type: String, required: true},
	synchronizeUrl: {type: String, required: true},
	registerTitle: {type: String, required: true},
});

const workStatus = ref(null);
const statusRequestCompleted = ref(false);
const fetchError = ref(false);
const workNotFound = ref(false);
const isLoading = ref(false);

const isPublished = computed(
	() => props.submission.status === pkp.const.STATUS_PUBLISHED,
);

const actionVisibility = computed(() =>
	getThothActionVisibility({
		hasWorkLink: Boolean(props.submission.thothWorkId),
		workStatus: workStatus.value,
		statusRequestCompleted: statusRequestCompleted.value,
		workNotFound: workNotFound.value,
		fetchError: fetchError.value,
		isPublished: isPublished.value,
	}),
);

const thothWorkUrl = computed(() => {
	if (!props.submission.thothWorkId) {
		return null;
	}
	return 'https://thoth.pub/books/' + props.submission.thothWorkId;
});

const workStatusLocaleMap = {
	ACTIVE: 'plugins.generic.thoth.workStatus.active',
	FORTHCOMING: 'plugins.generic.thoth.workStatus.forthcoming',
	WITHDRAWN: 'plugins.generic.thoth.workStatus.withdrawn',
	SUPERSEDED: 'plugins.generic.thoth.workStatus.superseded',
	POSTPONED_INDEFINITELY:
		'plugins.generic.thoth.workStatus.postponedIndefinitely',
	CANCELLED: 'plugins.generic.thoth.workStatus.cancelled',
};

const statusLabel = computed(() => {
	if (!props.submission.thothWorkId) {
		return t('plugins.generic.thoth.status.unregistered');
	}
	if (workNotFound.value) {
		return t('plugins.generic.thoth.status.notFound');
	}
	if (fetchError.value) {
		return t('common.error');
	}
	if (!workStatus.value) {
		return '...';
	}
	const localeKey = workStatusLocaleMap[workStatus.value];
	return localeKey ? t(localeKey) : workStatus.value;
});

const statusColor = computed(() => {
	if (!props.submission.thothWorkId) {
		return 'bg-stage-declined';
	}

	if (fetchError.value) {
		return 'bg-stage-declined';
	}

	switch (workStatus.value) {
		case 'ACTIVE':
			return 'bg-stage-published';
		case 'FORTHCOMING':
			return 'bg-stage-scheduled-for-publishing';
		case 'WITHDRAWN':
		case 'CANCELLED':
			return 'bg-stage-declined';
		case 'SUPERSEDED':
			return 'bg-stage-incomplete-submission';
		case 'POSTPONED_INDEFINITELY':
			return 'bg-stage-in-review';
		default:
			return 'bg-stage-declined';
	}
});

function fetchWorkStatus() {
	if (!props.submission.thothWorkId) {
		return;
	}

	statusRequestCompleted.value = false;
	fetchError.value = false;
	workNotFound.value = false;

	$.ajax({
		method: 'GET',
		url: props.workStatusUrl,
		headers: {
			'X-Csrf-Token': pkp.currentUser.csrfToken,
		},
		success(response) {
			workStatus.value = response.workStatus;
		},
		error(response) {
			workNotFound.value =
				response.status === 404 &&
				response.responseJSON?.workNotFound === true;
			fetchError.value = !workNotFound.value;
		},
		complete() {
			statusRequestCompleted.value = true;
		},
	});
}

function viewWork() {
	window.open(thothWorkUrl.value, '_blank', 'noopener,noreferrer');
}

function confirmUnlinkWork() {
	const {openDialog} = useModal();
	openUnlinkWorkConfirmation({
		openDialog,
		title: t('plugins.generic.thoth.unlink'),
		message: t('plugins.generic.thoth.unlink.confirm'),
		cancelLabel: t('common.cancel'),
		onConfirm: unlinkWork,
	});
}

function unlinkWork() {
	isLoading.value = true;

	$.ajax({
		method: 'POST',
		url: props.unlinkUrl,
		headers: {
			'X-Csrf-Token': pkp.currentUser.csrfToken,
			'X-Http-Method-Override': 'DELETE',
		},
		success: async function () {
			await triggerDataChange();
			isLoading.value = false;
		},
		error: function (response) {
			const message =
				response.responseJSON?.error ||
				response.responseJSON?.errorMessage ||
				t('plugins.generic.thoth.connectionError');
			pkp.eventBus.$emit('notify', message, 'warning');
			isLoading.value = false;
		},
	});
}

function openRegister() {
	const {openSideModal} = useModal();
	const sourceUrl = props.registerUrl.replace(
		'__publicationId__',
		props.selectedPublicationId,
	);

	openSideModal(
		'LegacyAjax',
		{
			legacyOptions: {
				title: props.registerTitle,
				url: sourceUrl,
				closeOnFormSuccessId: 'register',
			},
		},
		{
			onClose: async () => {
				await triggerDataChange();
				fetchWorkStatus();
			},
		},
	);
}

function updateMetadata() {
	isLoading.value = true;

	const url = props.synchronizeUrl.replace(
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

onMounted(() => {
	fetchWorkStatus();
});
</script>
