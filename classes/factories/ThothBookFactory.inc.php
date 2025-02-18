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
use ThothApi\GraphQL\Models\Work as ThothWork;

import('plugins.generic.thoth.classes.formatters.HtmlStripper');

class ThothBookFactory
{
    public function createFromPublication($publication)
    {
        $request = Application::get()->getRequest();
        $submission = Repo::submission()->get($publication->getData('submissionId'));
        $context = Application::getContextDAO()->getById($submission->getData('contextId'));

        return new ThothWork([
            'workType' => $this->getWorkTypeBySubmissionWorkType($submission->getData('workType')),
            'workStatus' => empty($publication->getData('datePublished'))
                ? ThothWork::WORK_STATUS_FORTHCOMING
                : ThothWork::WORK_STATUS_ACTIVE,
            'fullTitle' => $publication->getLocalizedFullTitle(),
            'title' => $publication->getLocalizedTitle(),
            'subtitle' => $publication->getLocalizedData('subtitle'),
            'longAbstract' => HtmlStripper::stripTags($publication->getLocalizedData('abstract')),
            'edition' => $publication->getData('version'),
            'doi' => $publication->getData('doiObject')?->getResolvingUrl(),
            'publicationDate' => $publication->getData('datePublished'),
            'license' => $publication->getData('licenseUrl'),
            'copyrightHolder' => $publication->getLocalizedData('copyrightHolder'),
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

    private function getWorkTypeBySubmissionWorkType($submissionWorkType)
    {
        $workTypeMapping = [
            Submission::WORK_TYPE_EDITED_VOLUME => ThothWork::WORK_TYPE_EDITED_BOOK,
            Submission::WORK_TYPE_AUTHORED_WORK => ThothWork::WORK_TYPE_MONOGRAPH
        ];

        return $workTypeMapping[$submissionWorkType];
    }
}
