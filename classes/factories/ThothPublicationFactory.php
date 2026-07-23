<?php

/**
 * @file plugins/generic/thoth/classes/factories/ThothPublicationFactory.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothPublicationFactory
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief A factory to create Thoth publications
 */

namespace APP\plugins\generic\thoth\classes\factories;

use ThothApi\GraphQL\Enums\PublicationType;
use ThothApi\GraphQL\Inputs\PatchPublication as ThothPublication;

class ThothPublicationFactory
{
    private const ACCESSIBILITY_FIELDS = [
        'accessibilityStandard',
        'accessibilityAdditionalStandard',
        'accessibilityException',
        'accessibilityReportUrl',
    ];

    private const PHYSICAL_PUBLICATION_TYPE_MAPPING = [
        'BC' => PublicationType::PAPERBACK,
        'BB' => PublicationType::HARDBACK,
    ];

    private const DIGITAL_PUBLICATION_TYPE_MAPPING = [
        'html' => PublicationType::HTML,
        'htm' => PublicationType::HTML,
        'xhtml' => PublicationType::HTML,
        'pdf' => PublicationType::PDF,
        'xml' => PublicationType::XML,
        'jats' => PublicationType::XML,
        'epub' => PublicationType::EPUB,
        'mobi' => PublicationType::MOBI,
        'azw3' => PublicationType::AZW3,
        'doc' => PublicationType::DOCX,
        'docx' => PublicationType::DOCX,
        'fb2' => PublicationType::FICTION_BOOK,
        'fictionbook' => PublicationType::FICTION_BOOK,
        'wav' => PublicationType::WAV,
        'mp3' => PublicationType::MP3,
        'm4a' => PublicationType::MP3,
        'aac' => PublicationType::MP3,
        'flac' => PublicationType::MP3,
        'ogg' => PublicationType::MP3,
        'oga' => PublicationType::MP3,
        'audio' => PublicationType::MP3,
    ];

    private const MIME_TYPE_MAPPING = [
        'text/html' => PublicationType::HTML,
        'application/xhtml+xml' => PublicationType::HTML,
        'application/pdf' => PublicationType::PDF,
        'application/xml' => PublicationType::XML,
        'text/xml' => PublicationType::XML,
        'application/jats+xml' => PublicationType::XML,
        'application/epub+zip' => PublicationType::EPUB,
        'application/x-mobipocket-ebook' => PublicationType::MOBI,
        'application/vnd.amazon.ebook' => PublicationType::AZW3,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => PublicationType::DOCX,
        'application/msword' => PublicationType::DOCX,
        'application/x-fictionbook+xml' => PublicationType::FICTION_BOOK,
        'audio/wav' => PublicationType::WAV,
        'audio/wave' => PublicationType::WAV,
        'audio/x-wav' => PublicationType::WAV,
        'audio/mpeg' => PublicationType::MP3,
        'audio/mp3' => PublicationType::MP3,
        'audio/mp4' => PublicationType::MP3,
        'audio/x-m4a' => PublicationType::MP3,
        'audio/aac' => PublicationType::MP3,
        'audio/flac' => PublicationType::MP3,
        'audio/ogg' => PublicationType::MP3,
    ];

    public function createFromPublicationFormat($publicationFormat, $submissionFile = null)
    {
        $publicationData = [
            'publicationType' => $this->getPublicationTypeByPublicationFormat($publicationFormat, $submissionFile),
            'isbn' => $this->getIsbnByPublicationFormat($publicationFormat),
        ];

        foreach (self::ACCESSIBILITY_FIELDS as $fieldName) {
            $fieldValue = $publicationFormat->getData($fieldName);
            $publicationData[$fieldName] = $fieldValue === '' ? null : $fieldValue;
        }

        return new ThothPublication($publicationData);
    }

    private function getPublicationTypeByPublicationFormat($publicationFormat, $submissionFile = null)
    {
        $entryKey = $publicationFormat->getEntryKey();
        if ($entryKey !== 'DA') {
            return self::PHYSICAL_PUBLICATION_TYPE_MAPPING[$entryKey]
                ?? PublicationType::PDF;
        }

        $submissionFilePublicationType = $this->getPublicationTypeBySubmissionFile($submissionFile);
        if ($submissionFilePublicationType !== null) {
            return $submissionFilePublicationType;
        }

        $remoteUrlPublicationType = $this->getPublicationTypeByUrl($publicationFormat->getData('urlRemote'));
        if ($remoteUrlPublicationType !== null) {
            return $remoteUrlPublicationType;
        }

        $publicationFormatName = $this->normalizeFormatLabel($publicationFormat->getLocalizedName());

        return self::DIGITAL_PUBLICATION_TYPE_MAPPING[$publicationFormatName]
            ?? PublicationType::PDF;
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
