<?php

/**
 * @file plugins/generic/thoth/classes/ThothValidator.php
 *
 * Copyright (c) 2025 Lepidus Tecnologia
 * Copyright (c) 2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothValidator
 * @ingroup plugins_generic_thoth
 *
 * @brief Validate submission metadata to Thoth submit
 */

require_once(__DIR__ . '/../lib/vendor/autoload.php');

use Biblys\Isbn\Isbn;

import('plugins.generic.thoth.classes.facades.ThothService');

class ThothValidator
{
    public static function validate($submission)
    {
        $errors = [];

        $publicationFormats = Application::getRepresentationDao()
            ->getApprovedByPublicationId($submission->getData('currentPublicationId'))
            ->toArray();

        $publicationFormats = array_filter($publicationFormats, function ($publicationFormat) {
            return $publicationFormat->getIsAvailable();
        });

        $errors = array_merge($errors, self::validateIsbn($publicationFormats));

        return $errors;
    }

    public static function validateIsbn($publicationFormats)
    {
        $errors = [];
        foreach ($publicationFormats as $publicationFormat) {
            try {
                $isbn = ThothService::publication()->getIsbnByPublicationFormat($publicationFormat);
                Isbn::validateAsIsbn13($isbn);
            } catch (Exception $e) {
                $errors[] = __('plugins.generic.thoth.validation.isbn', [
                    'isbn' => $isbn,
                    'formatName' => $publicationFormat->getLocalizedName()
                ]);
            }
        }

        return $errors;
    }
}
