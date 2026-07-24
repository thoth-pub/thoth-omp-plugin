/**
 * @file resources/js/main.js
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Entry point for Thoth plugin Vue components and workflow extensions
 */

import ThothSection from './Components/ThothSection.vue';
import ThothListPanel from './Components/ThothListPanel.vue';
import FeatureVideoForm from './Components/FeatureVideoForm.vue';
import {
	addFeatureVideoMenuItem,
	getFeatureVideoPrimaryItems,
} from './featureVideoWorkflow.mjs';
import './thoth.css';

pkp.registry.registerComponent('ThothSection', ThothSection);
pkp.registry.registerComponent('ThothListPanel', ThothListPanel);
pkp.registry.registerComponent('FeatureVideoForm', FeatureVideoForm);

pkp.registry.storeExtend('workflow', (piniaContext) => {
	const workflowStore = piniaContext.store;
	const {useLocalize} = pkp.modules.useLocalize;
	const {t} = useLocalize();
	const featureVideoLabel = t('plugins.generic.thoth.featureVideo');

	workflowStore.extender.extendFn('getMenuItems', (menuItems) =>
		addFeatureVideoMenuItem(menuItems, featureVideoLabel),
	);

	workflowStore.extender.extendFn(
		'getPrimaryItems',
		(primaryItems, args) => getFeatureVideoPrimaryItems(primaryItems, args),
	);

	workflowStore.extender.extendFn(
		'getPrimaryControlsLeft',
		(primaryControlsLeft, args) => {
			if (args?.selectedMenuState?.primaryMenuItem !== 'publication') {
				return primaryControlsLeft;
			}

			const {submission, selectedPublicationId} = args;

			if (
				submission.status !== pkp.const.STATUS_PUBLISHED &&
				!submission.thothWorkId
			) {
				return primaryControlsLeft;
			}

			const thothData = pkp.plugins?.generic?.thoth?.workflow || {};

			const workStatusUrl = (thothData.workStatusUrl || '')
				.replace('__submissionId__', submission.id);
			const unlinkUrl = (thothData.unlinkUrl || '').replace(
				'__submissionId__',
				submission.id,
			);

			const registerUrl = (thothData.registerUrl || '')
				.replace('__submissionId__', submission.id);
			const synchronizeUrl = (thothData.synchronizeUrl || '')
				.replace('__submissionId__', submission.id);

			return [
				...primaryControlsLeft,
				{
					component: 'ThothSection',
					props: {
						submission,
						selectedPublicationId,
						workStatusUrl,
						unlinkUrl,
						registerUrl,
						synchronizeUrl,
						registerTitle: thothData.registerTitle || '',
					},
				},
			];
		},
	);
});
