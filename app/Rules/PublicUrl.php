<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * SSRF guard for admin-supplied callback URLs (webhook endpoints): the host must
 * resolve only to public addresses — no localhost, private (RFC1918), link-local
 * (cloud metadata 169.254.169.254) or reserved ranges. HTTPS is required in production.
 */
class PublicUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = parse_url((string) $value);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = trim((string) ($parts['host'] ?? ''), '[]');

        if (! in_array($scheme, app()->isProduction() ? ['https'] : ['https', 'http'], true) || $host === '') {
            $fail(app()->isProduction() ? 'Endpoint mesti menggunakan HTTPS.' : 'URL tidak sah.');

            return;
        }

        if (! self::isPublicHost($host)) {
            $fail('Endpoint mesti alamat awam (bukan localhost atau rangkaian dalaman).');
        }
    }

    public static function isPublicHost(string $host): bool
    {
        if (in_array(strtolower($host), ['localhost', 'localhost.localdomain'], true) || str_ends_with(strtolower($host), '.local') || str_ends_with(strtolower($host), '.internal')) {
            return false;
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : array_merge(
            (array) @gethostbynamel($host),
            array_column((array) @dns_get_record($host, DNS_AAAA), 'ipv6'),
        );

        if ($ips === [] || $ips === [false]) {
            return app()->runningUnitTests();   // unresolvable host: allowed only in tests (no network)
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }
}
