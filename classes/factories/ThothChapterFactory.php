<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothChapterFactory.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothChapterFactory
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth chapters
 */

namespace APP\plugins\generic\thoth\classes\factories;

use PKP\core\Core;
use ThothApi\GraphQL\Enums\WorkStatus;
use ThothApi\GraphQL\Enums\WorkType;
use ThothApi\GraphQL\Inputs\PatchWork as ThothWork;

class ThothChapterFactory
{
    public function createFromChapter($chapter, array $context): ThothWork
    {
        $publication = $context['publication'];
        $pages = $this->extractPages($chapter);

        $workData = [
            'workType' => WorkType::BOOK_CHAPTER,
            'workStatus' => $this->getWorkStatusByDatePublished($chapter, $publication),
            'publicationDate' => $chapter->getDatePublished() ?? $publication->getData('datePublished'),
            'landingPage' => $context['landingPage'],
        ];

        $optionalData = [
            'doi' => $chapter->getData('doiObject')?->getResolvingUrl(),
            'pageInterval' => $pages['pageInterval'] ?? null,
            'firstPage' => $pages['firstPage'] ?? null,
            'lastPage' => $pages['lastPage'] ?? null,
        ];
        foreach ($optionalData as $fieldName => $fieldValue) {
            if ($fieldValue !== null && $fieldValue !== '') {
                $workData[$fieldName] = $fieldValue;
            }
        }

        return new ThothWork($workData);
    }

    public function getWorkStatusByDatePublished($chapter, $publication)
    {
        $dataPublished = $chapter->getDatePublished() ?? $publication->getData('datePublished');

        if ($dataPublished && $dataPublished <= Core::getCurrentDate()) {
            return WorkStatus::ACTIVE;
        }

        return WorkStatus::FORTHCOMING;
    }

    private function extractPages($chapter): array
    {
        $pages = $chapter->getPages();

        if (empty($pages)) {
            return [];
        }

        if (strpos($pages, '-') === false) {
            return [
                'firstPage' => trim($pages),
            ];
        }

        [$firstPage, $lastPage] = explode('-', $pages);
        return [
            'pageInterval' => trim($pages),
            'firstPage' => trim($firstPage),
            'lastPage' => trim($lastPage)
        ];
    }
}
