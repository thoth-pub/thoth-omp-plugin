<?php

/**
 * @file controllers/fileUpload/form/UploadThothPublicationFileForm.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UploadThothPublicationFileForm
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Form for uploading publication files to Thoth.
 */

import('lib.pkp.classes.form.Form');
import('lib.pkp.classes.plugins.PKPPubIdPluginDAO');
import('plugins.generic.thoth.classes.services.ThothCatalogFilesCacheService');

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

        $publication = Services::get('publication')->get($this->publicationId);
        if (!$publication) {
            return;
        }

        $chapters = DAORegistry::getDAO('ChapterDAO')->getByPublicationId($this->publicationId)->toAssociativeArray();
        $chaptersWithDoi = $this->filterComponentsWithDoi($chapters);

        if (!empty($chapters)) {
            $componentOptions = array_map([$this, 'getChapterOption'], $chaptersWithDoi);
            if ($this->hasDoi($publication)) {
                array_unshift($componentOptions, $this->getPublicationOption($publication));
            }

            $this->_data = [
                'submissionComponents' => $componentOptions,
                'missingDoiAlert' => empty($componentOptions),
            ];

            return;
        }

        $this->_data = [
            'missingDoiAlert' => !$this->hasDoi($publication),
        ];
    }

    private function filterComponentsWithDoi($components)
    {
        return array_filter($components, [$this, 'hasDoi']);
    }

    private function hasDoi($submissionComponent)
    {
        return !empty($submissionComponent->getStoredPubId('doi'));
    }

    private function getPublicationOption($publication)
    {
        return $this->getSubmissionComponentOption(
            $publication,
            'plugins.generic.thoth.publicationFormat.thothFiles.component.publication'
        );
    }

    private function getChapterOption($chapter)
    {
        return $this->getSubmissionComponentOption(
            $chapter,
            'plugins.generic.thoth.publicationFormat.thothFiles.component.chapter'
        );
    }

    private function getSubmissionComponentOption($submissionComponent, $translationKey)
    {
        return [
            'id' => $submissionComponent->getId(),
            'label' => __($translationKey, ['title' => $submissionComponent->getLocalizedTitle()]),
        ];
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
        $thothChapterId = null;

        try {
            if ($submissionComponentId && $submissionComponentId !== $this->publicationId) {
                $chapter = DAORegistry::getDAO('ChapterDAO')->getChapter($submissionComponentId, $this->publicationId);
                if (!$chapter) {
                    throw new Exception(__('plugins.generic.thoth.fileUpload.error.invalidSubmissionComponent'));
                }

                $thothChapterId = ThothRepo::chapter()->getByDoi(
                    DoiFormatter::resolveUrl($chapter->getStoredPubId('doi'))
                );
                $thothWorkId = $thothChapterId;
            }

            $publicationFormat = DAORegistry::getDAO('PublicationFormatDAO')->getById(
                $this->representationId,
                $this->publicationId
            );
            if (!$publicationFormat) {
                throw new Exception(__('plugins.generic.thoth.fileUpload.error.invalidPublicationFormat'));
            }

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
            $this->flushCatalogFilesCache();

            $notificationMgr->createTrivialNotification(
                $user->getId(),
                NOTIFICATION_TYPE_SUCCESS,
                ['contents' => __('plugins.generic.thoth.fileUpload.success')]
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

    private function flushCatalogFilesCache()
    {
        $cacheService = new ThothCatalogFilesCacheService();
        $cacheService->flush($this->publicationId);
    }

    public function validate($callHooks = true)
    {
        $publication = Services::get('publication')->get($this->publicationId);
        if (!$publication) {
            $this->addError('publicationId', __('plugins.generic.thoth.fileUpload.error.invalidPublication'));
            return parent::validate($callHooks);
        }

        $submission = Services::get('submission')->get($publication->getData('submissionId'));
        if (!$submission || (int) $submission->getData('contextId') !== (int) $this->contextId) {
            $this->addError('publicationId', __('plugins.generic.thoth.fileUpload.error.publicationContextMismatch'));
        }

        if ($submission && $submission->getData('thothWorkId') !== $this->thothWorkId) {
            $this->addError('thothWorkId', __('plugins.generic.thoth.fileUpload.error.thothWorkMismatch'));
        }

        $publicationFormat = DAORegistry::getDAO('PublicationFormatDAO')->getById(
            $this->representationId,
            $this->publicationId
        );
        if (!$publicationFormat) {
            $this->addError('representationId', __('plugins.generic.thoth.fileUpload.error.invalidPublicationFormat'));
        }

        $submissionComponentId = (int) $this->getData('submissionComponentId');
        if ($submissionComponentId && $submissionComponentId !== $this->publicationId) {
            $chapter = DAORegistry::getDAO('ChapterDAO')->getChapter($submissionComponentId, $this->publicationId);
            if (!$chapter) {
                $this->addError('submissionComponentId', __('plugins.generic.thoth.fileUpload.error.invalidSubmissionComponent'));
                return parent::validate($callHooks);
            }

            $chapterDoi = DoiFormatter::resolveUrl($chapter->getStoredPubId('doi'));
            try {
                $thothChapter = ThothRepo::chapter()->getByDoi($chapterDoi);
                if (is_null($thothChapter)) {
                    $this->addError(
                        'submissionComponentId',
                        __('plugins.generic.thoth.fileUpload.error.chapterNotFoundInThoth', ['doi' => $chapterDoi])
                    );
                }
            } catch (Exception $e) {
                $this->addError('submissionComponentId', __('plugins.generic.thoth.fileUpload.error.chapterLookupFailed'));
            }
        }

        return parent::validate($callHooks);
    }
}
