<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothBookFactory.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookFactory
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth books
 */

use APP\facades\Repo;
use APP\submission\Submission;
use PKP\core\Core;
use PKP\doi\Doi;
use PKP\submission\PKPSubmission;
use ThothApi\GraphQL\Models\Work as ThothWork;

import('plugins.generic.thoth.classes.formatters.HtmlStripper');

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
            'fullTitle' => $publication->getLocalizedFullTitle(),
            'title' => $publication->getLocalizedTitle(),
            'subtitle' => $publication->getLocalizedData('subtitle'),
            'longAbstract' => HtmlStripper::stripTags($publication->getLocalizedData('abstract')),
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
            'coverUrl' => $publication->getLocalizedCoverImageUrl($submission->getData('contextId')),
            'landingPage' => $request->getDispatcher()->url(
                $request,
                ROUTE_PAGE,
                $context->getPath(),
                'catalog',
                'book',
                $submission->getBestId()
            )
        ]);
    }

    public function getWorkTypeBySubmissionWorkType($submissionWorkType)
    {
        $workTypeMapping = [
            Submission::WORK_TYPE_EDITED_VOLUME => ThothWork::WORK_TYPE_EDITED_BOOK,
            Submission::WORK_TYPE_AUTHORED_WORK => ThothWork::WORK_TYPE_MONOGRAPH
        ];

        return $workTypeMapping[$submissionWorkType];
    }

    public function getWorkStatusByDatePublished($datePublished)
    {
        if ($datePublished && $datePublished <= Core::getCurrentDate()) {
            return ThothWork::WORK_STATUS_ACTIVE;
        }

        return ThothWork::WORK_STATUS_FORTHCOMING;
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
