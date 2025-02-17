<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothPublicationFactory.inc.php
 *
 * Copyright (c) 2024-2025 Lepidus Tecnologia
 * Copyright (c) 2024-2025 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationFactory
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth publications
 */

use ThothApi\GraphQL\Models\Publication as ThothPublication;

class ThothPublicationFactory
{
    public function createFromPublicationFormat($publicationFormat)
    {
        return new ThothPublication([
            'publicationType' => $this->getPublicationTypeByPublicationFormat($publicationFormat),
            'isbn' => $this->getIsbnByPublicationFormat($publicationFormat)
        ]);
    }

    private function getPublicationTypeByPublicationFormat($publicationFormat)
    {
        $publicationTypeMapping = [
            'BC' => ThothPublication::PUBLICATION_TYPE_PAPERBACK,
            'BB' => ThothPublication::PUBLICATION_TYPE_HARDBACK,
            'DA' => [
                'html' => ThothPublication::PUBLICATION_TYPE_HTML,
                'pdf' => ThothPublication::PUBLICATION_TYPE_PDF,
                'xml' => ThothPublication::PUBLICATION_TYPE_XML,
                'epub' => ThothPublication::PUBLICATION_TYPE_EPUB,
                'mobi' => ThothPublication::PUBLICATION_TYPE_MOBI,
                'azw3' => ThothPublication::PUBLICATION_TYPE_AZW3,
                'docx' => ThothPublication::PUBLICATION_TYPE_DOCX,
                'fictionbook' => ThothPublication::PUBLICATION_TYPE_FICTION_BOOK,
                'wav' => ThothPublication::PUBLICATION_TYPE_WAV,
                'mp3' => ThothPublication::PUBLICATION_TYPE_MP3,
                'audio' => ThothPublication::PUBLICATION_TYPE_MP3,
                'video' => ThothPublication::PUBLICATION_TYPE_MP3
            ]
        ];

        $entryKey = $publicationFormat->getEntryKey();
        if ($entryKey != 'DA') {
            return $publicationTypeMapping[$entryKey];
        }

        $pubFormatName = $publicationFormat->getLocalizedName();
        $pubFormatName = trim(
            preg_replace(
                "/[^a-z0-9\.\-]+/",
                '',
                str_replace(
                    [' ', '_', ':'],
                    '',
                    strtolower($pubFormatName)
                )
            )
        );

        return $publicationTypeMapping[$entryKey][$pubFormatName] ?? ThothPublication::PUBLICATION_TYPE_PDF;
    }

    private function getIsbnByPublicationFormat($publicationFormat)
    {
        $identificationCodes = $publicationFormat->getIdentificationCodes()->toArray();
        foreach ($identificationCodes as $identificationCode) {
            if ($identificationCode->getCode() == '15' || $identificationCode->getCode() == '24') {
                return $identificationCode->getValue();
            }
        }

        return null;
    }
}
