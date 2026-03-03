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
						:is-success="!!item.thothWorkId"
						:is-warnable="hasErrors"
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
import {ref, computed} from 'vue';

const {useLocalize} = pkp.modules.useLocalize;
const {t, localize} = useLocalize();

const props = defineProps({
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

const hasErrors = computed(() => props.errors && props.errors.length > 0);

const currentPublication = computed(() =>
	props.item.publications?.find(
		(publication) => publication.id === props.item.currentPublicationId,
	),
);

const statusLabel = computed(() => {
	if (props.item.thothWorkId) {
		return t('plugins.generic.thoth.status.registered');
	}
	if (hasErrors.value) {
		return t('common.error');
	}
	return t('plugins.generic.thoth.status.unregistered');
});
</script>
