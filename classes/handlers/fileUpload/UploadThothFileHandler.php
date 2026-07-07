<?php

/**
 * @file plugins/generic/thoth/classes/handlers/fileUpload/UploadThothFileHandler.php
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

namespace APP\plugins\generic\thoth\classes\handlers\fileUpload;

use APP\core\Application;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\plugins\generic\thoth\classes\facades\ThothRepository;
use APP\plugins\generic\thoth\classes\facades\ThothService;
use APP\plugins\generic\thoth\classes\factories\ThothPublicationFactory;
use APP\plugins\generic\thoth\classes\formatters\DoiFormatter;
use APP\plugins\generic\thoth\classes\handlers\fileUpload\form\UploadThothPublicationFileForm;
use APP\plugins\generic\thoth\classes\services\ThothCatalogFileService;
use APP\template\TemplateManager;
use Exception;
use PKP\core\JSONMessage;
use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\file\TemporaryFileManager;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\PKPSiteAccessPolicy;
use PKP\security\Role;

class UploadThothFileHandler extends Handler
{
    public $_isBackendPage = true;

    public $plugin;

    public function __construct()
    {
        parent::__construct();

        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT],
            [
                'uploadThothPublicationFile',
                'handleThothPublicationFile',
                'saveUploadThothPublicationFile',
                'viewThothPublicationFormatFiles',
            ]
        );

        $this->plugin = PluginRegistry::getPlugin('generic', 'thothplugin');
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function initialize($request, $args = null)
    {
        $this->setupTemplate($request);
        parent::initialize($request, $args);
    }

    public function uploadThothPublicationFile($args, $request)
    {
        $contextId = $request->getContext()->getId();
        $publicationId = (int) $request->getUserVar('publicationId');
        $representationId = (int) $request->getUserVar('representationId');
        $thothWorkId = $request->getUserVar('thothWorkId');

        $form = new UploadThothPublicationFileForm(
            $this->plugin->getTemplateResource('form/uploadThothPublicationFileForm.tpl'),
            $contextId,
            $publicationId,
            $representationId,
            $thothWorkId
        );
        $form->initData();
        $form->setData('missingCdnWritePermissionAlert', !$this->canUploadFiles());

        return new JSONMessage(true, $form->fetch($request));
    }

    public function handleThothPublicationFile($args, $request)
    {
        if (!$this->canUploadFiles()) {
            return new JSONMessage(false, __('plugins.generic.thoth.fileUpload.error.missingCdnWritePermission'));
        }

        $temporaryFileManager = new TemporaryFileManager();
        $user = $request->getUser();

        if ($temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId())) {
            $json = new JSONMessage(true);
            $json->setAdditionalAttributes([
                'temporaryFileId' => $temporaryFile->getId(),
            ]);
            return $json;
        }

        return new JSONMessage(false, __('manager.plugins.uploadError'));
    }

    public function saveUploadThothPublicationFile($args, $request)
    {
        if (!$request->checkCSRF()) {
            throw new Exception('CSRF mismatch!');
        }

        if (!$this->canUploadFiles()) {
            return new JSONMessage(false, __('plugins.generic.thoth.fileUpload.error.missingCdnWritePermission'));
        }

        $form = new UploadThothPublicationFileForm(
            $this->plugin->getTemplateResource('form/uploadThothPublicationFileForm.tpl'),
            $request->getContext()->getId(),
            (int) $request->getUserVar('publicationId'),
            (int) $request->getUserVar('representationId'),
            $request->getUserVar('thothWorkId')
        );
        $form->readInputData();

        if ($form->validate() && $form->execute()) {
            return DAO::getDataChangedEvent();
        }

        return new JSONMessage(false);
    }

    public function viewThothPublicationFormatFiles($args, $request)
    {
        $contextId = $request->getContext()->getId();
        $publicationId = (int) $request->getUserVar('publicationId');
        $representationId = (int) $request->getUserVar('representationId');

        $publication = Repo::publication()->get($publicationId);
        if (!$publication) {
            return new JSONMessage(false, __('plugins.generic.thoth.fileUpload.error.invalidPublication'));
        }

        $submission = Repo::submission()->get($publication->getData('submissionId'));
        if (!$submission || (int) $submission->getData('contextId') !== (int) $contextId) {
            return new JSONMessage(false, __('plugins.generic.thoth.fileUpload.error.publicationContextMismatch'));
        }

        $publicationFormat = DAORegistry::getDAO('PublicationFormatDAO')->getById(
            $representationId,
            $publicationId
        );
        if (!$publicationFormat) {
            return new JSONMessage(false, __('plugins.generic.thoth.fileUpload.error.invalidPublicationFormat'));
        }

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('thothFiles', $this->getThothFiles($submission, $publication, $publicationFormat));

        return new JSONMessage(
            true,
            $templateMgr->fetch($this->plugin->getTemplateResource('modal/thothPublicationFormatFiles.tpl'))
        );
    }

    private function getThothFiles($submission, $publication, $publicationFormat): array
    {
        $publicationType = $this->getPublicationType($publicationFormat);
        if (!$publicationType) {
            return [];
        }

        $catalogFileService = new ThothCatalogFileService(ThothRepository::publication());
        $thothFiles = [];

        $monographFile = $this->getFileByPublicationType(
            $catalogFileService->getFilesByWorkId($submission->getData('thothWorkId')),
            $publicationType
        );
        if ($monographFile) {
            $thothFiles[] = [
                'component' => __(
                    'plugins.generic.thoth.publicationFormat.thothFiles.component.publication',
                    ['title' => $publication->getLocalizedTitle()]
                ),
                'file' => $monographFile,
            ];
        }

        $chapters = DAORegistry::getDAO('ChapterDAO')->getByPublicationId($publication->getId())->toAssociativeArray();
        foreach ($chapters as $chapter) {
            $chapterFile = $this->getChapterFileByPublicationType($chapter, $catalogFileService, $publicationType);
            if ($chapterFile) {
                $thothFiles[] = [
                    'component' => __(
                        'plugins.generic.thoth.publicationFormat.thothFiles.component.chapter',
                        ['title' => $chapter->getLocalizedTitle()]
                    ),
                    'file' => $chapterFile,
                ];
            }
        }

        return $thothFiles;
    }

    private function getPublicationType($publicationFormat)
    {
        $factory = new ThothPublicationFactory();
        $submissionFile = $this->getSubmissionFile($publicationFormat);
        $thothPublication = $factory->createFromPublicationFormat($publicationFormat, $submissionFile);

        return $thothPublication->getPublicationType();
    }

    private function getSubmissionFile($publicationFormat)
    {
        try {
            $publication = Repo::publication()->get($publicationFormat->getData('publicationId'));
            if (!$publication) {
                return null;
            }

            $submissionFiles = Repo::submissionFile()
                ->getCollector()
                ->filterBySubmissionIds([$publication->getData('submissionId')])
                ->filterByAssoc(Application::ASSOC_TYPE_PUBLICATION_FORMAT)
                ->getMany();
        } catch (Exception $e) {
            return null;
        }

        foreach ($submissionFiles as $submissionFile) {
            if (
                $submissionFile->getData('assocId') == $publicationFormat->getId()
                && $submissionFile->getData('chapterId') == null
            ) {
                return $submissionFile;
            }
        }

        return null;
    }

    private function getChapterFileByPublicationType($chapter, $catalogFileService, $publicationType)
    {
        $doi = $chapter->getStoredPubId('doi');
        if (!$doi) {
            return null;
        }

        try {
            $thothChapter = ThothRepository::chapter()->getByDoi(DoiFormatter::resolveUrl($doi));
            if (!$thothChapter) {
                return null;
            }

            return $this->getFileByPublicationType(
                $catalogFileService->getFilesByWorkId($this->getThothWorkId($thothChapter)),
                $publicationType
            );
        } catch (Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    private function getFileByPublicationType($files, $publicationType)
    {
        foreach ($files as $file) {
            if (($file['publicationType'] ?? null) === $publicationType) {
                return $file;
            }
        }

        return null;
    }

    private function canUploadFiles(): bool
    {
        try {
            return ThothService::me()->hasCdnWritePermission();
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    private function getThothWorkId($thothWork)
    {
        return is_object($thothWork) ? $thothWork->getWorkId() : $thothWork;
    }
}
