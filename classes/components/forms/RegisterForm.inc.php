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
 * @ingroup plugins_generic_thoth
 *
 * @brief A preset form for confirming a publication's issue before publishing.
 *   It may also be used for scheduling a publication in an issue for later
 *   publication.
 */

use PKP\components\forms\FormComponent;
use PKP\components\forms\FieldHTML;

class RegisterForm extends FormComponent
{
    public $id = 'register';

    public $method = 'PUT';

    public function __construct($action)
    {
        $this->action = $action;

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
            ]));
    }
}
