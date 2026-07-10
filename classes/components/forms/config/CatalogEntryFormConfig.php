<?php

/**
 * @file plugins/generic/thoth/classes/components/forms/config/CatalogEntryFormConfig.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CatalogEntryFormConfig
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Thoth config for catalog entry form
 */

namespace APP\plugins\generic\thoth\classes\components\forms\config;

use APP\facades\Repo;
use APP\plugins\generic\thoth\classes\facades\ThothService;
use Exception;
use PKP\components\forms\FieldOptions;
use PKP\components\forms\FieldText;

class CatalogEntryFormConfig
{
    public function addConfig($hookName, $form)
    {
        if ($form->id !== 'catalogEntry' || !empty($form->errors)) {
            return;
        }

        $actionParts = explode('/', $form->action);
        $publicationId = end($actionParts);
        $publication = $this->getPublication($publicationId);

        $form->addField(new FieldText('place', [
            'label' => __('plugins.generic.thoth.field.place.label'),
            'value' => $publication->getData('place'),
        ]))
            ->addField(new FieldText('pageCount', [
                'label' => __('plugins.generic.thoth.field.pageCount.label'),
                'value' => $publication->getData('pageCount'),
            ]))
            ->addField(new FieldText('imageCount', [
                'label' => __('plugins.generic.thoth.field.imageCount.label'),
                'value' => $publication->getData('imageCount'),
            ]));

        $canUploadFiles = $this->canUploadFiles();
        $form->addField(new FieldOptions('thothUploadFrontcover', [
            'label' => __('plugins.generic.thoth.field.frontcover'),
            'description' => $canUploadFiles
                ? null
                : __('plugins.generic.thoth.field.frontcover.missingCdnWritePermission'),
            'options' => [
                [
                    'value' => true,
                    'label' => __('plugins.generic.thoth.field.frontcover.label'),
                    'disabled' => !$canUploadFiles,
                ],
            ],
            'value' => $publication->getData('thothUploadFrontcover') && $canUploadFiles ? [true] : [],
        ]), ['after', 'coverImage']);

        return false;
    }

    protected function getPublication($publicationId)
    {
        return Repo::publication()->get($publicationId);
    }

    protected function canUploadFiles(): bool
    {
        try {
            return ThothService::me()->hasCdnWritePermission();
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
