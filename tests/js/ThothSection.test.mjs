import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = fs.readFileSync(
	new URL('../../js/ThothSection.js', import.meta.url),
	'utf8',
);
const templateSource = fs.readFileSync(
	new URL('../../templates/thothSection.tpl', import.meta.url),
	'utf8',
);
const styleSource = fs.readFileSync(
	new URL('../../styles/thothSection.css', import.meta.url),
	'utf8',
);
const enLocaleSource = fs.readFileSync(
	new URL('../../locale/en/locale.po', import.meta.url),
	'utf8',
);
const ptBRLocaleSource = fs.readFileSync(
	new URL('../../locale/pt_BR/locale.po', import.meta.url),
	'utf8',
);

function loadWorkflow(overrides = {}) {
	const ajaxCalls = [];
	const eventHandlers = {};
	const modalCalls = [];
	const refreshSubmissionCalls = [];
	const workflow = {
		hasLinkedWork: true,
		unlinkCancel: 'Cancel',
		unlinkConfirm: 'Confirm unlink',
		unlinkError: 'Unable to unlink',
		unlinkTitle: 'Unlink',
		unlinkUrl: 'api/_submissions/13/thothWork',
		workStatusError: 'Error',
		workStatusLabels: {
			ACTIVE: 'Active',
		},
		workStatusNotFound: 'Work not found in Thoth',
		workStatusUrl: 'api/_submissions/13/thothWorkStatus',
		...overrides,
	};
	const $ = function () {
		return {
			pkpHandler(handler, options) {
				modalCalls.push({handler, options});
			},
		};
	};
	$.ajax = (options) => ajaxCalls.push(options);
	$.pkp = {
		classes: {
			Helper: {
				uuid: () => 'modal-id',
			},
		},
		plugins: {
			generic: {
				thothplugin: {workflow},
			},
		},
	};
	const pkp = {
		currentUser: {csrfToken: 'csrf-token'},
		eventBus: {
			$emit() {},
			$on(event, handler) {
				eventHandlers[event] = handler;
			},
		},
		registry: {
			_instances: {
				app: {
					$forceUpdate() {},
					refreshSubmission() {
						refreshSubmissionCalls.push(true);
					},
				},
			},
		},
	};

	vm.runInNewContext(source, {
		$,
		document: {activeElement: {focus() {}}},
		pkp,
	});
	const statusRequest = ajaxCalls[0];
	ajaxCalls.length = 0;

	return {
		ajaxCalls,
		eventHandlers,
		modalCalls,
		refreshSubmissionCalls,
		statusRequest,
		workflow,
	};
}

test('loads the Work status before making linked Work actions available', () => {
	const {statusRequest, workflow} = loadWorkflow();

	assert.equal(statusRequest.method, 'GET');
	assert.equal(statusRequest.url, 'api/_submissions/13/thothWorkStatus');
	assert.equal(workflow.workStatusLoaded, false);

	statusRequest.success({workStatus: 'ACTIVE'});
	statusRequest.complete();

	assert.equal(workflow.workStatusLoaded, true);
	assert.equal(workflow.workStatus, 'ACTIVE');
	assert.equal(workflow.getWorkStatusLabel(), 'Active');
	assert.equal(workflow.canShowLinkedWorkActions(), true);
});

test('uses DOM-safe spinner tags around the Work status result', () => {
	assert.doesNotMatch(templateSource, /<spinner\b[^>]*\/>/);
});

test('keeps the Thoth Status heading visible while loading the Work status', () => {
	const headingIndex = templateSource.indexOf('<strong>Thoth Status:</strong>');
	const linkedWorkIndex = templateSource.indexOf(
		'<span v-if="submission.thothWorkId">',
	);

	assert.notEqual(headingIndex, -1);
	assert.ok(headingIndex < linkedWorkIndex);
	assert.match(
		templateSource,
		/\{\{\s+\$\.pkp\.plugins\.generic\.thothplugin\.workflow\.getWorkStatusLabel\(\)\s+\}\}/,
	);
});

