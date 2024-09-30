{**
 * plugins/generic/thoth/thothBagde.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Thoth badge
 *
 *}

<span v-if="submission.status === getConstant('STATUS_PUBLISHED') || submission.thothWorkId" class="pkpPublication__thoth">
    <strong>
        {translate key="plugins.generic.thoth.thothBook"}
    </strong>
    <span v-if="submission.thothWorkId">
        <a class="pkpButton" :href="'https://thoth.pub/books/' + submission.thothWorkId">
            {translate key="common.view"}
        </a>
        <pkp-button
            v-if="submission.status !== getConstant('STATUS_PUBLISHED')"
            @click="$.pkp.plugins.generic.thothplugin.updateMetadata(workingPublication.id)"
        >
            {translate key="plugins.generic.thoth.update"}
        </pkp-button>
        <spinner v-if="$.pkp.plugins.generic.thothplugin.loading" />
    </span>
    <span v-else>
        <pkp-button @click="$.pkp.plugins.generic.thothplugin.openRegister(workingPublication.id)">
            {translate key="plugins.generic.thoth.register"}
        </pkp-button>
    </span>
</span>