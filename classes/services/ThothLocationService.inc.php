<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothLocationService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothLocationService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth locations
 */

use ThothApi\GraphQL\Models\Location as ThothLocation;

class ThothLocationService
{
    public $factory;
    public $repository;

    public function __construct($factory, $repository)
    {
        $this->factory = $factory;
        $this->repository = $repository;
    }

    public function register($publicationFormat, $thothPublicationId, $fileId = null)
    {
        $thothLocation = $this->factory->createFromPublicationFormat($publicationFormat, $fileId);
        $thothLocation->setPublicationId($thothPublicationId);
        $thothLocation->setCanonical(!$this->repository->hasCanonical($thothPublicationId));

        return $this->repository->add($thothLocation);
    }

    public function registerByPublicationFormat($publicationFormat, $chapterId = null)
    {
        $submissionFiles = array_filter(
            iterator_to_array(Services::get('submissionFile')->getMany([
                'assocTypes' => [ASSOC_TYPE_PUBLICATION_FORMAT],
                'assocIds' => [$publicationFormat->getId()],
            ])),
            function ($submissionFile) use ($chapterId) {
                return $submissionFile->getData('chapterId') == $chapterId;
            }
        );

        $thothPublicationId = $publicationFormat->getData('thothPublicationId');
        if (empty($submissionFiles) && $publicationFormat->getRemoteUrl()) {
            $this->register($publicationFormat, $thothPublicationId);
        }

        foreach ($submissionFiles as $submissionFile) {
            $this->register($publicationFormat, $thothPublicationId, $submissionFile->getId());
        }
    }
}
