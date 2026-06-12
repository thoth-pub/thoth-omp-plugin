<?php

/**
 * @file controllers/fileUpload/UploadThothFileHandler.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UploadThothFileHandler
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Handler for uploading Thoth files.
 */

import('classes.handler.Handler');

class UploadThothFileHandler extends Handler
{
    public $_isBackendPage = true;

    public $plugin;

    public function __construct()
    {
        parent::__construct();

        $this->addRoleAssignment(
            [ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER],
            ['uploadThothPublicationFile', 'handleThothPublicationFile', 'saveUploadThothPublicationFile']
        );

        $this->plugin = PluginRegistry::getPlugin('generic', 'thothplugin');
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
        $this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function initialize($request)
    {
        $this->setupTemplate($request);
        parent::initialize($request);
    }

    public function uploadThothPublicationFile($args, $request)
    {
        $context = $request->getContext();
        $publicationId = (int) $request->getUserVar('publicationId');
        $representationId = (int) $request->getUserVar('representationId');

        import('plugins.generic.thoth.controllers.fileUpload.form.UploadThothPublicationFileForm');
        $template = $this->plugin->getTemplateResource('form/uploadThothPublicationFileForm.tpl');
        $form = new UploadThothPublicationFileForm($template, $context->getId(), $publicationId, $representationId);
        $form->initData();

        return new JSONMessage(true, $form->fetch($request));
    }

    public function handleThothPublicationFile($args, $request)
    {
        import('lib.pkp.classes.file.TemporaryFileManager');
        $temporaryFileManager = new TemporaryFileManager();
        $user = $request->getUser();

        if ($temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId())) {
            $json = new JSONMessage(true);
            $json->setAdditionalAttributes([
                'temporaryFileId' => $temporaryFile->getId()
            ]);
            return $json;
        } else {
            return new JSONMessage(false, __('manager.plugins.uploadError'));
        }
    }

    public function saveUploadThothPublicationFile($args, $request)
    {
        return new JSONMessage(true);
    }
}
