import assert from 'node:assert/strict';
import test from 'node:test';

import {openUnlinkWorkConfirmation} from '../../resources/js/unlinkWorkConfirmation.mjs';

test('only unlinks the Work after confirmation', () => {
	let dialog;
	let unlinkCalls = 0;
	const closedDialogs = [];
	const close = () => closedDialogs.push(true);

	openUnlinkWorkConfirmation({
		openDialog: (config) => {
			dialog = config;
		},
		title: 'Unlink',
		message: 'Confirm unlink',
		cancelLabel: 'Cancel',
		onConfirm: () => {
			unlinkCalls++;
		},
	});

	assert.equal(unlinkCalls, 0);
	assert.equal(dialog.message, 'Confirm unlink');

	dialog.actions[1].callback(close);
	assert.equal(unlinkCalls, 0);
	assert.equal(closedDialogs.length, 1);

	dialog.actions[0].callback(close);
	assert.equal(unlinkCalls, 1);
	assert.equal(closedDialogs.length, 2);
});
