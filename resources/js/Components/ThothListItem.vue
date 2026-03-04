<template>
	<div :id="`list-item-submission-${item.id}`" class="listPanel__item--thoth">
		<div class="listPanel__itemSummary">
			<label class="listPanel__selectWrapper">
				<div class="listPanel__selector">
					<input
						type="checkbox"
						name="submissions[]"
						:value="item.id"
						:disabled="!!item.thothWorkId"
						:checked="isSelected"
						@change="$emit('select-item', item.id)"
					/>
				</div>

				<div class="listPanel__itemIdentity">
					<div class="listPanel__itemTitle">
						<span
							v-if="currentPublication?.authorsStringShort"
							class="listPanel__item--submission__author"
						>
							{{ currentPublication.authorsStringShort }}
						</span>
					</div>
					<div class="listPanel__itemSubtitle">
						<a
							:href="item.urlWorkflow"
							target="_blank"
							rel="noopener noreferrer"
						>
							{{ localize(currentPublication?.fullTitle) }}
						</a>
					</div>
					<PkpSpinner v-if="isLoading" />
				</div>
			</label>

			<div class="listPanel__itemActions">
				<div class="listPanel__itemMetadata">
					<PkpBadge
						class="listPanel__itemMetadata--badge"
						:style="badgeStyle"
						has-dot
					>
						{{ statusLabel }}
					</PkpBadge>
				</div>

				<button
					v-if="hasErrors"
					class="expander"
					@click="expanded = !expanded"
				>
					<Icon
						:icon="expanded ? 'ChevronUp' : 'ChevronDown'"
						:inline="true"
					/>
					<span class="-screenReader">
						{{
							expanded
								? t('list.viewLess', {name: item.id.toString()})
								: t('list.viewMore', {name: item.id.toString()})
						}}
					</span>
				</button>
			</div>
		</div>

		<div
			v-if="expanded && hasErrors"
			class="listPanel__itemExpanded listPanel__itemExpanded--thoth"
		>
			<PkpNotification type="warning">
				<div
					v-for="(error, index) in errors"
					:key="index"
					class="thothListItem__error"
				>
					<Icon icon="Error" :inline="true" />
					<span>{{ error }}</span>
				</div>
			</PkpNotification>
		</div>
	</div>
</template>

<script setup>
import {ref, computed, watch} from 'vue';

const {useLocalize} = pkp.modules.useLocalize;
const {t, localize} = useLocalize();

const props = defineProps({
	apiUrl: {
		type: String,
		required: true,
	},
	errors: {
		type: Array,
		default: () => [],
	},
	item: {
		type: Object,
		required: true,
	},
	isLoading: {
		type: Boolean,
		default: false,
	},
	isSelected: {
		type: Boolean,
		default: false,
	},
});

defineEmits(['select-item']);

const expanded = ref(false);
const workStatus = ref(null);
const fetchError = ref(false);

const workStatusLocaleMap = {
	ACTIVE: 'plugins.generic.thoth.workStatus.active',
	FORTHCOMING: 'plugins.generic.thoth.workStatus.forthcoming',
	WITHDRAWN: 'plugins.generic.thoth.workStatus.withdrawn',
	SUPERSEDED: 'plugins.generic.thoth.workStatus.superseded',
	POSTPONED_INDEFINITELY:
		'plugins.generic.thoth.workStatus.postponedIndefinitely',
	CANCELLED: 'plugins.generic.thoth.workStatus.cancelled',
};

const hasErrors = computed(() => props.errors && props.errors.length > 0);

const currentPublication = computed(() =>
	props.item.publications?.find(
		(publication) => publication.id === props.item.currentPublicationId,
	),
);

const statusLabel = computed(() => {
	if (props.item.thothWorkId) {
		if (fetchError.value) {
			return t('common.error');
		}
		if (!workStatus.value) {
			return '...';
		}
		const localeKey = workStatusLocaleMap[workStatus.value];
		return localeKey ? t(localeKey) : workStatus.value;
	}
	if (hasErrors.value) {
		return t('common.error');
	}
	return t('plugins.generic.thoth.status.unregistered');
});

const workStatusColorMap = {
	ACTIVE: '#00B24E',
	FORTHCOMING: '#DED15D',
	WITHDRAWN: '#D00A0A',
	CANCELLED: '#D00A0A',
	SUPERSEDED: '#777777',
	POSTPONED_INDEFINITELY: '#E08914',
};

const badgeStyle = computed(() => {
	let color;

	if (!props.item.thothWorkId) {
		color = hasErrors.value ? '#D00A0A' : '#777777';
	} else if (fetchError.value) {
		color = '#D00A0A';
	} else {
		color = workStatusColorMap[workStatus.value] || '#777777';
	}

	return {borderColor: color, color: color};
});

function fetchWorkStatus() {
	if (!props.item.thothWorkId) {
		return;
	}

	fetchError.value = false;

	$.ajax({
		method: 'GET',
		url: `${props.apiUrl}/${props.item.id}/thothWorkStatus`,
		headers: {
			'X-Csrf-Token': pkp.currentUser.csrfToken,
		},
		success(response) {
			workStatus.value = response.workStatus;
		},
		error() {
			fetchError.value = true;
		},
	});
}

watch(
	() => props.item.thothWorkId,
	(newVal) => {
		if (newVal) {
			if (props.item.thothWorkStatus) {
				workStatus.value = props.item.thothWorkStatus;
			} else {
				fetchWorkStatus();
			}
		}
	},
	{immediate: true},
);
</script>
