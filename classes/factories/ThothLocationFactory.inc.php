<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothLocationFactory.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationFactory
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth locations
 */

use ThothApi\GraphQL\Enums\LocationPlatform;
use ThothApi\GraphQL\Inputs\PatchLocation as ThothLocation;

class ThothLocationFactory
{
    public function createFromPublicationFormat($publicationFormat, $fileId = null)
    {
        $request = Application::get()->getRequest();
        $publication = DAORegistry::getDAO('PublicationDAO')->getById($publicationFormat->getData('publicationId'));
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById($publication->getData('submissionId'));
        $context = Application::getContextDAO()->getById($submission->getData('contextId'));

        $landingPage = $request->getDispatcher()->url(
            $request,
            ROUTE_PAGE,
            $context->getPath(),
            'catalog',
            'book',
            [$submission->getBestId()]
        );
        $fullTextUrl = $fileId ?
            $request->getDispatcher()->url(
                $request,
                ROUTE_PAGE,
                $context->getPath(),
                'catalog',
                'view',
                [$submission->getBestId(), $publicationFormat->getBestId(), $fileId]
            ) : $publicationFormat->getRemoteUrl();

        $locationData = [
            'landingPage' => $landingPage,
            'locationPlatform' => LocationPlatform::OTHER,
        ];
        if ($fullTextUrl !== null && $fullTextUrl !== '') {
            $locationData['fullTextUrl'] = $fullTextUrl;
        }

        return new ThothLocation($locationData);
    }
}
