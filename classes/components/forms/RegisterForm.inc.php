<?php

/**
 * @file plugins/generic/thoth/classes/components/form/RegisterForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Copyright (c) 2024 Lepidus Tecnologia
 * Copyright (c) 2024 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RegisterForm
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A preset form for confirming a publication's issue before publishing.
 *   It may also be used for scheduling a publication in an issue for later
 *   publication.
 */

use PKP\components\forms\FieldHTML;
use PKP\components\forms\FieldSelect;
use PKP\components\forms\FormComponent;

class RegisterForm extends FormComponent
{
    public $id = 'register';

    public $method = 'PUT';

    public function __construct($action, $imprints, $errors)
    {
        $this->action = $action;

        if (!empty($errors)) {
            $this->addPage([
                'id' => 'default',
            ])->addGroup([
                'id' => 'default',
                'pageId' => 'default',
            ]);

            $msg = '<div class="pkpNotification pkpNotification--warning">';
            $msg .= __('plugins.generic.thoth.register.warning');
            $msg .= '<ul>';
            foreach ($errors as $error) {
                $msg .= '<li>' . $error . '</li>';
            }
            $msg .= '</ul></div>';

            $this->addField(new \PKP\components\forms\FieldHTML('registerNotice', [
                'description' => $msg,
                'groupId' => 'default',
            ]));

            return;
        }

        $imprintOptions = [];
        foreach ($imprints as $imprint) {
            $imprintOptions[] = [
                'value' => $imprint['imprintId'],
                'label' => $imprint['imprintName']
            ];
        }

        $msg = __('plugins.generic.thoth.register.confirmation');
        $submitLabel = __('plugins.generic.thoth.register');
        $this->addPage([
            'id' => 'default',
            'submitButton' => [
                'label' => $submitLabel,
            ],
        ]);

        $this->addGroup([
            'id' => 'default',
            'pageId' => 'default',
        ])
            ->addField(new FieldHTML('validation', [
                'description' => $msg,
                'groupId' => 'default',
            ]))
            ->addField(new FieldSelect('imprint', [
                'label' => __('plugins.generic.thoth.imprint'),
                'options' => $imprintOptions,
                'required' => true,
                'groupId' => 'default',
                'value' => $imprints[0]['imprintId'] ?? null
            ]));
    }

    public function getOptions($list)
    {
        $options = [];
        foreach ($list as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }
        return $options;
    }
}
