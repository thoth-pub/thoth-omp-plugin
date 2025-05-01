<?php

/**
 * @file plugins/generic/thoth/classes/components/forms/config/ContributorFormConfig.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContributorFormConfig
 * @ingroup plugins_generic_thoth
 *
 * @brief Thoth configuration for contributor form
 */

use APP\facades\Repo;

class ContributorFormConfig
{
    public function addConfig($hookName, $form)
    {
        if ($form->id !== 'contributor' || !empty($form->errors)) {
            return;
        }

        $form->addField(new \PKP\components\forms\FieldOptions('mainContribution', [
            'label' => __('plugins.generic.thoth.field.mainContribution'),
            'value' => false,
            'options' => [
                [
                    'value' => true,
                    'label' => __('plugins.generic.thoth.field.mainContribution.label'),
                ],
            ],
        ]));

        return false;
    }
}
