<?php

/**
 * @file plugins/generic/thoth/tests/classes/notification/ThothErrorFormatterTest.php
 *
 * Copyright (c) 2026 Lepidus Tecnologia
 * Copyright (c) 2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThothErrorFormatterTest
 *
 * @ingroup plugins_generic_thoth_tests
 *
 * @brief Verifies error causes and diagnostic context
 */

namespace APP\plugins\generic\thoth\tests\classes\notification;

use APP\plugins\generic\thoth\classes\notification\ThothErrorFormatter;
use PHPUnit\Framework\TestCase;
use ThothApi\Exception\QueryException;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../classes/notification/ThothErrorFormatter.php';

class ThothErrorFormatterTest extends TestCase
{
    public function testMessageIncludesEscapedCause(): void
    {
        $this->assertSame(
            'Failed: &lt;script&gt;bad&lt;/script&gt;',
            ThothErrorFormatter::message('Failed', new QueryException(['message' => '<script>bad</script>']), true)
        );
    }

    public function testNoCausePreservesMessage(): void
    {
        $this->assertSame('Failed', ThothErrorFormatter::message('Failed', null));
    }

    public function testLogIdentifiesMutationWithoutPayload(): void
    {
        $error = new QueryException(
            ['message' => 'Permission denied'],
            'mutation CreateWork($input: WorkInput!) { createWork(input: $input) { workId } }',
            ['input' => ['title' => 'Private title', 'token' => 'secret']],
            null,
            403
        );
        $context = ThothErrorFormatter::context($error, ['submissionId' => 7]);
        $this->assertSame('mutation', $context['operation']);
        $this->assertSame('createWork', $context['field']);
        $this->assertSame(403, $context['status']);
        $this->assertSame(7, $context['submissionId']);
        $this->assertStringNotContainsString('secret', json_encode($context));
        $this->assertStringNotContainsString('Private title', json_encode($context));
    }

    public function testLogIdentifiesAnonymousQueryAndAlias(): void
    {
        $error = new QueryException(
            ['message' => 'Missing'],
            'query($id: Uuid!) { result: work(workId: $id) { workId } }'
        );
        $context = ThothErrorFormatter::context($error);
        $this->assertSame('query', $context['operation']);
        $this->assertSame('work', $context['field']);
    }

    public function testWrappedFailurePreservesCauseAndOperation(): void
    {
        $cause = new QueryException(['message' => 'Missing'], 'query { work { workId } }');
        $error = new \RuntimeException('Registration failed', 0, $cause);
        $this->assertSame('Registration failed: Missing', ThothErrorFormatter::reason($error));
        $this->assertSame('work', ThothErrorFormatter::context($error)['field']);
    }
    public function testSignedUrlCredentialsAreRemovedFromEveryDiagnostic(): void
    {
        $error = new \RuntimeException('Upload failed https://user:pass@example.org/file?X-Amz-Signature=secret#token');
        $this->assertSame('Upload failed https://example.org/file', ThothErrorFormatter::reason($error));
        $this->assertStringNotContainsString('secret', json_encode(ThothErrorFormatter::context($error)));
    }

    public function testAuthorizationCredentialsAreRedacted(): void
    {
        $error = new QueryException(['message' => 'Authorization: Bearer private-token; password=private-password']);
        $this->assertStringNotContainsString('private-token', ThothErrorFormatter::reason($error));
        $this->assertStringNotContainsString('private-password', ThothErrorFormatter::reason($error));
    }

    public function testQuotedCredentialsContainingSpacesAreFullyRedacted(): void
    {
        $error = 'password="two private words"; {"token":"another private token"}';
        $reason = ThothErrorFormatter::reason($error);
        $this->assertStringNotContainsString('private', $reason);
        $this->assertStringNotContainsString('words', $reason);
        $this->assertStringNotContainsString('another', $reason);
    }

    public function testMutationLogUsesReadableTextWithOperationAndContext(): void
    {
        $error = new QueryException(
            ['message' => 'Permission denied'],
            'mutation { createWork(data: $data) { workId } }',
            ['data' => ['title' => 'Private title']],
            null,
            403
        );
        $log = $this->captureLog($error, ['submissionId' => 123, 'contextId' => 1, 'action' => 'register']);
        $this->assertStringContainsString(
            'Thoth mutation createWork failed (HTTP 403, submission 123, context 1): Permission denied',
            $log
        );
        $this->assertStringNotContainsString('action', $log);
        $this->assertStringNotContainsString('exception', $log);
        $this->assertStringNotContainsString(QueryException::class, $log);
        $this->assertStringContainsString(': Permission denied', $log);
        $this->assertStringNotContainsString('Private title', $log);
        $this->assertStringNotContainsString('{', $log);
    }

    public function testQueryLogIdentifiesTheFailedQuery(): void
    {
        $error = new QueryException(['message' => 'Not found'], 'query { workByDoi(doi: $doi) { workId } }');
        $log = $this->captureLog($error);
        $this->assertStringContainsString('Thoth query workByDoi failed', $log);
        $this->assertStringContainsString(': Not found', $log);
    }

    public function testPlainErrorLogPreservesCauseWithoutCreatingExtraLogLines(): void
    {
        $log = $this->captureLog("Connection failed\nFake log entry", ['action' => "upload\r\nvideo"]);
        $this->assertStringContainsString('Thoth request failed', $log);
        $this->assertStringContainsString('Connection failed', $log);
        $this->assertStringNotContainsString('action', $log);
        $this->assertSame(0, substr_count(trim($log), "\n"));
    }

    private function captureLog($error, array $context = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'thoth-log-');
        $previousLog = ini_get('error_log');
        ini_set('error_log', $path);
        try {
            ThothErrorFormatter::log($error, $context);
            return file_get_contents($path);
        } finally {
            ini_set('error_log', $previousLog);
            unlink($path);
        }
    }

}
