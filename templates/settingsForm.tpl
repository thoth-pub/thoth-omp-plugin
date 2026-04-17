{**
 * plugins/generic/thoth/settingsForm.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Thoth plugin settings
 *
 *}

<script>
	$(function() {ldelim}
		$('#thothSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form
    class="pkp_form"
    id="thothSettingsForm"
    method="post"
    action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}"
>
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="thothSettingsFormNotification"}

	{fbvFormArea id="thothSettings"}
		<h3>
            {translate key="plugins.generic.thoth.settings.title" }
        </h3>

		{fbvFormSection}
			{fbvElement type="text" password="true" id="token" label="plugins.generic.thoth.settings.token" value=$token required="true"}
		{/fbvFormSection}

		{fbvFormSection list="true" title="plugins.generic.thoth.settings.customThothApi"}
			{fbvElement type="checkbox" id="customThothApi" label="plugins.generic.thoth.settings.customThothApi.description" checked=$customThothApi}
		{/fbvFormSection}

		{fbvFormSection}
			{fbvElement type="text" id="customThothApiUrl" label="plugins.generic.thoth.settings.customThothApiUrl" value=$customThothApiUrl}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
