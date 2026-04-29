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
    private const ACCESSIBILITY_FIELDS = [
        'accessibilityStandard',
        'accessibilityAdditionalStandard',
        'accessibilityException',
        'accessibilityReportUrl',
    ];

    private const PHYSICAL_PUBLICATION_TYPE_MAPPING = [
        'BC' => ThothPublication::PUBLICATION_TYPE_PAPERBACK,
        'BB' => ThothPublication::PUBLICATION_TYPE_HARDBACK,
    ];

    private const DIGITAL_PUBLICATION_TYPE_MAPPING = [
        'html' => ThothPublication::PUBLICATION_TYPE_HTML,
        'htm' => ThothPublication::PUBLICATION_TYPE_HTML,
        'xhtml' => ThothPublication::PUBLICATION_TYPE_HTML,
        'pdf' => ThothPublication::PUBLICATION_TYPE_PDF,
        'xml' => ThothPublication::PUBLICATION_TYPE_XML,
        'jats' => ThothPublication::PUBLICATION_TYPE_XML,
        'epub' => ThothPublication::PUBLICATION_TYPE_EPUB,
        'mobi' => ThothPublication::PUBLICATION_TYPE_MOBI,
        'azw3' => ThothPublication::PUBLICATION_TYPE_AZW3,
        'doc' => ThothPublication::PUBLICATION_TYPE_DOCX,
        'docx' => ThothPublication::PUBLICATION_TYPE_DOCX,
        'fb2' => ThothPublication::PUBLICATION_TYPE_FICTION_BOOK,
        'fictionbook' => ThothPublication::PUBLICATION_TYPE_FICTION_BOOK,
        'wav' => ThothPublication::PUBLICATION_TYPE_WAV,
        'mp3' => ThothPublication::PUBLICATION_TYPE_MP3,
        'm4a' => ThothPublication::PUBLICATION_TYPE_MP3,
        'aac' => ThothPublication::PUBLICATION_TYPE_MP3,
        'flac' => ThothPublication::PUBLICATION_TYPE_MP3,
        'ogg' => ThothPublication::PUBLICATION_TYPE_MP3,
        'oga' => ThothPublication::PUBLICATION_TYPE_MP3,
        'audio' => ThothPublication::PUBLICATION_TYPE_MP3,
    ];

    private const MIME_TYPE_MAPPING = [
        'text/html' => ThothPublication::PUBLICATION_TYPE_HTML,
        'application/xhtml+xml' => ThothPublication::PUBLICATION_TYPE_HTML,
        'application/pdf' => ThothPublication::PUBLICATION_TYPE_PDF,
        'application/xml' => ThothPublication::PUBLICATION_TYPE_XML,
        'text/xml' => ThothPublication::PUBLICATION_TYPE_XML,
        'application/jats+xml' => ThothPublication::PUBLICATION_TYPE_XML,
        'application/epub+zip' => ThothPublication::PUBLICATION_TYPE_EPUB,
        'application/x-mobipocket-ebook' => ThothPublication::PUBLICATION_TYPE_MOBI,
        'application/vnd.amazon.ebook' => ThothPublication::PUBLICATION_TYPE_AZW3,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ThothPublication::PUBLICATION_TYPE_DOCX,
        'application/msword' => ThothPublication::PUBLICATION_TYPE_DOCX,
        'application/x-fictionbook+xml' => ThothPublication::PUBLICATION_TYPE_FICTION_BOOK,
        'audio/wav' => ThothPublication::PUBLICATION_TYPE_WAV,
        'audio/wave' => ThothPublication::PUBLICATION_TYPE_WAV,
        'audio/x-wav' => ThothPublication::PUBLICATION_TYPE_WAV,
        'audio/mpeg' => ThothPublication::PUBLICATION_TYPE_MP3,
        'audio/mp3' => ThothPublication::PUBLICATION_TYPE_MP3,
        'audio/mp4' => ThothPublication::PUBLICATION_TYPE_MP3,
        'audio/x-m4a' => ThothPublication::PUBLICATION_TYPE_MP3,
        'audio/aac' => ThothPublication::PUBLICATION_TYPE_MP3,
        'audio/flac' => ThothPublication::PUBLICATION_TYPE_MP3,
        'audio/ogg' => ThothPublication::PUBLICATION_TYPE_MP3,
    ];

    public function createFromPublicationFormat($publicationFormat, $submissionFile = null)
    {
        $publicationData = [
            'publicationType' => $this->getPublicationTypeByPublicationFormat($publicationFormat, $submissionFile),
            'isbn' => $this->getIsbnByPublicationFormat($publicationFormat),
        ];

        foreach (self::ACCESSIBILITY_FIELDS as $fieldName) {
            $fieldValue = $publicationFormat->getData($fieldName);
            if ($fieldValue !== null && $fieldValue !== '') {
                $publicationData[$fieldName] = $fieldValue;
            }
        }

        return new ThothPublication($publicationData);
    }

    private function getPublicationTypeByPublicationFormat($publicationFormat, $submissionFile = null)
    {
        $entryKey = $publicationFormat->getEntryKey();
        if ($entryKey != 'DA') {
            return self::PHYSICAL_PUBLICATION_TYPE_MAPPING[$entryKey]
                ?? ThothPublication::PUBLICATION_TYPE_PDF;
        }

        $submissionFilePublicationType = $this->getPublicationTypeBySubmissionFile($submissionFile);
        if ($submissionFilePublicationType !== null) {
            return $submissionFilePublicationType;
        }

        $remoteUrlPublicationType = $this->getPublicationTypeByUrl($publicationFormat->getRemoteUrl());
        if ($remoteUrlPublicationType !== null) {
            return $remoteUrlPublicationType;
        }

        $publicationFormatName = $this->normalizeFormatLabel($publicationFormat->getLocalizedName());

        return self::DIGITAL_PUBLICATION_TYPE_MAPPING[$publicationFormatName]
            ?? ThothPublication::PUBLICATION_TYPE_PDF;
    }

    private function getPublicationTypeBySubmissionFile($submissionFile)
    {
        if (!$submissionFile) {
            return null;
        }

        $candidates = [];
        $fileNames = [
            $this->getSubmissionFileValue($submissionFile, 'getOriginalFileName', 'originalFileName'),
            $this->getSubmissionFileValue($submissionFile, 'getServerFileName', 'serverFileName'),
        ];

        foreach ($fileNames as $fileName) {
            $extension = pathinfo((string) $fileName, PATHINFO_EXTENSION);
            if ($extension !== '') {
                $candidates[] = $extension;
            }
        }

        $mimeTypes = [
            $this->getSubmissionFileValue($submissionFile, 'getFileType', 'filetype'),
            $submissionFile->getData('mimetype'),
            $submissionFile->getData('mimeType'),
        ];

        foreach ($mimeTypes as $mimeType) {
            if (!empty($mimeType)) {
                $candidates[] = $mimeType;
            }
        }

        return $this->resolveDigitalPublicationType($candidates);
    }

    private function getSubmissionFileValue($submissionFile, $methodName, $dataKey)
    {
        if (method_exists($submissionFile, $methodName)) {
            return $submissionFile->$methodName();
        }

        return $submissionFile->getData($dataKey);
    }

    private function getPublicationTypeByUrl($url)
    {
        if (empty($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (empty($path)) {
            return null;
        }

        return $this->resolveDigitalPublicationType([
            pathinfo($path, PATHINFO_EXTENSION),
        ]);
    }

    private function resolveDigitalPublicationType(array $candidates)
    {
        foreach ($candidates as $candidate) {
            if (empty($candidate)) {
                continue;
            }

            $normalizedCandidate = $this->normalizeFormatLabel($candidate);
            if (isset(self::DIGITAL_PUBLICATION_TYPE_MAPPING[$normalizedCandidate])) {
                return self::DIGITAL_PUBLICATION_TYPE_MAPPING[$normalizedCandidate];
            }

            $normalizedMimeType = strtolower(trim((string) $candidate));
            if (isset(self::MIME_TYPE_MAPPING[$normalizedMimeType])) {
                return self::MIME_TYPE_MAPPING[$normalizedMimeType];
            }
        }

        return null;
    }

    private function normalizeFormatLabel($value)
    {
        return trim(
            preg_replace(
                "/[^a-z0-9\/\+\.\-]+/",
                '',
                str_replace(
                    [' ', '_', ':'],
                    '',
                    strtolower((string) $value)
                )
            )
        );
    }

    private function getIsbnByPublicationFormat($publicationFormat)
    {
        $identificationCodes = $publicationFormat->getIdentificationCodes()->toArray();
        foreach ($identificationCodes as $identificationCode) {
            if ($identificationCode->getCode() == '15' || $identificationCode->getCode() == '24') {
                $isbn = $identificationCode->getValue();
                try {
                    $isbn13 = \Biblys\Isbn\Isbn::convertToIsbn13($isbn);
                    return str_replace('-', '', $isbn13) === $isbn ? $isbn13 : $isbn;
                } catch (Exception $e) {
                    return $isbn;
                }
            }
        }

        return null;
    }
}
