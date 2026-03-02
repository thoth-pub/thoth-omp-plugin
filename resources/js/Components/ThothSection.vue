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
			v-if="submission.thothWorkId && !isPublished"
			:disabled="isLoading"
			is-link
			@click="updateMetadata"
		>
			{{ t('plugins.generic.thoth.update') }}
			<PkpSpinner v-if="isLoading" class="ms-1" />
		</PkpButton>
		<PkpButton
			v-else-if="!submission.thothWorkId"
			is-link
			@click="openRegister"
		>
			{{ t('plugins.generic.thoth.register') }}
		</PkpButton>
	</div>
</template>

<script setup>
import {ref, computed, onMounted} from 'vue';

const {useLocalize} = pkp.modules.useLocalize;
const {useModal} = pkp.modules.useModal;
const {useDataChanged} = pkp.modules.useDataChanged;
const {t} = useLocalize();
const {triggerDataChange} = useDataChanged();

const props = defineProps({
	submission: {type: Object, required: true},
	selectedPublicationId: {type: Number, required: true},
	workStatusUrl: {type: String, required: true},
	registerUrl: {type: String, required: true},
	publicationUrl: {type: String, required: true},
	registerTitle: {type: String, required: true},
});

const workStatus = ref(null);
const isLoading = ref(false);

const isPublished = computed(
	() => props.submission.status === pkp.const.STATUS_PUBLISHED,
);

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
	if (!workStatus.value) {
		return '...';
	}
	const localeKey = workStatusLocaleMap[workStatus.value];
	return localeKey ? t(localeKey) : workStatus.value;
});

const statusColor = computed(() => {
	if (!props.submission.thothWorkId || !workStatus.value) {
		return 'bg-stage-declined';
	}

	switch (workStatus.value) {
		case 'ACTIVE':
			return 'bg-stage-published';
		case 'FORTHCOMING':
			return 'bg-stage-scheduled-for-publishing';
		default:
			return 'bg-stage-declined';
	}
});

function fetchWorkStatus() {
	if (!props.submission.thothWorkId) {
		return;
	}

	$.ajax({
		method: 'GET',
		url: props.workStatusUrl,
		headers: {
			'X-Csrf-Token': pkp.currentUser.csrfToken,
		},
		success(response) {
			workStatus.value = response.workStatus;
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

onMounted(() => {
	fetchWorkStatus();
});
</script>
