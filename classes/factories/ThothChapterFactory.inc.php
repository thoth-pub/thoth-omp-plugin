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
use PKP\core\Core;
use ThothApi\GraphQL\Models\Work as ThothWork;

import('plugins.generic.thoth.classes.formatters.HtmlStripper');

class ThothChapterFactory
{
    public function createFromChapter($chapter)
    {
        $request = Application::get()->getRequest();
        $publication = Repo::publication()->get($chapter->getData('publicationId'));
        $submission = Repo::submission()->get($publication->getData('submissionId'));
        $context = Application::getContextDAO()->getById($submission->getData('contextId'));

        return new ThothWork([
            'workType' => ThothWork::WORK_TYPE_BOOK_CHAPTER,
            'workStatus' => $this->getWorkStatusByDatePublished($chapter, $publication),
            'fullTitle' => $chapter->getLocalizedFullTitle(),
            'title' => $chapter->getLocalizedTitle(),
            'subtitle' => $chapter->getLocalizedData('subtitle'),
            'longAbstract' => HtmlStripper::stripTags($chapter->getLocalizedData('abstract')),
            'doi' => $chapter->getData('doiObject')?->getResolvingUrl(),
            'pageCount' => !empty($chapter->getPages()) ? (int) $chapter->getPages() : null,
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

    public function getWorkStatusByDatePublished($chapter, $publication)
    {
        $dataPublished = $chapter->getDatePublished() ?? $publication->getData('datePublished');

        if ($dataPublished && $dataPublished <= Core::getCurrentDate()) {
            return ThothWork::WORK_STATUS_ACTIVE;
        }

        return ThothWork::WORK_STATUS_FORTHCOMING;
    }
}
