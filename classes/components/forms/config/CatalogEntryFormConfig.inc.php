<?php

/**
 * @file plugins/generic/thoth/classes/components/forms/config/CatalogEntryFormConfig.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CatalogEntryFormConfig
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Thoth config for catalog entry form
 */

use APP\facades\Repo;

import('plugins.generic.thoth.classes.facades.ThothRepo');
import('plugins.generic.thoth.classes.services.ThothMeCacheService');

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

        $canUploadFiles = $this->canUploadFiles($publication);
        $form->addField(new \PKP\components\forms\FieldOptions('thothUploadFrontcover', [
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

    protected function canUploadFiles($publication): bool
    {
        try {
            $submission = $this->getSubmission($publication->getData('submissionId'));
            return $this->hasCdnWritePermission($submission->getData('contextId'));
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    protected function getSubmission($submissionId)
    {
        return Repo::submission()->get($submissionId);
    }

    protected function hasCdnWritePermission($contextId): bool
    {
        return (new ThothMeCacheService(ThothRepo::me()))->hasCdnWritePermission($contextId);
    }
}
