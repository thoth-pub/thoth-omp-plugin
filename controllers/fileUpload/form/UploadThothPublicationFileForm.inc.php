<?php

/**
 * @file controllers/fileUpload/form/UploadThothPublicationFileForm.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UploadThothPublicationFileForm
 * @ingroup plugins_generic_thoth
 *
 * @brief Form for uploading publication files to Thoth.
 */

import('lib.pkp.classes.form.Form');
import('lib.pkp.classes.plugins.PKPPubIdPluginDAO');

class UploadThothPublicationFileForm extends Form
{
    public $contextId;

    public $publicationId;

    public $representationId;

    public $thothWorkId;

    public function __construct($template, $contextId, $publicationId, $representationId, $thothWorkId)
    {
        parent::__construct($template);

        $this->contextId = $contextId;
        $this->publicationId = $publicationId;
        $this->representationId = $representationId;
        $this->thothWorkId = $thothWorkId;

        $this->addCheck(new FormValidator($this, 'temporaryFileId', 'required', 'form.fileRequired'));
    }

    public function initData()
    {
        AppLocale::requireComponents(
            LOCALE_COMPONENT_APP_COMMON,
            LOCALE_COMPONENT_PKP_SUBMISSION,
            LOCALE_COMPONENT_APP_SUBMISSION
        );

        $chapters = DAORegistry::getDAO('ChapterDAO')->getByPublicationId($this->publicationId)->toAssociativeArray();
        $chapters = array_filter($chapters, function ($chapter) {
            return !empty($chapter->getStoredPubId('doi'));
        });

        if (!empty($chapters)) {
            $publication = Services::get('publication')->get($this->publicationId);
            $this->_data = [
                'publication' => $publication,
                'chapters' => $chapters
            ];
        }
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('publicationId', $this->publicationId);
        $templateMgr->assign('representationId', $this->representationId);
        $templateMgr->assign('thothWorkId', $this->thothWorkId);

        return parent::fetch($request, $template, $display);
    }

    public function readInputData()
    {
        parent::readInputData();
        $this->readUserVars(['temporaryFileId', 'submissionComponentId']);
    }

    public function execute(...$functionParams)
    {
        parent::execute(...$functionParams);

        $request = Application::get()->getRequest();
        $user = $request->getUser();
        $notificationMgr = new NotificationManager();

        import('lib.pkp.classes.file.TemporaryFileManager');
        $temporaryFileManager = new TemporaryFileManager();
        $temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
        $temporaryFile = $temporaryFileDao->getTemporaryFile($this->getData('temporaryFileId'), $user->getId());

        $extension = pathinfo($temporaryFile->getOriginalFileName(), PATHINFO_EXTENSION);
        $mimeType = mime_content_type($temporaryFile->getfilePath());
        $sha256 = hash_file('sha256', $temporaryFile->getfilePath());

        $submissionComponentId = (int) $this->getData('submissionComponentId');
        $thothWorkId = $this->thothWorkId;

        try {
            if ($submissionComponentId && $submissionComponentId !== $this->publicationId) {
                $chapter = DAORegistry::getDAO('ChapterDAO')->getChapter($submissionComponentId);
                $thothChapterId = ThothRepo::chapter()->getByDoi(
                    DoiFormatter::resolveUrl($chapter->getStoredPubId('doi'))
                );
                $thothWorkId = $thothChapterId;
            }

            $publicationFormat = DAORegistry::getDAO('PublicationFormatDAO')->getById($this->representationId);
            $thothPublicationFactory = new ThothPublicationFactory();
            $newThothPublication = $thothPublicationFactory->createFromPublicationFormat($publicationFormat);

            $thothPublicationId = ThothRepo::publication()->getIdByType(
                $thothWorkId,
                $newThothPublication->getPublicationType()
            );

            if (is_null($thothPublicationId)) {
                $newThothPublication->setWorkId($thothWorkId);
                if ($thothChapterId) {
                    $newThothPublication->unsetIsbn();
                }
                $thothPublicationId = ThothRepo::publication()->add($newThothPublication);
            }

            $newPublicationFileUpload = ThothRepo::publicationFileUpload()->new();
            $newPublicationFileUpload->setPublicationId($thothPublicationId)
                ->setDeclaredExtension($extension)
                ->setDeclaredMimeType($mimeType)
                ->setDeclaredSha256($sha256);

            $fileUploadResponse = ThothRepo::publicationFileUpload()->init($newPublicationFileUpload);

            $httpClient = Application::get()->getHttpClient();
            $headers = array_reduce($fileUploadResponse->getUploadHeaders(), function ($headers, $uploadHeader) {
                $headers[$uploadHeader->getName()] = $uploadHeader->getValue();
                return $headers;
            }, []);
            $resource = fopen($temporaryFile->getfilePath(), 'r');

            $httpClient->request('PUT', $fileUploadResponse->getUploadUrl(), [
                'headers' => $headers,
                'body' => $resource
            ]);

            $file = ThothRepo::publicationFileUpload()->complete($fileUploadResponse->getFileUploadId());

            $notificationMgr->createTrivialNotification(
                $user->getId(),
                NOTIFICATION_TYPE_SUCCESS,
                array('contents' => __('plugins.generic.thoth.fileUpload.success'))
            );
        } catch (Exception $e) {
            $notificationMgr->createTrivialNotification(
                $user->getId(),
                NOTIFICATION_TYPE_ERROR,
                ['contents' => $e->getMessage()]
            );
        } finally {
            $temporaryFileManager->deleteById($temporaryFile->getId(), $user->getId());
        }

        return true;
    }

    public function validate($callHooks = true)
    {
        $submissionComponentId = (int) $this->getData('submissionComponentId');
        if ($submissionComponentId && $submissionComponentId !== $this->publicationId) {
            $chapter = DAORegistry::getDAO('ChapterDAO')->getChapter($submissionComponentId);
            $chapterDoi = DoiFormatter::resolveUrl($chapter->getStoredPubId('doi'));
            try {
                $thothChapter = ThothRepo::chapter()->getByDoi($chapterDoi);
                if (is_null($thothChapter)) {
                    $this->addError(
                        'submissionComponentId',
                        __('plugins.generic.thoth.validation.doiNotFound', ['doi' => $chapterDoi])
                    );
                }
            } catch (Exception $e) {
                $this->addError('submissionComponentId', __('plugins.generic.thoth.connectionError'));
            }
        }

        return parent::validate($callHooks);
    }
}
