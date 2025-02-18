const thothListTemplate = pkp.Vue.compile(`
    <div>
		<list-panel
			:isSidebarVisible="isSidebarVisible"
			:items="items"
			class="listPanel--thoth"
		>
			<template slot="header">
				<pkp-header>
					<h2>{{ title }}</h2>
					<spinner v-if="isLoading" />

					<template slot="actions">
						<search
							:searchPhrase="searchPhrase"
							@search-phrase-changed="setSearchPhrase"
						/>
						<pkp-button
							@click="toggleSelectAll"
						>
							{{
								isAllSelected
									? __('common.selectNone')
									: __('common.selectAll')
							}}
						</pkp-button>
						<pkp-button
							:disabled="selected.length === 0"
							@click="openRegister"
						>
							{{ __('plugins.generic.thoth.register') }}
						</pkp-button>
					</template>
				</pkp-header>
			</template>

			<template slot="sidebar">
				<div
					class="listPanel__block"
				>
					<pkp-header :isOneLine="false">
						<h3>
							{{ __('plugins.generic.thoth.imprint') }}
						</h3>
					</pkp-header>
					<label
						v-for="option in imprintOptions"
						:key="'imprint' + option.value"
						class="listPanel__block--option"
					>
						<input
							v-model="imprintValue"
							type="radio"
							:value="option.value"
							@change="selectImprint"
						/>
						<span
							v-strip-unsafe-html="option.label"
						/>
					</label>
				</div>

				<pkp-header :isOneLine="false">
					<h3>
						<icon icon="filter" :inline="true" />
						{{ __('common.filter') }}
					</h3>
				</pkp-header>
				<div
					v-for="(filterSet, index) in filters"
					:key="index"
					class="listPanel__block"
				>
					<pkp-header v-if="filterSet.heading">
						<h4>{{ filterSet.heading }}</h4>
					</pkp-header>
					<pkp-filter
						v-for="filter in filterSet.filters"
						:key="filter.param + filter.value"
						v-bind="filter"
						:isFilterActive="isFilterActive(filter.param, filter.value)"
						@add-filter="addFilter"
						@remove-filter="removeFilter"
					/>
				</div>
			</template>

			<template v-if="isLoading" slot="itemsEmpty">
				<spinner />
				{{ __('common.loading') }}
			</template>

			<template v-slot:item="{item}">
				<slot name="item" :item="item">
					<thoth-list-item
						:key="item.id"
						:item="item"
						:errors="errors[item.id]"
						:is-expanded="errors[item.id]"
						:is-loading="startedItems.includes(item.id)"
						:is-selected="selected.includes(item.id)"
						@select-item="selectItem"
						@update:item="updateItem"
					/>
				</slot>
			</template>

			<pagination
				v-if="lastPage > 1"
				slot="footer"
				:currentPage="currentPage"
				:isLoading="isLoading"
				:lastPage="lastPage"
				@set-page="setPage"
			/>
		</list-panel>
	</div>
`);

const SubmissionsListPanel = pkp.controllers.Container.components.SubmissionsListPanel;
const ManageEmailsPage = pkp.controllers.ManageEmailsPage;

const fetch = SubmissionsListPanel.mixins[0];
const dialog = ManageEmailsPage.mixins[0];
const ListPanel = SubmissionsListPanel.components.ListPanel;
const Modal = ManageEmailsPage.components.Modal;
const Notification = ListPanel.components.Notification;
const Pagination = SubmissionsListPanel.components.PkpHeader;
const PkpHeader = ListPanel.components.PkpHeader;
const PkpFilter = SubmissionsListPanel.components.PkpFilter;
const Search = SubmissionsListPanel.components.Search;

