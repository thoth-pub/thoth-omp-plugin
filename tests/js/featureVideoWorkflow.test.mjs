import assert from 'node:assert/strict';
import test from 'node:test';

import {
	addFeatureVideoMenuItem,
	getFormComposable,
	getTranslator,
	getFeatureVideoPrimaryItems,
} from '../../resources/js/featureVideoWorkflow.mjs';

test('gets useForm from the OMP form module', () => {
	const useForm = () => ({form: 'form-state'});

	assert.equal(getFormComposable({useForm}), useForm);
});

test('gets the translator from the OMP localize module', () => {
	const useLocalizeModule = {
		useLocalize: () => ({
			t: (key) => `translated:${key}`,
		}),
	};

	assert.equal(getTranslator(useLocalizeModule)('translation.key'), 'translated:translation.key');
});

test('adds feature video immediately after publication dates', () => {
	const menuItems = [
		{
			key: 'marketing',
			items: [
				{key: 'marketing_audience'},
				{key: 'marketing_publicationDates'},
			],
		},
	];

	const result = addFeatureVideoMenuItem(menuItems, 'Feature video');

	assert.deepEqual(
		result[0].items.map(({key}) => key),
		[
			'marketing_audience',
			'marketing_publicationDates',
			'marketing_featureVideo',
		],
	);
});

test('shows feature video form for its marketing item', () => {
	const result = getFeatureVideoPrimaryItems([], {
		selectedMenuState: {
			primaryMenuItem: 'marketing',
			secondaryMenuItem: 'featureVideo',
		},
		submission: {id: 12},
	});

	assert.deepEqual(result, [
		{
			component: 'FeatureVideoForm',
			props: {submission: {id: 12}},
		},
	]);
});
