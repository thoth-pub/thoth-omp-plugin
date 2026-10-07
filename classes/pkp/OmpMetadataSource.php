<?php

/**
 * @file plugins/generic/thoth/classes/pkp/OmpMetadataSource.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OmpMetadataSource
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Provides OMP metadata for Thoth books, chapters and locations
 */

namespace APP\plugins\generic\thoth\classes\pkp;

use APP\core\Application;
use APP\core\Request;
use APP\monograph\Chapter;
use APP\press\PressDAO;
use APP\publication\Publication;
use APP\publication\Repository as PublicationRepository;
use APP\publicationFormat\PublicationFormat;
use APP\publicationFormat\PublicationFormatDAO;
use APP\submission\Repository as SubmissionRepository;
use APP\submission\Submission;
use PKP\submission\PKPSubmission;
use UnexpectedValueException;

class OmpMetadataSource
{
    public function __construct(
        private SubmissionRepository $submissions,
        private PublicationRepository $publications,
        private PressDAO $contexts,
        private PublicationFormatDAO $formats,
        private Request $request
    ) {
    }

    public function getBookContext(Publication $publication): array
    {
        $submission = $this->getSubmission($publication);
        $license = $publication->getData('licenseUrl');
        if ($license === null || $license === '') {
            $license = $submission->_getContextLicenseFieldValue(
                null,
                PKPSubmission::PERMISSIONS_FIELD_LICENSE_URL,
                $publication
            );
        }
        $copyrightHolder = $publication->getLocalizedData('copyrightHolder');
        if ($copyrightHolder === null || $copyrightHolder === '') {
            $copyrightHolder = $submission->_getContextLicenseFieldValue(
                $submission->getData('locale'),
                PKPSubmission::PERMISSIONS_FIELD_COPYRIGHT_HOLDER,
                $publication
            );
        }
        $coverUrl = $publication->getData('thothUploadFrontcover')
            ? $publication->getData('thothFrontcoverUrl') : null;

        return [
            'submissionWorkType' => $submission->getData('workType'),
            'landingPage' => $this->getCatalogUrl($submission),
            'license' => $license,
            'copyrightHolder' => $copyrightHolder,
            'coverUrl' => $coverUrl ?: $publication->getLocalizedCoverImageUrl($submission->getData('contextId')),
            'fallbackDoi' => $publication->getData('doiObject') ? null : $this->getFormatDoi($publication),
        ];
    }

    public function getChapterContext(Chapter $chapter): array
    {
        $publication = $this->getPublication((int) $chapter->getData('publicationId'));
        return [
            'publication' => $publication,
            'landingPage' => $this->getCatalogUrl($this->getSubmission($publication)),
        ];
    }

    public function getLocationContext(PublicationFormat $format, ?int $fileId = null): array
    {
        $publication = $this->getPublication((int) $format->getData('publicationId'));
        $submission = $this->getSubmission($publication);
        return [
            'landingPage' => $this->getCatalogUrl($submission),
            'fullTextUrl' => $fileId
                ? $this->getCatalogUrl($submission, 'view', [$format->getBestId(), $fileId])
                : $format->getData('urlRemote'),
        ];
    }

    public function getPublication(int $publicationId): Publication
    {
        $publication = $this->publications->get($publicationId);
        if (!$publication) {
            throw new UnexpectedValueException('Missing OMP publication ' . $publicationId);
        }
        return $publication;
    }

    private function getSubmission(Publication $publication): Submission
    {
        $submissionId = (int) $publication->getData('submissionId');
        $submission = $this->submissions->get($submissionId);
        if (!$submission) {
            throw new UnexpectedValueException('Missing OMP submission ' . $submissionId);
        }
        return $submission;
    }

    private function getCatalogUrl(Submission $submission, string $operation = 'book', array $path = []): string
    {
        $contextId = (int) $submission->getData('contextId');
        $context = $this->contexts->getById($contextId);
        if (!$context) {
            throw new UnexpectedValueException('Missing OMP context ' . $contextId);
        }
        return $this->request->getDispatcher()->url(
            $this->request,
            Application::ROUTE_PAGE,
            $context->getPath(),
            'catalog',
            $operation,
            [$submission->getBestId(), ...$path]
        );
    }

    private function getFormatDoi(Publication $publication): ?string
    {
        foreach ($this->formats->getByPublicationId($publication->getId()) as $format) {
            foreach ($format->getIdentificationCodes()->toArray() as $code) {
                if ($code->getCode() === '06') {
                    return $code->getValue();
                }
            }
        }
        return null;
    }
}
