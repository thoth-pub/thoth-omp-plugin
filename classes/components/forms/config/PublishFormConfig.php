<?php

/**
 * @file plugins/generic/thoth/classes/components/forms/config/PublishFormConfig.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublishFormConfig
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Thoth config for publish form
 */

namespace APP\plugins\generic\thoth\classes\components\forms\config;

use APP\facades\Repo;
use APP\plugins\generic\thoth\classes\facades\ThothRepository;
use APP\plugins\generic\thoth\classes\facades\ThothService;
use APP\plugins\generic\thoth\classes\notification\ThothNotification;
use APP\submission\Submission;
use Exception;
use ThothApi\GraphQL\Models\Work as ThothWork;

class PublishFormConfig
{
    public function addConfig($hookName, $form)
    {
        if ($form->id !== 'publish' || !empty($form->errors)) {
            return;
        }

        $publication = $form->publication;
        $submission = $this->getSubmission($publication);

        if ($submission->getData('thothWorkId')) {
            return;
        }

        try {
            $errors = $this->validatePublication($publication);

            if (empty($errors)) {
                $imprints = $this->getImprints();
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

    protected function getSubmission($publication)
    {
        return Repo::submission()->get($publication->getData('submissionId'));
    }

    protected function validatePublication($publication): array
    {
        return ThothService::book()->validate($publication);
    }

    protected function getImprints(): array
    {
        $publishers = ThothRepository::account()->getLinkedPublishers();
        return ThothRepository::imprint()->getMany(array_column($publishers, 'publisherId'));
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
