import test from 'node:test';
import assert from 'node:assert/strict';
import {getThothActionVisibility} from '../../resources/js/thothActionVisibility.mjs';

const noActions = {
	view: false,
	unlink: false,
	update: false,
	register: false,
};

test('hides every Thoth action while the Work status is loading', () => {
	assert.deepEqual(
		getThothActionVisibility({
			hasWorkLink: true,
			workStatus: 'ACTIVE',
			statusRequestCompleted: false,
			workNotFound: false,
			fetchError: false,
			isPublished: false,
		}),
		noActions,
	);
});

test('shows only the Work status when its request fails', () => {
	assert.deepEqual(
		getThothActionVisibility({
			hasWorkLink: true,
			workStatus: null,
			statusRequestCompleted: true,
			workNotFound: false,
			fetchError: true,
			isPublished: false,
		}),
		noActions,
	);
});

test('shows only Unlink when the Work was not found', () => {
	assert.deepEqual(
		getThothActionVisibility({
			hasWorkLink: true,
			workStatus: null,
			statusRequestCompleted: true,
			workNotFound: true,
			fetchError: false,
			isPublished: false,
		}),
		{
			...noActions,
			unlink: true,
		},
	);
});

test('shows linked Work actions only after its status is loaded', () => {
	assert.deepEqual(
		getThothActionVisibility({
			hasWorkLink: true,
			workStatus: 'ACTIVE',
			statusRequestCompleted: true,
			workNotFound: false,
			fetchError: false,
			isPublished: false,
		}),
		{
			...noActions,
			view: true,
			update: true,
		},
	);
});

test('keeps Register available when the submission has no Work link', () => {
	assert.deepEqual(
		getThothActionVisibility({
			hasWorkLink: false,
			workStatus: null,
			statusRequestCompleted: false,
			workNotFound: false,
			fetchError: false,
			isPublished: false,
		}),
		{
			...noActions,
			register: true,
		},
	);
});
