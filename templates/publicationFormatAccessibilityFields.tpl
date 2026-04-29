{**
 * plugins/generic/thoth/templates/publicationFormatAccessibilityFields.tpl
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Thoth accessibility metadata fields for publication formats
 *}

{fbvFormSection for="thothAccessibility"}
	<div class="thothAccessibilityHeader">
		<span class="thothAccessibilityHeader__label">
			{translate key="plugins.generic.thoth.publicationFormat.accessibility"}
		</span>
		<div class="thothAccessibilityHelp">
			<button
				type="button"
				id="thothAccessibilityHelpButton"
				class="tooltipButton thothAccessibilityHelp__button"
				aria-describedby="thothAccessibilityHelp"
			>
				<span class="fa fa-question-circle" aria-hidden="true"></span>
				<span class="-screenReader">
					{translate key='plugins.generic.thoth.publicationFormat.accessibilityHelp'}
				</span>
			</button>
			<div id="thothAccessibilityHelp" class="thothAccessibilityHelp__popover" role="tooltip">
				{translate key='plugins.generic.thoth.publicationFormat.accessibilityHelp.description'}
			</div>
		</div>
	</div>
	{fbvElement type="select" id="accessibilityStandard" label="plugins.generic.thoth.publicationFormat.accessibilityStandard" from=$thothAccessibilityStandardOptions selected=$accessibilityStandard size=$fbvStyles.size.MEDIUM inline=true}
	{fbvElement type="select" id="accessibilityAdditionalStandard" label="plugins.generic.thoth.publicationFormat.accessibilityAdditionalStandard" from=$thothAccessibilityStandardOptions selected=$accessibilityAdditionalStandard size=$fbvStyles.size.MEDIUM inline=true}
	{fbvElement type="select" id="accessibilityException" label="plugins.generic.thoth.publicationFormat.accessibilityException" from=$thothAccessibilityExceptionOptions selected=$accessibilityException size=$fbvStyles.size.MEDIUM inline=true}
	{fbvElement type="text" id="accessibilityReportUrl" label="plugins.generic.thoth.publicationFormat.accessibilityReportUrl" value=$accessibilityReportUrl size=$fbvStyles.size.MEDIUM inline=true}
{/fbvFormSection}
