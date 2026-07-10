<?php

namespace APP\plugins\generic\thoth\tests\classes\security;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use APP\plugins\generic\thoth\classes\security\ThothApiUrlValidator;
use PKP\tests\PKPTestCase;

class ThothApiUrlValidatorTest extends PKPTestCase
{
    public function testAcceptsPublicHttpsEndpoint(): void
    {
        $validator = new ThothApiUrlValidator(fn () => ['93.184.216.34']);

        self::assertTrue($validator->isSafe('https://api.example.test/graphql'));
    }

    /**
     * @dataProvider unsafeUrlProvider
     */
    public function testRejectsUnsafeEndpoint(string $url, array $resolvedAddresses): void
    {
        $validator = new ThothApiUrlValidator(fn () => $resolvedAddresses);

        self::assertFalse($validator->isSafe($url));
    }

    public function unsafeUrlProvider(): array
    {
        return [
            'cleartext HTTP' => ['http://api.example.test/graphql', ['93.184.216.34']],
            'embedded credentials' => ['https://user:pass@api.example.test/graphql', ['93.184.216.34']],
            'loopback' => ['https://localhost/graphql', ['127.0.0.1']],
            'private network' => ['https://internal.example.test/graphql', ['10.0.0.10']],
            'link local metadata' => ['https://metadata.example.test/', ['169.254.169.254']],
            'mixed public and private DNS' => ['https://api.example.test/', ['93.184.216.34', '10.0.0.10']],
            'unresolved hostname' => ['https://missing.example.test/', []],
        ];
    }
}
