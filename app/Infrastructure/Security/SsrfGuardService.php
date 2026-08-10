<?php

namespace App\Infrastructure\Security;

use Exception;

class SsrfGuardService
{
    /**
     * Validate an outbound URL against SSRF attacks (blocking private/loopback/link-local IPs).
     *
     * @param string $url
     * @throws Exception
     */
    public function validateUrl(string $url): void
    {
        $parsed = parse_url($url);

        if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            throw new Exception("Invalid feed URL format.");
        }

        $scheme = strtolower($parsed['scheme']);
        if (!in_array($scheme, ['http', 'https', 'webcal'])) {
            throw new Exception("Disallowed URL scheme '{$scheme}'. Only http, https, and webcal are supported.");
        }

        $host = $parsed['host'];

        // Resolve IP address
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

        if (!$ip || $ip === $host && !filter_var($ip, FILTER_VALIDATE_IP)) {
            // DNS resolution failed or returned hostname directly
            throw new Exception("Unable to resolve hostname '{$host}'.");
        }

        // Validate IP against private/reserved ranges
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new Exception("SSRF Protection Block: Target IP '{$ip}' resolves to a private, loopback, or reserved range.");
        }
    }
}
