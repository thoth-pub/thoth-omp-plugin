<?php

/**
 * @file plugins/generic/thoth/classes/components/forms/FeatureVideoForm.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FeatureVideoForm
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Form used to upload a featured video for a Thoth work.
 */

namespace APP\plugins\generic\thoth\classes\components\forms;

use PKP\components\forms\FieldText;
use PKP\components\forms\FieldUpload;
use PKP\components\forms\FieldHTML;
use PKP\components\forms\FormComponent;

class FeatureVideoForm extends FormComponent
{
    public const FORM_FEATURE_VIDEO = 'featureVideo';

    public function __construct(string $action, string $temporaryFilesUrl, bool $canUpload = true)
    {
        parent::__construct(self::FORM_FEATURE_VIDEO, 'POST', $action, []);

        if (!$canUpload) {
            $this->addField(new FieldHTML('permissionNotice', [
                'description' => '<div class="pkpNotification pkpNotification--warning">'
                    . __('plugins.generic.thoth.fileUpload.error.missingCdnWritePermission')
                    . '</div>',
            ]));
            return;
        }

        $this->addField(new FieldText('title', [
            'label' => __('common.title'),
            'isRequired' => true,
        ]))->addField(new FieldUpload('video', [
            'label' => __('plugins.generic.thoth.featureVideo.file'),
            'isRequired' => true,
            'options' => [
                'url' => $temporaryFilesUrl,
                'acceptedFiles' => '.mp4,.webm,.mov',
                'maxFiles' => 1,
            ],
        ]));
    }
}
