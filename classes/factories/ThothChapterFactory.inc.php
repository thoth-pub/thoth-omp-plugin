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

use ThothApi\GraphQL\Models\Work as ThothWork;

import('classes.submission.Submission');
import('plugins.generic.thoth.classes.formatters.DoiFormatter');
import('plugins.generic.thoth.classes.formatters.HtmlStripper');

class ThothChapterFactory
{
    public function createFromChapter($chapter)
    {
        $request = Application::get()->getRequest();
        $publication = DAORegistry::getDAO('PublicationDAO')->getById($chapter->getData('publicationId'));
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById($publication->getData('submissionId'));
        $context = Application::getContextDAO()->getById($submission->getData('contextId'));

        $pages = $this->extractPages($chapter);

        return new ThothWork([
            'workType' => ThothWork::WORK_TYPE_BOOK_CHAPTER,
            'workStatus' => $this->getWorkStatusByDatePublished($chapter, $publication),
            'fullTitle' => $chapter->getLocalizedFullTitle(),
            'title' => $chapter->getLocalizedTitle(),
            'subtitle' => $chapter->getLocalizedData('subtitle'),
            'longAbstract' => HtmlStripper::stripTags($chapter->getLocalizedData('abstract')),
            'doi' => DoiFormatter::resolveUrl($chapter->getStoredPubId('doi')),
            'pageInterval' => $pages['pageInterval'] ?? null,
            'firstPage' => $pages['firstPage'] ?? null,
            'lastPage' => $pages['lastPage'] ?? null,
            'publicationDate' => $chapter->getDatePublished() ?? $publication->getData('datePublished'),
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
            return ThothWork::WORK_STATUS_ACTIVE;
        }

        return ThothWork::WORK_STATUS_FORTHCOMING;
    }
}
