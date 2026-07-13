<?php
/**
 * GreenArc - pure, side-effect-free helpers for the contact endpoint.
 * Kept separate from contact.php so they can be unit-tested (see tests/).
 */

declare(strict_types=1);

if (!function_exists('ga_clean')) {
    /** Strip CR/LF (and their encoded forms) to prevent header injection. */
    function ga_clean(string $v): string
    {
        return trim(str_replace(["\r", "\n", "%0a", "%0d", "%0A", "%0D"], ' ', $v));
    }
}

if (!function_exists('ga_validate_contact')) {
    /**
     * Validate a contact submission.
     * @return string[] human-readable error fragments; empty array means valid.
     */
    function ga_validate_contact(array $in): array
    {
        $name    = (string) ($in['name'] ?? '');
        $email   = (string) ($in['email'] ?? '');
        $company = (string) ($in['company'] ?? '');
        $message = (string) ($in['message'] ?? '');

        $e = [];
        if (mb_strlen($name) < 2)                       $e[] = 'a valid name';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $e[] = 'a valid email address';
        if (mb_strlen($message) < 10)                   $e[] = 'a short message';
        if (mb_strlen($name) > 100)                     $e[] = 'a name under 100 characters';
        if (mb_strlen($email) > 200)                    $e[] = 'an email under 200 characters';
        if (mb_strlen($company) > 150)                  $e[] = 'a company name under 150 characters';
        if (mb_strlen($message) > 5000)                 $e[] = 'a message under 5000 characters';
        return $e;
    }
}

if (!function_exists('ga_origin_allowed')) {
    /**
     * Same-origin gate. Absent Origin/Referer passes (some privacy browsers strip
     * them); a present-but-foreign host is rejected.
     */
    function ga_origin_allowed(?string $origin, ?string $referer, array $allowedHosts): bool
    {
        $host = '';
        if (!empty($origin)) {
            $host = strtolower((string) parse_url($origin, PHP_URL_HOST));
        } elseif (!empty($referer)) {
            $host = strtolower((string) parse_url($referer, PHP_URL_HOST));
        }
        if ($host === '') {
            return true;
        }
        $allow = array_map('strtolower', $allowedHosts);
        return in_array($host, $allow, true);
    }
}

if (!function_exists('ga_rate_ok')) {
    /** True if fewer than $max of the given timestamps fall within the window. */
    function ga_rate_ok(array $timestamps, int $now, int $window, int $max): bool
    {
        $recent = 0;
        foreach ($timestamps as $t) {
            if (is_int($t) && $t > $now - $window) {
                $recent++;
            }
        }
        return $recent < $max;
    }
}
