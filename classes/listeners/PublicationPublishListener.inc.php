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

        $imprint = $request->getUserVar('imprint');
        if (empty($imprint)) {
            $errors['imprint'] = [__('plugins.generic.thoth.imprint.required')];
        }
    }

    public function registerThothBook($hookName, $args)
    {
        $submission = $args[2];
        $request = Application::get()->getRequest();

        if ($submission->getData('thothWorkId')) {
            return false;
        }

        $confirmation = $request->getUserVar('registerConfirmation');
        if (!$confirmation || $confirmation == 'false') {
            return false;
        }

        $imprint = $request->getUserVar('imprint');

        $thothNotification = new ThothNotification();
        try {
            $thothBookId = ThothService::book()->register($submission, $imprint);
            $submission = Services::get('submission')->edit($submission, ['thothWorkId' => $thothBookId], $request);
            $thothNotification->notifySuccess($request, $submission);
        } catch (QueryException $e) {
            $thothNotification->notifyError($request, $submission, $e->getMessage());
        }

        return false;
    }
}
