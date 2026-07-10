<?php

require_once(__DIR__ . '/../../../vendor/autoload.php');

use PKP\tests\PKPTestCase;

import('plugins.generic.thoth.classes.security.ThothApiUrlValidator');

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
    public function testRejectsUnsafeEndpoint($url, $resolvedAddresses): void
    {
        $validator = new ThothApiUrlValidator(fn () => $resolvedAddresses);

        self::assertFalse($validator->isSafe($url));
    }

    public function unsafeUrlProvider(): array
    {
        return [
            ['http://api.example.test/graphql', ['93.184.216.34']],
            ['https://user:pass@api.example.test/graphql', ['93.184.216.34']],
            ['https://localhost/graphql', ['127.0.0.1']],
            ['https://internal.example.test/graphql', ['10.0.0.10']],
            ['https://metadata.example.test/', ['169.254.169.254']],
            ['https://api.example.test/', ['93.184.216.34', '10.0.0.10']],
            ['https://missing.example.test/', []],
        ];
    }
}
