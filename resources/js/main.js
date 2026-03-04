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

pkp.registry.registerComponent('ThothSection', ThothSection);
pkp.registry.registerComponent('ThothListPanel', ThothListPanel);

pkp.registry.storeExtend('workflow', (piniaContext) => {
	const workflowStore = piniaContext.store;

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

			const registerUrl = (thothData.registerUrl || '')
				.replace('__submissionId__', submission.id);
			const publicationUrl = (thothData.publicationUrl || '')
				.replace('__submissionId__', submission.id);

			return [
				...primaryControlsLeft,
				{
					component: 'ThothSection',
					props: {
						submission,
						selectedPublicationId,
						workStatusUrl,
						registerUrl,
						publicationUrl,
						registerTitle: thothData.registerTitle || '',
					},
				},
			];
		},
	);
});
