<?php

/**
 * @file plugins/generic/thoth/classes/listeners/PublicationPublishListener.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationPublishListener
 * @ingroup plugins_generic_thoth
 *
 * @brief Trigger actions on publication publish event
 */

use ThothApi\Exception\QueryException;

import('plugins.generic.thoth.classes.facades.ThothService');

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
        try {
            $thothBookService = ThothService::book();
            $thothBookId = $thothBookService->register($publication, $thothImprintId);
            $thothBookService->setActive();
            $submission = Services::get('submission')->edit($submission, ['thothWorkId' => $thothBookId], $request);
            $thothNotification->notifySuccess($request, $submission);
        } catch (QueryException $e) {
            $thothBookService->deleteRegisteredEntry();
            $thothNotification->notifyError($request, $submission, $e->getMessage());
        }

        return false;
    }
}
