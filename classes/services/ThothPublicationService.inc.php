<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothPublicationService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationService
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth publications
 */

use Biblys\Isbn\Isbn;
use Biblys\Isbn\IsbnParsingException;
use Biblys\Isbn\IsbnValidationException;
use ThothApi\GraphQL\Models\Publication as ThothPublication;

class ThothPublicationService
{
    public $factory;
    public $repository;

    public function __construct($factory, $repository)
    {
        $this->factory = $factory;
        $this->repository = $repository;
    }

    public function register($publicationFormat, $thothWorkId, $chapterId = null)
    {
        $thothPublication = $this->factory->createFromPublicationFormat($publicationFormat);
        $thothPublication->setWorkId($thothWorkId);

        if ($chapterId !== null) {
            $thothPublication->setIsbn(null);
        }

        $thothPublicationId = $this->repository->add($thothPublication);

        $submissionFiles = array_filter(
            iterator_to_array(Services::get('submissionFile')->getMany([
                'assocTypes' => [ASSOC_TYPE_PUBLICATION_FORMAT],
                'assocIds' => [$publicationFormat->getId()],
            ])),
            function ($submissionFile) use ($chapterId) {
                return $submissionFile->getData('chapterId') == $chapterId;
            }
        );

        if (empty($submissionFiles) && $publicationFormat->getRemoteUrl()) {
            ThothService::location()->register($publicationFormat, $thothPublicationId);
        }

        foreach ($submissionFiles as $submissionFile) {
            ThothService::location()->register($publicationFormat, $thothPublicationId, $submissionFile->getId());
        }

        return $thothPublicationId;
    }

    public function validate($publicationFormat)
    {
        $errors = [];

        $thothPublication = $this->factory->createFromPublicationFormat($publicationFormat);
        if ($isbn = $thothPublication->getIsbn()) {
            $isbnValidationMessage = __(
                'plugins.generic.thoth.validation.isbn',
                ['isbn' => $isbn,'formatName' => $publicationFormat->getLocalizedName()]
            );
            try {
                Isbn::validateAsIsbn13($isbn);
            } catch (IsbnParsingException $e) {
                $errors[] = $isbnValidationMessage;
            } catch (IsbnValidationException $e) {
                $errors[] = $isbnValidationMessage;
            }

            $retrievedThothPublication = $this->repository->find($isbn);
            if ($retrievedThothPublication !== null) {
                $errors[] = __(
                    'plugins.generic.thoth.validation.isbnExists',
                    ['isbn' => $isbn]
                );
            }
        }

        return $errors;
    }
}
