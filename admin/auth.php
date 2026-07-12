<?php
/**
 * GreenArc admin - shared bootstrap: config, hardened session, CSRF, auth guard.
 * Included by every admin page. Not meant to be requested directly.
 */

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

$__cfgPath = __DIR__ . '/../config.php';
if (!file_exists($__cfgPath)) {
    http_response_code(500);
    exit('Admin is not configured yet. Create config.php from config.sample.php.');
}
$cfg = require $__cfgPath;

require __DIR__ . '/../lib/db.php';

// ---- hardened session ------------------------------------------------------
$__secure = !empty($cfg['https_only']);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/admin',
    'httponly' => true,
    'samesite' => 'Strict',
    'secure'   => $__secure,
]);
session_name('greenarc_admin');
session_start();

// ---- helpers ---------------------------------------------------------------
function admin_csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function admin_csrf_check(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function admin_is_logged_in(): bool
{
    return !empty($_SESSION['admin']);
}

function admin_require_login(): void
{
    if (!admin_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function admin_h($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/**
 * Simple file-based login throttle: max attempts per IP per window.
 * Returns true if the caller is allowed to attempt a login.
 */
function admin_login_allowed(string $ip, int $max = 8, int $window = 900): bool
{
    if ($ip === '') {
        return true;
    }
    $file = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR
          . 'greenarc_login_' . hash('sha256', 'ga-login|' . $ip) . '.json';
    $now = time();
    $times = [];
    $raw = @file_get_contents($file);
    if ($raw !== false) {
        $d = json_decode($raw, true);
        if (is_array($d)) {
            foreach ($d as $t) {
                if (is_int($t) && $t > $now - $window) {
                    $times[] = $t;
                }
            }
        }
    }
    return count($times) < $max;
}

function admin_login_record(string $ip, int $window = 900): void
{
    if ($ip === '') {
        return;
    }
    $file = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR
          . 'greenarc_login_' . hash('sha256', 'ga-login|' . $ip) . '.json';
    $now = time();
    $times = [];
    $raw = @file_get_contents($file);
    if ($raw !== false) {
        $d = json_decode($raw, true);
        if (is_array($d)) {
            foreach ($d as $t) {
                if (is_int($t) && $t > $now - $window) {
                    $times[] = $t;
                }
            }
        }
    }
    $times[] = $now;
    @file_put_contents($file, json_encode($times), LOCK_EX);
}
