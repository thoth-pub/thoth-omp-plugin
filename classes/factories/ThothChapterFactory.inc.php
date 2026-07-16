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
class ThothChapterFactory
{
    public function createFromChapter($chapter)
    {
        $request = Application::get()->getRequest();
        $publication = DAORegistry::getDAO('PublicationDAO')->getById($chapter->getData('publicationId'));
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById($publication->getData('submissionId'));
        $context = Application::getContextDAO()->getById($submission->getData('contextId'));

        $pages = $this->extractPages($chapter);

        $workData = [
            'workType' => WorkType::BOOK_CHAPTER,
            'workStatus' => $this->getWorkStatusByDatePublished($chapter, $publication),
            'publicationDate' => $chapter->getDatePublished() ?? $publication->getData('datePublished'),
            'landingPage' => $request->getDispatcher()->url(
                $request,
                ROUTE_PAGE,
                $context->getPath(),
                'catalog',
                'book',
                $submission->getBestId()
            ),
        ];

        $optionalData = [
            'doi' => DoiFormatter::resolveUrl($chapter->getStoredPubId('doi')),
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

        list($firstPage, $lastPage) = explode('-', $pages);
        return [
            'pageInterval' => trim($pages),
            'firstPage' => trim($firstPage),
            'lastPage' => trim($lastPage)
        ];
    }

    public function getWorkStatusByDatePublished($chapter, $publication)
    {
        $dataPublished = $chapter->getDatePublished() ?? $publication->getData('datePublished');

        if ($dataPublished && $dataPublished <= \Core::getCurrentDate()) {
            return WorkStatus::ACTIVE;
        }

        return WorkStatus::FORTHCOMING;
    }
}
