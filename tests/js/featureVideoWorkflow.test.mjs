import assert from 'node:assert/strict';
import test from 'node:test';

import {
	addFeatureVideoMenuItem,
	getFeatureVideoPrimaryItems,
} from '../../resources/js/featureVideoWorkflow.mjs';

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
