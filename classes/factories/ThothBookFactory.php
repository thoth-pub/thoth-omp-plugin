<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothBookFactory.inc.php
*
* Copyright (c) 2024-2026 Lepidus Tecnologia
* Copyright (c) 2024-2026 Thoth
* Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
*
* @class ThothBookFactory
*
* @ingroup plugins_generic_thoth
*
* @brief A factory to create Thoth books
*/

namespace APP\plugins\generic\thoth\classes\factories;

use APP\core\Application;
use APP\facades\Repo;
use APP\submission\Submission;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\doi\Doi;
use PKP\submission\PKPSubmission;
use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Enums\WorkType;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

class ThothBookFactory
{
    public function createFromPublication($publication)
    {
        $request = Application::get()->getRequest();
        $submission = Repo::submission()->get($publication->getData('submissionId'));
        $context = Application::getContextDAO()->getById($submission->getData('contextId'));
        $thothWorkType = $request->getUserVar('thothWorkType');

        return new ThothWork([
            'workType' => $thothWorkType ?? $this->getWorkTypeBySubmissionWorkType($submission->getData('workType')),
            'workStatus' => $this->getWorkStatusByDatePublished($publication->getData('datePublished')),
            'edition' => $publication->getData('version'),
            'doi' => $this->getDoi($publication),
            'publicationDate' => $publication->getData('datePublished'),
            'place' => $publication->getData('place'),
            'pageCount' => $publication->getData('pageCount'),
            'imageCount' => $publication->getData('imageCount'),
            'license' => $publication->getData('licenseUrl')
                ?? $submission->_getContextLicenseFieldValue(
                    null,
                    PKPSubmission::PERMISSIONS_FIELD_LICENSE_URL,
                    $publication
                ),
            'copyrightHolder' => $publication->getLocalizedData('copyrightHolder')
                ?? $submission->_getContextLicenseFieldValue(
                    $submission->getData('locale'),
                    PKPSubmission::PERMISSIONS_FIELD_COPYRIGHT_HOLDER,
                    $publication
                ),
            'coverUrl' => $this->getCoverUrl($publication, $submission->getData('contextId')),
            'landingPage' => $request->getDispatcher()->url(
                $request,
                ROUTE_PAGE,
                $context->getPath(),
                'catalog',
                'book',
                [$submission->getBestId()]
            )
        ]);
    }

    private function getCoverUrl($publication, int $contextId): ?string
    {
        if (
            $publication->getData('thothUploadFrontcover')
            && $frontcoverUrl = $publication->getData('thothFrontcoverUrl')
        ) {
            return $frontcoverUrl;
        }

        return $publication->getLocalizedCoverImageUrl($contextId);
    }

    public function getWorkTypeBySubmissionWorkType($submissionWorkType)
    {
        $workTypeMapping = [
            Submission::WORK_TYPE_EDITED_VOLUME => WorkType::EDITED_BOOK,
            Submission::WORK_TYPE_AUTHORED_WORK => WorkType::MONOGRAPH
        ];

        return $workTypeMapping[$submissionWorkType] ?? WorkType::MONOGRAPH;
    }

    public function getWorkStatusByDatePublished($datePublished)
    {
        if ($datePublished && $datePublished <= Core::getCurrentDate()) {
            return WorkStatus::ACTIVE;
        }

        return WorkStatus::FORTHCOMING;
    }

    public function getDoi($publication)
    {
        $doiObject = $publication->getData('doiObject');

        if ($doiObject === null) {
            $publicationFormats = DAORegistry::getDAO('PublicationFormatDAO')
                ->getByPublicationId($publication->getId());
            foreach ($publicationFormats as $publicationFormat) {
                $identificationCodes = $publicationFormat->getIdentificationCodes()->toArray();
                foreach ($identificationCodes as $identificationCode) {
                    if ($identificationCode->getCode() == '06') {
                        $doi = $identificationCode->getValue();
                        if (str_contains($doi, 'doi.org')) {
                            $doi = str_replace('https://doi.org/', '', $doi);
                        }
                        $doiObject = new Doi();
                        $doiObject->setDoi($doi);
                        break 2;
                    }
                }
            }
        }

        if ($doiObject === null) {
            return $doiObject;
        }

        return $doiObject->getResolvingUrl();
    }
}
