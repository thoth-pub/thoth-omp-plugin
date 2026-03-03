<template>
	<PkpListPanel
		class="listPanel--thoth"
		:items="currentItems"
		:is-sidebar-visible="true"
	>
		<template #header>
			<PkpHeader>
				<h2>{{ title }}</h2>
				<PkpSpinner v-if="isLoading" />

				<template #actions>
					<PkpSearch
						:search-phrase="searchPhrase"
						@search-phrase-changed="setSearchPhrase"
					/>
					<PkpButton @click="toggleSelectAll">
						{{
							isAllSelected
								? t('common.selectNone')
								: t('common.selectAll')
						}}
					</PkpButton>
					<PkpButton
						:is-disabled="selected.length === 0"
						@click="openRegister"
					>
						{{ t('plugins.generic.thoth.register') }}
					</PkpButton>
				</template>
			</PkpHeader>
		</template>

		<template #sidebar>
			<div class="listPanel__block">
				<PkpHeader :is-one-line="false">
					<h3>
						{{ t('plugins.generic.thoth.imprint') }}
					</h3>
				</PkpHeader>
				<label
					v-for="option in imprintOptions"
					:key="'imprint' + option.value"
					class="pkpFormField--options__option"
				>
					<input
						v-model="imprintValue"
						class="pkpFormField--options__input"
						type="radio"
						:value="option.value"
					/>
					<span class="pkpFormField--options__optionLabel">
						{{ option.label }}
					</span>
				</label>
			</div>

			<PkpHeader :is-one-line="false">
				<h3>
					<Icon icon="Filter" class="h-4 w-4" :inline="true" />
					{{ t('common.filter') }}
				</h3>
			</PkpHeader>
			<div
				v-for="(filterSet, index) in filters"
				:key="index"
				class="listPanel__block"
			>
				<PkpHeader v-if="filterSet.heading">
					<h4>{{ filterSet.heading }}</h4>
				</PkpHeader>
				<PkpFilter
					v-for="filter in filterSet.filters"
					:key="filter.param + filter.value"
					v-bind="filter"
					:is-filter-active="isFilterActive(filter.param, filter.value)"
					@add-filter="addFilter"
					@remove-filter="removeFilter"
				/>
			</div>
		</template>

		<template v-if="isLoading && !currentItems.length" #itemsEmpty>
			<PkpSpinner />
			{{ t('common.loading') }}
		</template>

		<template #item="{item}">
			<ThothListItem
				:key="item.id"
				:item="item"
				:errors="errors[item.id] || []"
				:is-loading="startedItems.includes(item.id)"
				:is-selected="selected.includes(item.id)"
				@select-item="selectItem"
			/>
		</template>

		<template v-if="lastPage > 1" #footer>
			<PkpPagination
				:current-page="currentPage"
				:is-loading="isLoading"
				:last-page="lastPage"
				@set-page="setPage"
			/>
		</template>
	</PkpListPanel>
</template>

<script setup>
import {ref, computed} from 'vue';
import ThothListItem from './ThothListItem.vue';

const {useLocalize} = pkp.modules.useLocalize;
const {useFetch} = pkp.modules.useFetch;
const {useModal} = pkp.modules.useModal;
const {t} = useLocalize();
const {openDialog} = useModal();

const props = defineProps({
	apiUrl: {
		type: String,
		required: true,
	},
	count: {
		type: Number,
		default: 30,
	},
	csrfToken: {
		type: String,
		default: '',
	},
	filters: {
		type: Array,
		default: () => [],
	},
	getParams: {
		type: Object,
		default: () => ({}),
	},
	id: {
		type: String,
		required: true,
	},
	imprintOptions: {
		type: Array,
		default: () => [],
	},
	items: {
		type: Array,
		default: () => [],
	},
	itemsMax: {
		type: Number,
		default: 0,
	},
	selectedImprint: {
		type: String,
		default: '',
	},
	title: {
		type: String,
		default: '',
	},
});

const emit = defineEmits(['set']);

const currentItems = ref([...props.items]);
const totalItems = ref(props.itemsMax);
const isLoading = ref(false);
const searchPhrase = ref('');
const currentPage = ref(1);
const activeFilters = ref({});
const imprintValue = ref(props.selectedImprint || '');
const selected = ref([]);
const startedItems = ref([]);
const errors = ref({});

const lastPage = computed(() => Math.ceil(totalItems.value / props.count));

const isAllSelected = computed(() => {
	const unregistered = currentItems.value.filter(
		(item) => !item.thothWorkId,
	);
	return selected.value.length > 0 && selected.value.length === unregistered.length;
});

const queryParams = computed(() => {
	const params = {
		...props.getParams,
		searchPhrase: searchPhrase.value || undefined,
		count: props.count,
		offset: (currentPage.value - 1) * props.count,
		...activeFilters.value,
	};
	return params;
});

async function fetchItems() {
	isLoading.value = true;

	const url = computed(() => props.apiUrl);
	const {data, fetch} = useFetch(url, {
		query: queryParams,
	});

	await fetch();

	if (data.value) {
		currentItems.value = data.value.items || [];
		totalItems.value = data.value.itemsMax ?? totalItems.value;
		emit('set', props.id, {
			items: currentItems.value,
			itemsMax: totalItems.value,
		});
	}

	isLoading.value = false;
}

