{**
 * plugins/generic/thoth/templates/modal/thothPublicationFormatFiles.tpl
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Modal listing Thoth files for a publication format.
 *}

<div>
	{if empty($thothFiles)}
		<p>{translate key="plugins.generic.thoth.publicationFormat.thothFiles.empty"}</p>
	{else}
		<table class="pkpTable">
			<thead>
				<tr>
					<th>{translate key="plugins.generic.thoth.publicationFormat.thothFiles.component"}</th>
					<th>{translate key="plugins.generic.thoth.publicationFormat.thothFiles.file"}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$thothFiles item=thothFile}
					<tr>
						<td>{$thothFile.component|escape}</td>
						<td>
							<a href="{$thothFile.file.url|escape}" target="_blank" rel="noopener noreferrer">
								{$thothFile.file.label|escape}
							</a>
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	{/if}
</div>
