<?php

/**
 * @file plugins/generic/thoth/classes/notification/ThothErrorFormatter.inc.php
 *
 * Copyright (c) 2026 Lepidus Tecnologia
 * Copyright (c) 2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothErrorFormatter
 *
 * @ingroup plugins_generic_thoth
 *
 * @brief Formats error causes and safe diagnostic context for Thoth operations
 */

use ThothApi\Exception\QueryException;

class ThothErrorFormatter
{
    public static function reason($error): ?string
    {
        if ($error === null) {
            return null;
        }
        if ($error instanceof \Throwable) {
            $messages = [];
            do {
                $messages[] = self::sanitize($error->getMessage());
                $error = $error->getPrevious();
            } while ($error !== null);
            return implode(': ', array_unique($messages));
        }
        if (is_scalar($error)) {
            return self::sanitize((string) $error);
        }
        if (is_object($error) && method_exists($error, 'getMessage')) {
            return self::sanitize($error->getMessage());
        }
        $reason = json_encode($error, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        return $reason === false ? null : self::sanitize($reason);
    }

    private static function sanitize(string $message): string
    {
        $message = preg_replace_callback('~https?://[^\s<>"`]+~i', static function (array $match): string {
            $url = parse_url(rtrim($match[0], "'"));
            if ($url === false || !isset($url['scheme'], $url['host'])) {
                return '[URL redacted]';
            }
            return $url['scheme'] . '://' . $url['host']
                . (isset($url['port']) ? ':' . $url['port'] : '') . ($url['path'] ?? '');
        }, $message);
        $message = preg_replace('/(\b(?:Bearer|Basic)\s+)[^\s;,]+/i', '$1[redacted]', $message);
        $credentials = <<<'REGEX'
~((?:["']?)(?:password|token|secret|api[_-]?key|X-Amz-Signature|X-Amz-Credential|X-Amz-Security-Token)(?:["']?)\s*[:=]\s*)(?:"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|[^\s,;]+)~i
REGEX;
        return preg_replace($credentials, '$1[redacted]', $message);
    }

    public static function message(string $message, $error, bool $escape = false): string
    {
        $reason = self::reason($error);
        if ($reason === null || $reason === '') {
            return $message;
        }
        return ($message === '' ? '' : $message . ': ')
            . ($escape ? htmlspecialchars($reason, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : $reason);
    }

    public static function context($error, array $context = []): array
    {
        $context['reason'] = self::reason($error);
        if ($error instanceof \Throwable) {
            $context['exception'] = get_class($error);
            do {
                if ($error instanceof QueryException) {
                    $context['status'] = $error->getStatusCode();
                    // Inspect only operation structure. Never log query literals or variable values.
                    $query = preg_replace('/""".*?"""|"(?:\\\\.|[^"\\\\])*"|#[^\r\n]*/s', '', $error->getQuery() ?? '');
                    if (preg_match('/^\s*(query|mutation|subscription)\b/', $query, $match)) {
                        $context['operation'] = $match[1];
                    } elseif (preg_match('/^\s*\{/', $query)) {
                        $context['operation'] = 'query';
                    }
                    if (preg_match('/\{\s*(?:[A-Za-z_][A-Za-z_0-9]*\s*:\s*)?([A-Za-z_][A-Za-z_0-9]*)/', $query, $match)) {
                        $context['field'] = $match[1];
                    }
                    break;
                }
                $error = $error->getPrevious();
            } while ($error !== null);
        }
        return $context;
    }

    public static function log($error, array $context = []): void
    {
        $context = self::context($error, $context);
        $operation = $context['operation'] ?? 'request';
        if (!empty($context['field'])) {
            $operation .= ' ' . $context['field'];
        }

        $details = [];
        foreach ([
            'status' => 'HTTP',
            'submissionId' => 'submission',
            'contextId' => 'context',
        ] as $key => $label) {
            if (isset($context[$key]) && $context[$key] !== '') {
                $details[] = $label . ' ' . $context[$key];
            }
        }

        $message = 'Thoth ' . $operation . ' failed';
        if ($details) {
            $message .= ' (' . implode(', ', $details) . ')';
        }
        if (isset($context['reason']) && $context['reason'] !== '') {
            $message .= ': ' . $context['reason'];
        }
        // Keep one physical log line, including when a remote error contains control characters.
        error_log(preg_replace('/[\x00-\x1F\x7F]/', ' ', self::sanitize($message)));
    }
}
