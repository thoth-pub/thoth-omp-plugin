{**
 * plugins/generic/thoth/templates/thothSection.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Thoth section
 *
 *}

<span
    v-if="submission.status === getConstant('STATUS_PUBLISHED') || submission.thothWorkId" class="pkpPublication__thoth"
>
    <strong>Thoth Status:</strong>
    <span v-if="submission.thothWorkId">
        <spinner v-if="!$.pkp.plugins.generic.thothplugin.workflow.workStatusLoaded"></spinner>
        <template v-else>
            <span class="thothWorkStatus">
                <span
                    class="thothWorkStatus__indicator"
                    :class="$.pkp.plugins.generic.thothplugin.workflow.getWorkStatusClass()"
                    aria-hidden="true"
                ></span>
                <span>{{ $.pkp.plugins.generic.thothplugin.workflow.getWorkStatusLabel() }}</span>
            </span>
            <a
                v-if="$.pkp.plugins.generic.thothplugin.workflow.workNotFound"
                href="#"
                @click.prevent="$.pkp.plugins.generic.thothplugin.workflow.unlinkWork()"
            >
                {translate key="plugins.generic.thoth.unlink"}
            </a>
            <template v-else-if="$.pkp.plugins.generic.thothplugin.workflow.canShowLinkedWorkActions()">
                <a
                    target="_blank"
                    rel="noopener noreferrer"
                    :href="'https://thoth.pub/books/' + submission.thothWorkId"
                >
                    {translate key="common.view"}
                </a>
                <a
                    v-if="submission.status !== getConstant('STATUS_PUBLISHED')"
                    href="#"
                    @click.prevent="$.pkp.plugins.generic.thothplugin.workflow.updateMetadata(workingPublication.id)"
                >
                    {translate key="plugins.generic.thoth.update"}
                </a>
            </template>
            <spinner v-if="$.pkp.plugins.generic.thothplugin.workflow.loading"></spinner>
        </template>
    </span>
    <span v-else>
        <a
            href="#"
            @click.prevent="$.pkp.plugins.generic.thothplugin.workflow.openRegister(workingPublication.id)"
        >
            {translate key="plugins.generic.thoth.register"}
        </a>
    </span>
</span>
