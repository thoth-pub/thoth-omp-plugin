<template>
	<div class="flex items-center">
		<span class="text-lg-bold">
			{{ t('semicolon', {label: t('plugins.generic.thoth.workStatus')}) }}
		</span>
		<span
			class="ms-2 h-[1em] w-[1em] rounded-full"
			:class="statusColor"
			aria-hidden="true"
		/>
		<span class="ms-1 text-base-normal">{{ statusLabel }}</span>
	</div>
</template>

<script setup>
import {ref, computed, onMounted} from 'vue';

const {useLocalize} = pkp.modules.useLocalize;
const {t} = useLocalize();

const props = defineProps({
	submission: {type: Object, required: true},
	workStatusUrl: {type: String, required: true},
});

const workStatus = ref(null);

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

onMounted(() => {
	fetchWorkStatus();
});

pkp.eventBus.$on('form-success', (formId) => {
	if (formId === 'register') {
		fetchWorkStatus();
	}
});
</script>