function setSearchPhrase(newSearchPhrase) {
	searchPhrase.value = newSearchPhrase;
	currentPage.value = 1;
	fetchItems();
}

function setPage(page) {
	currentPage.value = page;
	fetchItems();
}

function addFilter(param, value) {
	activeFilters.value = {[param]: value};
	currentPage.value = 1;
	fetchItems();
}

function removeFilter() {
	activeFilters.value = {};
	currentPage.value = 1;
	fetchItems();
}

function isFilterActive(param, value) {
	if (!Object.keys(activeFilters.value).includes(param)) {
		return false;
	}
	if (Array.isArray(activeFilters.value[param])) {
		return activeFilters.value[param].includes(value);
	}
	return activeFilters.value[param] === value;
}

function selectItem(itemId) {
	if (selected.value.includes(itemId)) {
		selected.value = selected.value.filter((id) => id !== itemId);
	} else {
		selected.value.push(itemId);
	}
}

function toggleSelectAll() {
	if (isAllSelected.value) {
		selected.value = [];
	} else {
		selected.value = currentItems.value
			.filter((item) => !item.thothWorkId)
			.map((i) => i.id);
	}
}

function updateItem(updatedItem) {
	currentItems.value = currentItems.value.map((item) =>
		item.id === updatedItem.id ? updatedItem : item,
	);
	emit('set', props.id, {items: currentItems.value});
}

function openRegister() {
	const title = t('plugins.generic.thoth.actions.register.label');
	const message = t('plugins.generic.thoth.actions.register.prompt', {
		count: selected.value.length,
	});

	if (!imprintValue.value) {
		pkp.eventBus.$emit(
			'notify',
			t('plugins.generic.thoth.imprint.required'),
			'warning',
		);
		return;
	}

	openDialog({
		title,
		message,
		actions: [
			{
				label: title,
				isWarnable: true,
				callback: (close) => {
					close();
					registerAll();
				},
			},
			{
				label: t('common.cancel'),
				callback: (close) => close(),
			},
		],
	});
}

function registerAll() {
	startedItems.value = [...selected.value];

	selected.value.forEach((id) => {
		const item = currentItems.value.find((i) => i.id === id);
		if (item) {
			submitItem(item);
		}
	});
}

async function submitItem(item) {
	const url = computed(() => `${props.apiUrl}/${item.id}/register`);
	const {data, fetch, isSuccess, validationError} = useFetch(url, {
		method: 'PUT',
		body: {
			thothImprintId: imprintValue.value,
			disableNotification: true,
		},
		expectValidationError: true,
	});

	await fetch();

	if (isSuccess.value) {
		updateItem(data.value);
	} else if (validationError.value) {
		setErrors(item.id, validationError.value);
	}

	selected.value = selected.value.filter((id) => id !== item.id);
	startedItems.value = startedItems.value.filter((id) => id !== item.id);
}

function setErrors(itemId, response) {
	if (response.errors) {
		errors.value = {...errors.value, [itemId]: response.errors};
	}
}
</script>

<style>
.listPanel--thoth .listPanel__itemIdentity {
	position: relative;
	padding: 0;
}

.listPanel__selectWrapper {
	display: flex;
	align-items: center;
	margin-left: -1rem;
	overflow: hidden;
	flex: 1;
	min-width: 0;
}

.listPanel--thoth .listPanel__itemSummary {
	align-items: center;
}

.listPanel--thoth .listPanel__itemActions {
	align-self: stretch;
	align-items: center;
}

.listPanel__selector {
	width: 3rem;
	padding-left: 1rem;
}

.listPanel__itemMetadata {
	font-size: 0.75rem;
	line-height: 1.5em;
	color: #222;
	margin-left: 0.75rem;
}

.listPanel__itemMetadata--badge {
	margin-right: 0.25rem;
}

.listPanel__itemExpanded--thoth {
	padding-left: 2rem;
}

.listPanel--thoth .expander {
	padding: 0 0.5em;
	background: transparent;
	border: 1px solid #ddd;
	border-radius: 2px;
	font-size: 0.875rem;
	line-height: 2rem;
	color: #006798;
	box-shadow: 0 1px 0 rgba(0, 0, 0, 0.04);
	cursor: pointer;
}

.listPanel--thoth .expander:hover,
.listPanel--thoth .expander:focus {
	color: #006798;
	border-color: #006798;
	outline: 0;
}

.listPanel--thoth .expander:active {
	box-shadow: 0 0 2px;
}

.listPanel__item--thoth svg {
	width: 1rem;
	height: 1rem;
	display: inline-block;
	vertical-align: middle;
}

.listPanel__itemExpanded--thoth .pkpNotification {
	border: 1px solid #d00a6c;
	box-shadow: inset 0.25rem 0 0 #d00a6c;
	padding: 0.5rem 0.75rem;
	margin-top: .5rem;
}

.thothListItem__error {
	display: flex;
	align-items: baseline;
	gap: 0.5rem;
}

.thothListItem__error + .thothListItem__error {
	margin-top: 0.25rem;
}
</style>
