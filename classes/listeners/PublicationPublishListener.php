<?php

/**
 * @file plugins/generic/thoth/classes/listeners/PublicationPublishListener.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationPublishListener
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Trigger actions on publication publish event
 */

namespace APP\plugins\generic\thoth\classes\listeners;

use APP\core\Request;
use APP\plugins\generic\thoth\classes\notification\ThothNotification;

class PublicationPublishListener
{
    public function __construct(
        private \Closure $registrationService,
        private ThothNotification $notification,
        private Request $request
    ) {
    }

    public function validate($hookName, $args)
    {
        $errors = & $args[0];
        $request = $this->request;

        $confirmation = $request->getUserVar('registerConfirmation');
        if (!$confirmation || $confirmation == 'false') {
            return;
        }

        $thothImprintId = $request->getUserVar('thothImprintId');
        if (empty($thothImprintId)) {
            $errors['thothImprintId'] = [__('plugins.generic.thoth.imprint.required')];
        }
    }

    public function registerThothBook($hookName, $args)
    {
        $publication = $args[0];
        $submission = $args[2];
        $request = $this->request;

        if ($submission->getData('thothWorkId')) {
            return false;
        }

        $confirmation = $request->getUserVar('registerConfirmation');
        if (!$confirmation || $confirmation == 'false') {
            return false;
        }

        $thothImprintId = $request->getUserVar('thothImprintId');
        try {
            $registrationResult = ($this->registrationService)()->register(
                $publication,
                $thothImprintId,
                $submission,
                $request->getUserVar('thothWorkType')
            );
        } catch (\Throwable $e) {
            $this->notification->notifyError($request, $submission, $e);
            return false;
        }

        $this->notification->notifySuccess($request, $submission);
        foreach ($registrationResult->getWarnings() as $warning) {
            $this->notification->notifyWarning($request, $submission, $warning);
        }

        return false;
    }
}
