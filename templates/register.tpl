{**
 * plugins/generic/thoth/register.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Final Thoth registration confirmation for a publication
 *}

{assign var="uuid" value=""|uniqid|escape}
<div id="register-{$uuid}" class="pkpWorkflow__thothRegisterModal">
  <pkp-form v-bind="components.register" @set="set" />
	<script type="text/javascript">
		pkp.registry.init('register-{$uuid}', 'Container', {$registerData|json_encode});
	</script>
</div>