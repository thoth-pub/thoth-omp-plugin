import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const bundle = readFileSync(new URL('../../public/build/build.iife.js', import.meta.url), 'utf8');

function workflowControls(args) {
	const extensions = new Map();
	const pkp = {
		const: {STATUS_PUBLISHED: 3},
		modules: {
			vue: {},
			useLocalize: {useLocalize: () => ({t: (key) => key})},
		},
		plugins: {generic: {thoth: {workflow: {
			registerUrl: '/submissions/__submissionId__/register',
		}}}},
		registry: {
			registerComponent() {},
			storeExtend(name, extend) {
				extend({store: {extender: {extendFn(key, callback) {
					extensions.set(key, callback);
				}}}});
			},
		},
	};
	vm.runInNewContext(bundle, {pkp});
	return extensions.get('getPrimaryControlsLeft')([], args);
}

test('offers individual registration for an unpublished book without a Thoth link', () => {
	const controls = workflowControls({
		selectedMenuState: {primaryMenuItem: 'publication'},
		submission: {id: 42, status: 1},
		selectedPublicationId: 17,
	});
	assert.equal(controls.length, 1);
	assert.equal(controls[0].component, 'ThothSection');
	assert.equal(controls[0].props.registerUrl, '/submissions/42/register');
});

test('keeps Thoth controls limited to the publication menu', () => {
	assert.equal(workflowControls({
		selectedMenuState: {primaryMenuItem: 'review'},
		submission: {id: 42, status: 1},
	}).length, 0);
});
