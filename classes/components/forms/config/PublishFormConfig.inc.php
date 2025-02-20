<?php

/**
 * @file plugins/generic/thoth/classes/components/forms/config/PublishFormConfig.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublishFormConfig
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Thoth config for publish form
 */

use APP\facades\Repo;
use APP\submission\Submission;
use ThothApi\GraphQL\Models\Work as ThothWork;

import('plugins.generic.thoth.classes.facades.ThothService');
import('plugins.generic.thoth.classes.facades.ThothRepository');

class PublishFormConfig
{
    public function addConfig($hookName, $form)
    {
        if ($form->id !== 'publish' || !empty($form->errors)) {
            return;
        }

        $publication = $form->publication;
        $submission = Repo::submission()->get($publication->getData('submissionId'));

        if ($submission->getData('thothWorkId')) {
            return;
        }

        try {
            $errors = ThothService::book()->validate($publication);

            if (empty($errors)) {
                $publishers = ThothRepository::account()->getLinkedPublishers();
                $imprints = ThothRepository::imprint()->getMany(array_column($publishers, 'publisherId'));
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            $errors = [__('plugins.generic.thoth.connectionError')];
        }

        if (!empty($errors)) {
            $this->showErrors($form, $errors);
            return false;
        }

        $this->addFields($form, $imprints, $submission->getData('workType'));

        return false;
    }

    private function addFields($form, $imprints, $workType)
    {
        $imprintOptions = [];
        foreach ($imprints as $imprint) {
            $imprintOptions[] = [
                'value' => $imprint->getImprintId(),
                'label' => $imprint->getImprintName()
            ];
        }

        $form->addField(new \PKP\components\forms\FieldOptions('registerConfirmation', [
            'label' => __('plugins.generic.thoth.register.label'),
            'options' => [
                ['value' => true, 'label' => __('plugins.generic.thoth.register.confirmation')]
            ],
            'value' => false,
            'groupId' => 'default',
        ]))
            ->addField(new \PKP\components\forms\FieldSelect('thothImprintId', [
                'label' => __('plugins.generic.thoth.imprint'),
                'options' => $imprintOptions,
                'required' => true,
                'showWhen' => 'registerConfirmation',
                'groupId' => 'default',
                'value' => $imprintOptions[0]['value'] ?? null
            ]));

        if ($workType !== Submission::WORK_TYPE_AUTHORED_WORK) {
            return;
        }

        $workTypeOptions = [
            [
                'value' => ThothWork::WORK_TYPE_MONOGRAPH,
                'label' => __('plugins.generic.thoth.workType.monograph')
            ],
            [
                'value' => ThothWork::WORK_TYPE_TEXTBOOK,
                'label' => __('plugins.generic.thoth.workType.textbook')
            ],
        ];

        $form->addField(new \PKP\components\forms\FieldSelect('thothWorkType', [
            'label' => __('plugins.generic.thoth.workType'),
            'options' => $workTypeOptions,
            'required' => true,
            'showWhen' => 'registerConfirmation',
            'groupId' => 'default',
            'value' => $workTypeOptions[0]['value'] ?? null
        ]));
    }

    private function showErrors($form, $errors)
    {
        $msg = '<div class="pkpNotification pkpNotification--warning">';
        $msg .= __('plugins.generic.thoth.register.warning');
        $msg .= '<ul>';
        foreach ($errors as $error) {
            $msg .= '<li>' . $error . '</li>';
        }
        $msg .= '</ul></div>';

        $form->addField(new \PKP\components\forms\FieldHTML('registerNotice', [
            'description' => $msg,
            'groupId' => 'default',
        ]));
    }
}
