{**
 * plugins/generic/thoth/thothBagde.tpl
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

<span v-if="submission.thothWorkId" style="margin-left: 1rem">
    <strong>
        {translate key="plugins.generic.thoth.thothBook"}
    </strong>
    <span>
        <a :href="'https://thoth.pub/books/' + submission.thothWorkId">
            {{ submission.thothWorkId }}
        </a>
    </span>
</span>