pkp.Vue.component('thoth-list-panel', {
    name: 'ThothListPanel',
	components: {
        ListPanel,
		Modal,
		Notification,
		Pagination,
		PkpHeader,
		PkpFilter,
		Search
    },
	mixins: [fetch, dialog],
	props: {
		csrfToken: {
			type: String,
			required: true
		},
		filters: {
			type: Array,
			default() {
				return [];
			}
		},
		id: {
			type: String,
			required: true
		},
		imprintOptions: {
			type: Array,
			default() {
				return [];
			}
		},
		items: {
			type: Array,
			default() {
				return [];
			}
		},
		itemsMax: {
			type: Number,
			default() {
				return 0;
			}
		},
		selectedImprint: {
			type: String,
			default() {
				return '';
			}
		},
		title: {
			type: String,
			default() {
				return '';
			}
		},
	},
	data() {
		return {
			isAllSelected: false,
			isSidebarVisible: true,
			errors: [],
			imprintValue: '',
			selected: [],
			startedItems: [],
		};
	},
	methods: {
		addFilter(param, value) {
			let newFilters = {};
			newFilters[param] = value;
			this.activeFilters = newFilters;
		},
		removeFilter(param, value) {
			this.activeFilters = {};
		},
		isFilterActive(param, value) {
			if (!Object.keys(this.activeFilters).includes(param)) {
				return false;
			} else if (Array.isArray(this.activeFilters[param])) {
				return this.activeFilters[param].includes(value);
			} else {
				return this.activeFilters[param] === value;
			}
		},
		getItem(itemId) {
			let self = this;

			return $.ajax({
				url: `${this.apiUrl}/${itemId}`,
				type: 'GET',
				_uuid: $.pkp.classes.Helper.uuid(),
				error(r) {
					self.ajaxErrorCallback(r);
				},
				success(r) {
					self.updateItem(r);
					self.selected = self.selected.filter(id => id !== itemId)
					self.startedItems = self.startedItems.filter(id => id !== itemId)
				}
			});
		},
		setItems(items, itemsMax) {
			this.$emit('set', this.id, {items, itemsMax});
		},
		updateItem(updatedItem) {
			let items = this.items.map(item => {
				return item.id === updatedItem.id ? updatedItem : item;
			});
			this.$emit('set', this.id, {items: items});
		},
		selectItem(itemId) {
			if (this.selected.includes(itemId)) {
				this.selected = this.selected.filter((item) => item !== itemId);
			} else {
				this.selected.push(itemId);
			}
		},
		toggleSelectAll() {
			if (this.isAllSelected) {
				this.selected = [];
			} else {
				this.selected = this.items.filter((item) => !item.thothWorkId).map(i => i.id);
			}
		},
		selectImprint() {
			this.selectedImprint = this.imprintValue;
		},
		openRegister() {
			const title = this.__('plugins.generic.thoth.actions.register.label');
			const message = this.__('plugins.generic.thoth.actions.register.prompt', {
				count: this.selected.length,
			});

			if (!this.selectedImprint) {
				pkp.eventBus.$emit('notify',this.__('plugins.generic.thoth.imprint.required'),'warning');
			} else {
				this.openDialog({
					name: 'register',
					title: title,
					message: message,
					actions: [
						{
							label: title,
							isWarnable: true,
							callback: () => {
								this.$modal.hide('register');
								this.registerAll();
							},
						},
						{
							label: this.__('common.cancel'),
							callback: () => this.$modal.hide('register'),
						},
					],
				});
			}

		},
		registerAll() {
			this.startedItems = [...this.selected];

			this.selected.forEach((id) => {
				let item = this.items.find(item => item.id === id);
				this.submitItem(item);
			});
		},
		submitItem(item) {
			$.ajax({
				url: `${this.apiUrl}/${item.id}/register`,
				type: 'POST',
				headers: {
					'X-Csrf-Token': pkp.currentUser.csrfToken,
					'X-Http-Method-Override': 'PUT',
				},
				data: {
					imprint: this.selectedImprint,
					disableNotification: true
				},
				success: (response) => this.updateItem(response),
				error: (response) => this.setErrors(response),
				complete: () => {
					this.selected = this.selected.filter(id => id !== item.id);
					this.startedItems = this.startedItems.filter(id => id !== item.id);
				}
			});
		},
		setErrors(response) {
			if (
				response.status &&
				response.status !== 200 &&
				response.responseJSON &&
				response.responseJSON.errors
			) {
				let responseJson = response.responseJSON;
				this.errors[responseJson.id] = responseJson.errors;
			}
		}
	},
	watch: {
		selected(newVal, oldVal) {
			this.isAllSelected =
				this.selected.length
				&& this.selected.length === this.items.filter((item) => !item.thothWorkId).length;
		}
	},
    render: function(h) {
		return thothListTemplate.render.call(this, h);
	}
});