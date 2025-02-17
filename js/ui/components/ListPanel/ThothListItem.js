const thothListItemTemplate = pkp.Vue.compile(`
    <div>
		<div class="listPanel__itemSummary">
			<label class="listPanel__selectWrapper">
				<div class="listPanel__selector">
					<input
						type="checkbox"
						name="submissions[]"
						:value="item.id"
						:disabled="item.thothWorkId"
						v-model="isSelected"
						@click="toggleSelected"
					/>
				</div>

				<div class="listPanel__itemIdentity listPanel__itemIdentity--thoth">
					<div class="listPanel__itemTitle">
						<span
							v-if="currentPublication.authorsStringShort"
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
							{{ localize(currentPublication.fullTitle) }}
						</a>
					</div>
					<spinner v-if="isLoading" />
				</div>
			</label>

			<div class="listPanel__itemActions">
				{{ item.id }}
				<div class="listPanel__itemMetadata">
					<badge
						class="listPanel__itemMetadata--badge"
						:is-success="item.thothWorkId"
					>
						{{ statusLabel }}
					</badge>
				</div>

				<expander
					v-if="errors.length > 0"
					:isExpanded="isExpanded"
					:itemName="item.id.toString()"
					@toggle="isExpanded = !isExpanded"
				/>
			</div>
		</div>

		<div
			v-if="errors.length > 0 && !isExpanded"
			class="listPanel__item--thoth_notice"
		>
			<icon icon="exclamation-triangle" :inline="true" />
			{{ __('common.error') }}
		</div>

		<div
			v-if="isExpanded"
			class="listPanel__itemExpanded listPanel__itemExpanded--thoth"
		>
			<list>
				<list-item v-for="error in errors">
					<icon icon="exclamation-triangle" :inline="true" />
					{{ error }}
				</list-item>
			</list>
		</div>
	</div>
`);

const SubmissionsListItemComponents = pkp.controllers.Container.components.
	SubmissionsListPanel.components.
	SubmissionsListItem.components;

const Expander = SubmissionsListItemComponents.Expander;
const List = SubmissionsListItemComponents.List;
const ListItem = SubmissionsListItemComponents.ListItem;


pkp.Vue.component('thoth-list-item', {
    name: 'ThothListItem',
	components: {
		Expander,
		List,
		ListItem
	},
    props: {
		errors: {
			type: Array,
			default() {
				return [];
			}
		},
		item: {
			type: Object,
			required: true
		},
		isExpanded: {
			type: Boolean,
		},
		isLoading: {
			type: Boolean,
		},
		isSelected: {
			type: Boolean,
		}
	},
	computed: {
		canSelect() {
			return this.item.thothWorkId !== null;
		},
		currentPublication() {
			return this.item.publications.find(
				publication => publication.id === this.item.currentPublicationId
			);
		},
		statusLabel() {
			if (this.item.thothWorkId) {
				return this.__('plugins.generic.thoth.status.registered');
			} else {
				return this.__('plugins.generic.thoth.status.unregistered');
			}
		}
	},
	methods: {
		toggleSelected() {
			this.$emit('select-item', this.item.id);
		}
	},
    render: function(h) {
		return thothListItemTemplate.render.call(this, h);
	}
});