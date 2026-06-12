{**
 * plugins/generic/thoth/templates/form/uploadThothPublicationFileForm.tpl
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Template for the publication file upload form used in the publication format grid.
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the upload form handler.
		$('#uploadThothPublicationFileForm').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#plupload'),
				uploaderOptions: {ldelim}
					uploadUrl: {url|json_encode router=$smarty.const.ROUTE_PAGE page="thoth" op="handleThothPublicationFile" escape=false},
					baseUrl: {$baseUrl|json_encode}
				{rdelim}
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="uploadThothPublicationFileForm" action="{url router=$smarty.const.ROUTE_PAGE page="thoth" op="saveUploadThothPublicationFile"}" method="post">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="uploadThothPublicationFileNotification"}

	{fbvFormArea id="file"}
		{fbvFormSection title="common.file" required=true}
			{include file="controllers/fileUploadContainer.tpl" id="plupload"}
			<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons id="uploadThothPublicationFileFormSubmit" submitText="common.save"}
</form>
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
