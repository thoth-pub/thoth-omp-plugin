<?php

namespace APP\plugins\generic\thoth\classes\security;

class ThothApiUrlValidator
{
    private $resolver;

    public function __construct(?callable $resolver = null)
    {
        $this->resolver = $resolver ?? [$this, 'resolveHost'];
    }

    public function isSafe(string $url): bool
    {
        $parts = parse_url($url);
        if (
            $parts === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            return false;
        }

        $addresses = call_user_func($this->resolver, trim($parts['host'], '[]'));
        if (empty($addresses)) {
            return false;
        }

        foreach ($addresses as $address) {
            if (
                filter_var(
                    $address,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                ) === false
            ) {
                return false;
            }
        }

        return true;
    }

    private function resolveHost(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records === false) {
            return [];
        }

        $addresses = [];
        foreach ($records as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($address !== null) {
                $addresses[] = $address;
            }
        }

        return array_values(array_unique($addresses));
    }
}
