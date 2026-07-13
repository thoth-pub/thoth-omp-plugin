<template>
	<PkpForm v-if="form" v-bind="form" @set="set" />
	<PkpSpinner v-else />
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {getFormComposable} from '../featureVideoWorkflow.mjs';

const useForm = getFormComposable(pkp.modules.useForm);

const props = defineProps({
	submission: {type: Object, required: true},
});

const formConfig = ref(null);
const {form, set} = useForm(formConfig);

onMounted(() => {
	const workflowData = pkp.plugins?.generic?.thoth?.workflow || {};
	const url = (workflowData.featureVideoUrl || '').replace(
		'__submissionId__',
		props.submission.id,
	);

	if (!url) {
		return;
	}

	$.ajax({
		method: 'GET',
		url,
		headers: {
			'X-Csrf-Token': pkp.currentUser.csrfToken,
		},
		success(response) {
			formConfig.value = response;
		},
		error(response) {
			pkp.eventBus.$emit(
				'notify',
				response.responseJSON?.error || 'common.error',
				'warning',
			);
		},
	});
});
</script>
