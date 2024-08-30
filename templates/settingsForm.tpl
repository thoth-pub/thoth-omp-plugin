{**
 * plugins/generic/thoth/settingsForm.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
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
			{fbvElement type="text" id="imprintId" label="plugins.generic.thoth.settings.imprintId" value=$imprintId required="true"}
			</p>
			{fbvElement type="email" id="email" label="plugins.generic.thoth.settings.email" value=$email required="true"}
			</p>
			{fbvElement type="text" password="true" id="password" label="plugins.generic.thoth.settings.password" value=$password required="true"}
		{/fbvFormSection}

		{fbvFormSection list="true" title="plugins.generic.thoth.settings.sandbox"}
			{fbvElement type="checkbox" id="sandbox" label="plugins.generic.thoth.settings.sandbox.description" checked=$sandbox}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
