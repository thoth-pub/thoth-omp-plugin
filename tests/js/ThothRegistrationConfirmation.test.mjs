import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../js/ui/components/ListPanel/ThothListPanel.js', import.meta.url), 'utf8');

test('shows the forthcoming notice before confirming bulk registration', () => {
	let component;
	const panel = {mixins: [{}], components: {ListPanel: {components: {}}}};
	const pkp = {
		Vue: {
			compile: () => ({render() {}}),
			component: (name, options) => {component = options;},
		},
		controllers: {
			Container: {components: {SubmissionsListPanel: panel, SubmissionFilesListPanel: panel}},
			ManageEmailsPage: panel,
		},
	};
	vm.runInNewContext(source, {pkp});
	let confirmation;
	const notice = 'Please note that these titles are being marked as "Forthcoming" in Thoth.';
	component.methods.openRegister.call({
		selected: [1, 2],
		selectedImprint: 'imprint-id',
		__(key, params) {
			return key === 'plugins.generic.thoth.register.forthcomingNotice'
				? notice : params?.count ? `Register ${params.count} books?` : key;
		},
		openDialog: (options) => {confirmation = options;},
		registerAll() {assert.fail('Registration must wait for confirmation');},
	});
	assert.equal(confirmation.message, `Register 2 books? ${notice}`);
});
