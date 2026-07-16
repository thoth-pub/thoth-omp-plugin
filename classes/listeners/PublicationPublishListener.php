<?php

/**
 * @file plugins/generic/thoth/classes/listeners/PublicationPublishListener.inc.php
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

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\thoth\classes\facades\ThothService;
use APP\plugins\generic\thoth\classes\notification\ThothNotification;
use ThothApi\Exception\QueryException;

class PublicationPublishListener
{
    public function validate($hookName, $args)
    {
        $errors = & $args[0];
        $request = Application::get()->getRequest();

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
        $request = Application::get()->getRequest();

        if ($submission->getData('thothWorkId')) {
            return false;
        }

        $confirmation = $request->getUserVar('registerConfirmation');
        if (!$confirmation || $confirmation == 'false') {
            return false;
        }

        $thothImprintId = $request->getUserVar('thothImprintId');
        $thothNotification = new ThothNotification();
        $registrationResult = null;
        try {
            $thothBookRegistrationService = ThothService::bookRegistration();
            $registrationResult = $thothBookRegistrationService->register($publication, $thothImprintId);
            $thothBookRegistrationService->setActive($registrationResult);
            $thothBookId = $registrationResult->getWorkId();
            Repo::submission()->edit($submission, ['thothWorkId' => $thothBookId]);
            $thothNotification->notifySuccess($request, $submission);
            if ($warning = $registrationResult->getWarning()) {
                $thothNotification->notifyWarning($request, $submission, $warning);
            }
        } catch (QueryException $e) {
            if ($registrationResult !== null) {
                $thothBookRegistrationService->deleteRegisteredEntry($registrationResult);
            }
            $thothNotification->notifyError($request, $submission, $e);
            if ($registrationResult && $warning = $registrationResult->getWarning()) {
                $thothNotification->notifyWarning($request, $submission, $warning);
            }
        }

        return false;
    }
}
