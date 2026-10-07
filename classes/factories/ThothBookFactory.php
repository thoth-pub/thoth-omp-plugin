<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothBookFactory.php
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

use APP\submission\Submission;
use PKP\core\Core;
use PKP\doi\Doi;
use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Enums\WorkType;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

class ThothBookFactory
{
    public function createFromPublication($publication, array $context, ?string $workType = null): ThothWork
    {
        $workData = [
            'workType' => $workType ?? $this->getWorkTypeBySubmissionWorkType($context['submissionWorkType']),
            'workStatus' => $this->getWorkStatusByDatePublished($publication->getData('datePublished')),
            'edition' => $publication->getData('version'),
            'publicationDate' => $publication->getData('datePublished'),
            'pageCount' => $publication->getData('pageCount'),
            'imageCount' => $publication->getData('imageCount'),
            'landingPage' => $context['landingPage'],
        ];

        $optionalData = [
            'doi' => $this->getDoi($publication, $context['fallbackDoi']),
            'place' => $publication->getData('place'),
            'license' => $context['license'],
            'copyrightHolder' => $context['copyrightHolder'],
            'coverUrl' => $context['coverUrl'],
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

    public function getDoi($publication, ?string $fallbackDoi = null): ?string
    {
        $doiObject = $publication->getData('doiObject');
        if ($doiObject === null && $fallbackDoi !== null) {
            $doiObject = new Doi();
            $doiObject->setDoi(str_replace('https://doi.org/', '', $fallbackDoi));
        }
        return $doiObject?->getResolvingUrl();
    }
}
