<?php

/**
 * @file plugins/generic/thoth/classes/services/ThothSubjectClassifier.inc.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothSubjectClassifier
 * @ingroup plugins_generic_thoth
 *
 * @brief Classifies OMP subject metadata for Thoth
 */

use ThothApi\GraphQL\Enums\SubjectType;

import('classes.codelist.SubjectDAO');
import('lib.pkp.classes.db.XMLDAO');

class ThothSubjectClassifier
{
    private const ONIX_SUBJECT_SCHEMES = [
        '03' => SubjectType::LCC,
        '10' => SubjectType::BISAC,
        '12' => SubjectType::BIC,
        '93' => SubjectType::THEMA,
    ];

    private $bicValidator;
    private $themaValidator;
    private $httpClient;
    private $bicCodes;
    private $themaCodes = [];

    public function __construct($bicValidator = null, $themaValidator = null, $httpClient = null)
    {
        $this->bicValidator = $bicValidator;
        $this->themaValidator = $themaValidator;
        $this->httpClient = $httpClient;
    }

    public function classify($subject)
    {
        if (
            !is_array($subject)
            && preg_match('/^\s*(03|10|12|93|LCC|BISAC|BIC|THEMA)\s*:\s*(.+?)\s*$/i', (string) $subject, $matches)
        ) {
            $subject = [
                'name' => trim((string) $subject),
                'source' => $matches[1],
                'identifier' => $matches[2],
            ];
        }

        $name = trim((string) ($subject['name'] ?? $subject));
        $identifier = strtoupper(trim((string) ($subject['identifier'] ?? '')));
        $source = trim((string) ($subject['source'] ?? ''));

        if ($identifier !== '' && $source !== '') {
            $subjectType = $this->getSubjectTypeFromSource($source);
            if ($subjectType !== null && $this->isValidCode($identifier, $subjectType)) {
                return [
                    'subjectType' => $subjectType,
                    'subjectCode' => $identifier,
                ];
            }
            if ($subjectType === null && !$this->isOnixSubjectScheme($source)) {
                return [
                    'subjectType' => SubjectType::CUSTOM,
                    'subjectCode' => $subject['identifier'],
                ];
            }
            return $this->asKeyword($name !== '' ? $name : $identifier);
        }

        $code = strtoupper($name);
        $bicMatch = $this->isBicCode($code);
        $themaMatch = $this->isThemaCode($code);
        if ($bicMatch === null || $themaMatch === null) {
            return $this->asKeyword($name);
        }

        $matchingTypes = [];
        if ($bicMatch) {
            $matchingTypes[] = SubjectType::BIC;
        }
        if ($themaMatch) {
            $matchingTypes[] = SubjectType::THEMA;
        }

        if (count($matchingTypes) === 1) {
            return [
                'subjectType' => $matchingTypes[0],
                'subjectCode' => $code,
            ];
        }

        return $this->asKeyword($name);
    }

    private function getSubjectTypeFromSource($source)
    {
        $normalizedSource = strtolower(trim($source));
        $aliases = [
            'lcc' => SubjectType::LCC,
            'lc classification' => SubjectType::LCC,
            'bisac' => SubjectType::BISAC,
            'bisacsh' => SubjectType::BISAC,
            'bic' => SubjectType::BIC,
            'bicssc' => SubjectType::BIC,
            'thema' => SubjectType::THEMA,
        ];
        if (isset(self::ONIX_SUBJECT_SCHEMES[$source])) {
            return self::ONIX_SUBJECT_SCHEMES[$source];
        }
        if (isset($aliases[$normalizedSource])) {
            return $aliases[$normalizedSource];
        }

        $url = parse_url($normalizedSource);
        $host = $url['host'] ?? '';
        $path = $url['path'] ?? '';
        if ($host === 'ns.editeur.org' && strpos($path, '/thema') === 0) {
            return SubjectType::THEMA;
        }
        if (in_array($host, ['bic.org.uk', 'www.bic.org.uk'], true) && strpos($path, 'subject') !== false) {
            return SubjectType::BIC;
        }
        if (in_array($host, ['bisg.org', 'www.bisg.org'], true) && strpos($path, 'bisac') !== false) {
            return SubjectType::BISAC;
        }
        if (in_array($host, ['id.loc.gov', 'www.loc.gov'], true) && strpos($path, 'class') !== false) {
            return SubjectType::LCC;
        }

        return null;
    }

    private function isValidCode($code, $subjectType)
    {
        switch ($subjectType) {
            case SubjectType::BIC:
                return $this->isBicCode($code) === true;
            case SubjectType::THEMA:
                return $this->isThemaCode($code) === true;
            case SubjectType::BISAC:
                return (bool) preg_match('/^[A-Z]{3}[0-9]{6}$/', $code);
            case SubjectType::LCC:
                return (bool) preg_match('/^[A-Z]{1,3}[0-9]+(?:\.[0-9]+)?(?:\.[A-Z][0-9]+)?$/', $code);
        }

        return false;
    }

    private function isOnixSubjectScheme($source)
    {
        return (bool) preg_match('/^[0-9A-Z]{2}$/i', trim($source));
    }

    private function isBicCode($code)
    {
        if ($this->bicValidator !== null) {
            return (bool) call_user_func($this->bicValidator, $code);
        }

        if ($this->bicCodes === null) {
            $this->bicCodes = [];
            try {
                $subjectDao = new SubjectDAO();
                $nodeName = $subjectDao->getName();
                $data = (new XMLDAO())->parseStruct(
                    $subjectDao->getFilename(MASTER_LOCALE),
                    [$nodeName]
                );
                foreach ($data[$nodeName] ?? [] as $subject) {
                    $this->bicCodes[$subject['attributes']['code']] = true;
                }
            } catch (Throwable $exception) {
                return null;
            }
        }

        return isset($this->bicCodes[$code]);
    }

    private function isThemaCode($code)
    {
        if ($this->themaValidator !== null) {
            return (bool) call_user_func($this->themaValidator, $code);
        }
        if (!preg_match('/^[A-Y][A-Z0-9]{0,5}$/', $code)) {
            return false;
        }
        if (array_key_exists($code, $this->themaCodes)) {
            return $this->themaCodes[$code];
        }

        try {
            $response = $this->getHttpClient()->request(
                'GET',
                'https://ns.editeur.org/thema/en/' . rawurlencode($code),
                [
                    'allow_redirects' => false,
                    'connect_timeout' => 3,
                    'http_errors' => false,
                    'timeout' => 5,
                ]
            );
            if ($response->getStatusCode() !== 200) {
                return $this->themaCodes[$code] = null;
            }

            $document = new DOMDocument();
            $loaded = $document->loadHTML(
                (string) $response->getBody(),
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
            );
            if (!$loaded) {
                return $this->themaCodes[$code] = null;
            }
            $xpath = new DOMXPath($document);
            $notationNodes = $xpath->query(
                '//td[contains(concat(" ", normalize-space(@class), " "), " notation ")]'
            );
            foreach ($notationNodes as $node) {
                if (trim($node->textContent) === $code) {
                    return $this->themaCodes[$code] = true;
                }
            }
        } catch (Throwable $exception) {
            return $this->themaCodes[$code] = null;
        }

        return $this->themaCodes[$code] = false;
    }

    private function getHttpClient()
    {
        return $this->httpClient ?? Application::get()->getHttpClient();
    }

    private function asKeyword($subject)
    {
        return [
            'subjectType' => SubjectType::KEYWORD,
            'subjectCode' => $subject,
        ];
    }
}
