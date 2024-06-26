{**
 * plugins/generic/thoth/settingsForm.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2024 Lepidus Tecnologia
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

    {fbvFormSection label="plugins.generic.thoth.settings.apiKey"}
		{fbvElement type="text" password="true" id="apiKey" value=$apiKey}
	{/fbvFormSection}

	{fbvFormButtons}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
