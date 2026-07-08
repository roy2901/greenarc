<?php
/**
 * GreenArc Solutions - contact form handler
 * Sends inquiries via authenticated SMTP (PHPMailer).
 * Responds with JSON for AJAX; falls back to an HTML page for no-JS submits.
 *
 * Hardening layers (in order): method check, response headers, honeypot,
 * time-trap, same-origin check, rate limit, validation with length caps.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/lib/PHPMailer/Exception.php';
require __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/SMTP.php';

// ---- response hardening ----------------------------------------------------
ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

// ---- helpers -------------------------------------------------------------
$isAjax = (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
);

function respond(bool $ok, string $message, bool $isAjax, int $httpCode = 0): void
{
    if ($httpCode === 0) {
        $httpCode = $ok ? 200 : 400;
    }
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($httpCode);
        echo json_encode(['ok' => $ok, 'message' => $message]);
        exit;
    }
    // No-JS fallback: simple HTML response
    http_response_code($httpCode);
    $color = $ok ? '#123c26' : '#8a2020';
    $title = $ok ? 'Message sent' : 'Something went wrong';
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>' . $title . ' | GreenArc Solutions</title>'
       . '<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:12vh auto;padding:0 24px;text-align:center">'
       . '<h1 style="color:' . $color . ';font-size:26px">' . $title . '</h1>'
       . '<p style="color:#444;font-size:16px;line-height:1.6">' . htmlspecialchars($message) . '</p>'
       . '<p><a href="/#contact" style="color:#123c26;font-weight:bold">Back to GreenArc Solutions</a></p></div>';
    exit;
}

function clean(string $v): string
{
    return trim(str_replace(["\r", "\n", "%0a", "%0d"], ' ', $v));
}

// ---- only accept POST ------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.', $isAjax, 405);
}

// ---- honeypot: bots fill the hidden "website" field -------------------------
if (!empty($_POST['website'])) {
    // Pretend success so bots do not retry
    respond(true, 'Thank you, your message has been sent.', $isAjax);
}

// ---- time-trap: JS reports elapsed ms between page load and submit ----------
// Real visitors take longer than 3 seconds; headless bots submit instantly.
// The field is absent for no-JS visitors, so they are never blocked by this.
if (isset($_POST['ts']) && $_POST['ts'] !== '' && ctype_digit((string) $_POST['ts'])) {
    if ((int) $_POST['ts'] < 3000) {
        respond(true, 'Thank you, your message has been sent.', $isAjax);
    }
}

// ---- same-origin check ------------------------------------------------------
// Reject cross-site POSTs. Requests without Origin/Referer pass (some privacy
// browsers strip them), so no legitimate visitor is ever locked out.
$originHost = '';
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $originHost = strtolower((string) parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST));
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $originHost = strtolower((string) parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST));
}
if ($originHost !== '') {
    $selfHost = strtolower(explode(':', (string) ($_SERVER['HTTP_HOST'] ?? ''))[0]);
    $allowed  = [$selfHost, 'greenarc.solutions', 'www.greenarc.solutions'];
    if (!in_array($originHost, $allowed, true)) {
        respond(false, 'Cross-site requests are not allowed.', $isAjax, 403);
    }
}

// ---- rate limit: 5 submissions per 15 minutes per IP ------------------------
// File-based sliding window in the system temp dir. Fails open: if storage is
// unavailable, real clients are never blocked by an infrastructure quirk.
$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
if ($ip !== '') {
    $rlFile = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR
            . 'greenarc_rl_' . hash('sha256', 'ga-rl-2026|' . $ip) . '.json';
    $now = time();
    $windowSeconds = 900;
    $maxRequests = 5;
    $times = [];
    $raw = @file_get_contents($rlFile);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $t) {
                if (is_int($t) && $t > $now - $windowSeconds) {
                    $times[] = $t;
                }
            }
        }
    }
    if (count($times) >= $maxRequests) {
        respond(false, 'You have reached the message limit for now. Please try again in a little while, or email finance@greenarc.solutions directly.', $isAjax, 429);
    }
    $times[] = $now;
    @file_put_contents($rlFile, json_encode($times), LOCK_EX);
}

// ---- collect + validate ------------------------------------------------------
$name    = clean((string) ($_POST['name']    ?? ''));
$email   = clean((string) ($_POST['email']   ?? ''));
$company = clean((string) ($_POST['company'] ?? ''));
$message = trim((string) ($_POST['message']  ?? ''));

$errors = [];
if (mb_strlen($name) < 2)                              $errors[] = 'a valid name';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))        $errors[] = 'a valid email address';
if (mb_strlen($message) < 10)                          $errors[] = 'a short message';

if (mb_strlen($name) > 100)      $errors[] = 'a name under 100 characters';
if (mb_strlen($email) > 200)     $errors[] = 'an email under 200 characters';
if (mb_strlen($company) > 150)   $errors[] = 'a company name under 150 characters';
if (mb_strlen($message) > 5000)  $errors[] = 'a message under 5000 characters';

if ($errors) {
    respond(false, 'Please provide ' . implode(', ', $errors) . '.', $isAjax);
}

// ---- load config -------------------------------------------------------------
$cfgPath = __DIR__ . '/config.php';
if (!file_exists($cfgPath)) {
    respond(false, 'The contact form is not configured yet. Please email finance@greenarc.solutions.', $isAjax, 500);
}
$cfg = require $cfgPath;

// ---- build + send --------------------------------------------------------------
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $cfg['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $cfg['smtp_user'];
    $mail->Password   = $cfg['smtp_pass'];
    $mail->SMTPSecure = $cfg['smtp_secure']; // 'ssl' | 'tls'
    $mail->Port       = (int) $cfg['smtp_port'];
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 15;

    $mail->setFrom($cfg['from_email'], $cfg['from_name']);
    $mail->addAddress($cfg['to_email'], $cfg['to_name']);
    $mail->addReplyTo($email, $name); // replies go straight to the visitor

    $mail->Subject = $cfg['subject'] . ' | ' . $name . ($company ? ' (' . $company . ')' : '');

    $safeMsg = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $mail->isHTML(true);
    $mail->Body =
        '<div style="font-family:Arial,Helvetica,sans-serif;color:#1c2620;font-size:15px;line-height:1.6">'
        . '<h2 style="color:#123c26;margin:0 0 14px">New website inquiry</h2>'
        . '<p><strong>Name:</strong> ' . htmlspecialchars($name) . '</p>'
        . '<p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>'
        . ($company ? '<p><strong>Company:</strong> ' . htmlspecialchars($company) . '</p>' : '')
        . '<p><strong>Message:</strong></p><p>' . $safeMsg . '</p>'
        . '<hr style="border:none;border-top:1px solid #e2e8e2;margin:18px 0">'
        . '<p style="color:#5c675f;font-size:13px">Sent from greenarc.solutions contact form.</p></div>';
    $mail->AltBody =
        "New website inquiry\n\n"
        . "Name: $name\nEmail: $email\n"
        . ($company ? "Company: $company\n" : '')
        . "\nMessage:\n$message\n";

    $mail->send();
    respond(true, 'Thank you, your message has been sent. We\'ll be in touch shortly.', $isAjax);
} catch (Exception $e) {
    error_log('GreenArc contact form error: ' . $mail->ErrorInfo);
    respond(false, 'We could not send your message right now. Please email finance@greenarc.solutions directly.', $isAjax, 500);
}