test('uses the short missing Work labels in English and Portuguese', () => {
	assert.match(
		enLocaleSource,
		/msgid "plugins\.generic\.thoth\.status\.notFound"\nmsgstr "Not found"/,
	);
	assert.match(
		ptBRLocaleSource,
		/msgid "plugins\.generic\.thoth\.status\.notFound"\nmsgstr "Não encontrado"/,
	);
});

test('uses links for every Thoth action', () => {
	const actionTags = templateSource.match(/<a\b[\s\S]*?>/g) || [];
	const localActionTags = actionTags.filter((actionTag) =>
		actionTag.includes('@click.prevent'),
	);

	assert.doesNotMatch(templateSource, /class="pkpButton"/);
	assert.doesNotMatch(templateSource, /<pkp-button\b/);
	assert.equal(actionTags.length, 4);
	assert.equal(localActionTags.length, 3);
	localActionTags.forEach((actionTag) =>
		assert.match(actionTag, /\bhref="#"/),
	);
	assert.match(
		styleSource,
		/\.pkpPublication__thoth a,\n\.pkpPublication__thoth button \{\n\s+padding: 0\.5rem;/,
	);
});

test('shows only the unlink action when the linked Work was not found', () => {
	const {statusRequest, workflow} = loadWorkflow();

	statusRequest.error({
		status: 404,
		responseJSON: {workNotFound: true},
	});
	statusRequest.complete();

	assert.equal(workflow.workStatusLoaded, true);
	assert.equal(workflow.workNotFound, true);
	assert.equal(workflow.getWorkStatusLabel(), 'Work not found in Thoth');
	assert.equal(workflow.canShowLinkedWorkActions(), false);
});

test('keeps linked Work actions hidden when the status request fails', () => {
	const {statusRequest, workflow} = loadWorkflow();

	statusRequest.error({status: 500, responseJSON: {}});
	statusRequest.complete();

	assert.equal(workflow.workStatusLoaded, true);
	assert.equal(workflow.fetchError, true);
	assert.equal(workflow.getWorkStatusLabel(), 'Error');
	assert.equal(workflow.canShowLinkedWorkActions(), false);
});

test('does not request a Work status when the submission is not linked', () => {
	const {statusRequest, workflow} = loadWorkflow({hasLinkedWork: false});

	assert.equal(statusRequest, undefined);
	assert.equal(workflow.workStatusLoaded, true);
});

test('requests the Work status after registration succeeds', () => {
	const {
		ajaxCalls,
		eventHandlers,
		refreshSubmissionCalls,
	} = loadWorkflow({hasLinkedWork: false});

	eventHandlers['form-success']('register');

	assert.equal(refreshSubmissionCalls.length, 1);
	assert.equal(ajaxCalls.length, 1);
	assert.equal(ajaxCalls[0].url, 'api/_submissions/13/thothWorkStatus');
});

test('opens an OMP confirmation modal before unlinking the Work', () => {
	const {ajaxCalls, modalCalls, workflow} = loadWorkflow();

	workflow.unlinkWork();

	assert.equal(ajaxCalls.length, 0);
	assert.equal(modalCalls.length, 1);
	assert.equal(
		modalCalls[0].handler,
		'$.pkp.controllers.modal.ConfirmationModalHandler',
	);
	assert.equal(modalCalls[0].options.dialogText, 'Confirm unlink');
	assert.equal(modalCalls[0].options.cancelButton, 'Cancel');
});

test('unlinks the Work only after modal confirmation', () => {
	const {ajaxCalls, modalCalls, workflow} = loadWorkflow();

	workflow.unlinkWork();
	modalCalls[0].options.callback();

	assert.equal(ajaxCalls.length, 1);
	assert.equal(ajaxCalls[0].url, 'api/_submissions/13/thothWork');
	assert.equal(ajaxCalls[0].headers['X-Http-Method-Override'], 'DELETE');
});
