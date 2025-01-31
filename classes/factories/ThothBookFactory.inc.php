<?php

/**
 * @file plugins/generic/thoth/tests/classes/factories/ThothBookFactory.inc.php
 *
 * Copyright (c) 2025 Lepidus Tecnologia
 * Copyright (c) 2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothBookFactory
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth books
 */

use ThothApi\GraphQL\Models\Work as ThothWork;

import('classes.submission.Submission');

class ThothBookFactory
{
    public function createFromSubmission($submission, $request)
    {
        $allowedTags = '<b><strong><em><i><u><ul><ol><li><p><h1><h2><h3><h4><h5><h6>';
        $publication = $submission->getCurrentPublication();

        return new ThothWork([
            'workType' => $this->getWorkTypeBySubmissionWorkType($submission->getData('workType')),
            'workStatus' => ThothWork::WORK_STATUS_ACTIVE,
            'fullTitle' => $publication->getLocalizedFullTitle(),
            'title' => $publication->getLocalizedTitle(),
            'subtitle' => $publication->getLocalizedData('subtitle'),
            'longAbstract' => strip_tags($publication->getLocalizedData('abstract'), $allowedTags),
            'edition' => $publication->getData('version'),
            'doi' => $this->getDoiResolvingUrl($publication->getStoredPubId('doi')),
            'publicationDate' => $publication->getData('datePublished'),
            'license' => $publication->getData('licenseUrl'),
            'copyrightHolder' => $publication->getLocalizedData('copyrightHolder'),
            'coverUrl' => $publication->getLocalizedCoverImageUrl($submission->getContextId()),
            'landingPage' => $request->getDispatcher()->url(
                $request,
                ROUTE_PAGE,
                $request->getContext()->getPath(),
                'catalog',
                'book',
                $submission->getBestId()
            )
        ]);
    }

    private function getWorkTypeBySubmissionWorkType($submissionWorkType)
    {
        $workTypeMapping = [
            WORK_TYPE_EDITED_VOLUME => ThothWork::WORK_TYPE_EDITED_BOOK,
            WORK_TYPE_AUTHORED_WORK => ThothWork::WORK_TYPE_MONOGRAPH
        ];

        return $workTypeMapping[$submissionWorkType];
    }

    private function getDoiResolvingUrl($doi)
    {
        if (empty($doi)) {
            return $doi;
        }

        $search = ['%', '"', '#', ' ', '<', '>', '{'];
        $replace = ['%25', '%22', '%23', '%20', '%3c', '%3e', '%7b'];
        $encodedDoi = str_replace($search, $replace, $doi);

        return "https://doi.org/$encodedDoi";
    }
}
