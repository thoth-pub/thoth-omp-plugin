<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothBookFactory.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookFactory
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth books
 */

use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Enums\WorkType;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

import('classes.submission.Submission');
import('plugins.generic.thoth.classes.formatters.DoiFormatter');
class ThothBookFactory
{
    public function createFromPublication($publication)
    {
        $request = Application::get()->getRequest();
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById($publication->getData('submissionId'));
        $context = Application::getContextDAO()->getById($submission->getData('contextId'));
        $thothWorkType = $request->getUserVar('thothWorkType');

        $workData = [
            'workType' => $thothWorkType ?? $this->getWorkTypeBySubmissionWorkType($submission->getData('workType')),
            'workStatus' => $this->getWorkStatusByDatePublished($publication->getData('datePublished')),
            'edition' => $publication->getData('version'),
            'publicationDate' => $publication->getData('datePublished'),
            'pageCount' => $publication->getData('pageCount'),
            'imageCount' => $publication->getData('imageCount'),
            'landingPage' => $request->getDispatcher()->url(
                $request,
                ROUTE_PAGE,
                $context->getPath(),
                'catalog',
                'book',
                $submission->getBestId()
            ),
        ];

        $license = $publication->getData('licenseUrl');
        if ($license === null || $license === '') {
            $license = $submission->_getContextLicenseFieldValue(
                null,
                PERMISSIONS_FIELD_LICENSE_URL,
                $publication
            );
        }

        $copyrightHolder = $publication->getLocalizedData('copyrightHolder');
        if ($copyrightHolder === null || $copyrightHolder === '') {
            $copyrightHolder = $submission->_getContextLicenseFieldValue(
                $submission->getData('locale'),
                PERMISSIONS_FIELD_COPYRIGHT_HOLDER,
                $publication
            );
        }

        $optionalData = [
            'doi' => $this->getDoi($publication),
            'place' => $publication->getData('place'),
            'license' => $license,
            'copyrightHolder' => $copyrightHolder,
            'coverUrl' => $publication->getLocalizedCoverImageUrl($submission->getContextId()),
        ];
        foreach ($optionalData as $fieldName => $fieldValue) {
            if ($fieldValue !== null && $fieldValue !== '') {
                $workData[$fieldName] = $fieldValue;
            }
        }

        return new ThothWork($workData);
    }

    public function getWorkTypeBySubmissionWorkType($submissionWorkType)
    {
        $workTypeMapping = [
            WORK_TYPE_EDITED_VOLUME => WorkType::EDITED_BOOK,
            WORK_TYPE_AUTHORED_WORK => WorkType::MONOGRAPH
        ];

        return $workTypeMapping[$submissionWorkType] ?? WorkType::MONOGRAPH;
    }

    public function getWorkStatusByDatePublished($datePublished)
    {
        if ($datePublished && $datePublished <= \Core::getCurrentDate()) {
            return WorkStatus::ACTIVE;
        }

        return WorkStatus::FORTHCOMING;
    }

    public function getDoi($publication)
    {
        $doi = $publication->getStoredPubId('doi');

        if ($doi === null) {
            $publicationFormats = DAORegistry::getDAO('PublicationFormatDAO')
                ->getByPublicationId($publication->getId())
                ->toArray();

            foreach ($publicationFormats as $publicationFormat) {
                $identificationCodes = $publicationFormat->getIdentificationCodes()->toArray();
                foreach ($identificationCodes as $identificationCode) {
                    if ($identificationCode->getCode() == '06') {
                        $doi = $identificationCode->getValue();
                        if (str_contains($doi, 'doi.org')) {
                            $doi = str_replace('https://doi.org/', '', $doi);
                        }
                        break 2;
                    }
                }
            }
        }

        if ($doi === null) {
            return $doi;
        }

        return DoiFormatter::resolveUrl($doi);
    }
}
