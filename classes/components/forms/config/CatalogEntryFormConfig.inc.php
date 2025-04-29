<?php

/**
 * @file plugins/generic/thoth/classes/components/forms/config/CatalogEntryFormConfig.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CatalogEntryFormConfig
 * @ingroup plugins_generic_thoth
 *
 * @brief Thoth config for catalog entry form
 */

class CatalogEntryFormConfig
{
    public function addConfig($hookName, $form)
    {
        if ($form->id !== 'catalogEntry' || !empty($form->errors)) {
            return;
        }

        $actionParts = explode('/', $form->action);
        $publicationId = end($actionParts);
        $publication = Services::get('publication')->get($publicationId);

        $form->addField(new \PKP\components\forms\FieldText('place', [
            'label' => __('plugins.generic.thoth.field.place.label'),
            'value' => $publication->getData('place'),
        ]))
        ->addField(new \PKP\components\forms\FieldText('pageCount', [
            'label' => __('plugins.generic.thoth.field.pageCount.label'),
            'value' => $publication->getData('pageCount'),
        ]))
        ->addField(new \PKP\components\forms\FieldText('imageCount', [
            'label' => __('plugins.generic.thoth.field.imageCount.label'),
            'value' => $publication->getData('imageCount'),
        ]));

        return false;
    }
}
