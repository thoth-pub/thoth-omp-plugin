<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothPublicationService.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationService
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Helper class that encapsulates business logic for Thoth publications
 */

use APP\facades\Repo;
use Biblys\Isbn\Isbn;
use Biblys\Isbn\IsbnParsingException;
use Biblys\Isbn\IsbnValidationException;
use PKP\db\DAORegistry;

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
        $publicationFormat->setData('thothPublicationId', $thothPublicationId);

        ThothService::location()->registerByPublicationFormat($publicationFormat, $chapterId);

        return $thothPublicationId;
    }

    public function registerByPublication($publication)
    {
        $thothBookId = $publication->getData('thothBookId');
        $publicationFormats = DAORegistry::getDAO('PublicationFormatDAO')
            ->getApprovedByPublicationId($publication->getId())
            ->toArray();
        foreach ($publicationFormats as $publicationFormat) {
            if ($publicationFormat->getIsAvailable()) {
                $this->register($publicationFormat, $thothBookId);
            }
        }
    }

    public function registerByChapter($chapter)
    {
        $publication = Repo::publication()->get($chapter->getData('publicationId'));
        $submissionFiles = iterator_to_array(
            Repo::submissionFile()->getCollector()
                ->filterBySubmissionIds([$publication->getData('submissionId')])
                ->filterByAssoc(Application::ASSOC_TYPE_PUBLICATION_FORMAT)
                ->getMany()
        );
        $chapterSubmissionFiles = array_filter($submissionFiles, function ($submissionFile) use ($chapter) {
            return $submissionFile->getData('chapterId') == $chapter->getId();
        });

        $thothChapterId = $chapter->getData('thothChapterId');
        $publicationFormatDao = DAORegistry::getDAO('PublicationFormatDAO');
        foreach ($chapterSubmissionFiles as $chapterSubmissionFile) {
            $publicationFormat = $publicationFormatDao->getById($chapterSubmissionFile->getData('assocId'));
            if ($publicationFormat->getIsAvailable()) {
                $this->register($publicationFormat, $thothChapterId, $chapter->getId());
            }
        }
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
