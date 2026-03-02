/**
 * @file resources/js/main.js
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Entry point for Thoth plugin Vue components and workflow extensions
 */

import ThothSection from './Components/ThothSection.vue';
import ThothRegisterButton from './Components/ThothRegisterButton.vue';

pkp.registry.registerComponent('ThothSection', ThothSection);
pkp.registry.registerComponent('ThothRegisterButton', ThothRegisterButton);

pkp.registry.storeExtend('workflow', (piniaContext) => {
	const workflowStore = piniaContext.store;

	workflowStore.extender.extendFn(
		'getPrimaryControlsLeft',
		(primaryControlsLeft, args) => {
			if (args?.selectedMenuState?.primaryMenuItem !== 'publication') {
				return primaryControlsLeft;
			}

			const {submission} = args;

			if (
				submission.status !== pkp.const.STATUS_PUBLISHED &&
				!submission.thothWorkId
			) {
				return primaryControlsLeft;
			}

			const thothData = pkp.plugins?.generic?.thoth?.workflow || {};

			const workStatusUrl = (thothData.workStatusUrl || '')
				.replace('__submissionId__', submission.id);

			return [
				...primaryControlsLeft,
				{
					component: 'ThothSection',
					props: {
						submission,
						workStatusUrl,
					},
				},
			];
		},
	);

	workflowStore.extender.extendFn(
		'getPrimaryControlsRight',
		(primaryControlsRight, args) => {
			if (args?.selectedMenuState?.primaryMenuItem !== 'publication') {
				return primaryControlsRight;
			}

			const {submission, selectedPublicationId} = args;

			if (
				submission.status !== pkp.const.STATUS_PUBLISHED &&
				!submission.thothWorkId
			) {
				return primaryControlsRight;
			}

			const thothData = pkp.plugins?.generic?.thoth?.workflow || {};

			const registerUrl = (thothData.registerUrl || '')
				.replace('__submissionId__', submission.id);
			const publicationUrl = (thothData.publicationUrl || '')
				.replace('__submissionId__', submission.id);

			return [
				...primaryControlsRight,
				{
					component: 'ThothRegisterButton',
					props: {
						submission,
						selectedPublicationId,
						registerUrl,
						publicationUrl,
						registerTitle: thothData.registerTitle || '',
					},
				},
			];
		},
	);
});
