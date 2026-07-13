<?php
/**
 * GreenArc Solutions - contact form handler
 * Emails submissions (PHPMailer/SMTP) and stores them in MySQL (best-effort).
 * Hardening: method check, headers, honeypot, time-trap, same-origin, rate limit,
 * length-capped validation. Pure logic lives in lib/validate.php (unit tested).
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/lib/PHPMailer/Exception.php';
require __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/SMTP.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/validate.php';

// ---- response hardening ----------------------------------------------------
ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

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

// ---- only accept POST ------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.', $isAjax, 405);
}

// ---- honeypot --------------------------------------------------------------
if (!empty($_POST['website'])) {
    respond(true, 'Thank you, your message has been sent.', $isAjax);
}

// ---- time-trap (JS reports elapsed ms; absent for no-JS visitors) ----------
if (isset($_POST['ts']) && $_POST['ts'] !== '' && ctype_digit((string) $_POST['ts'])) {
    if ((int) $_POST['ts'] < 3000) {
        respond(true, 'Thank you, your message has been sent.', $isAjax);
    }
}

// ---- same-origin -----------------------------------------------------------
$selfHost = strtolower(explode(':', (string) ($_SERVER['HTTP_HOST'] ?? ''))[0]);
$allowed  = [$selfHost, 'greenarc.solutions', 'www.greenarc.solutions'];
if (!ga_origin_allowed($_SERVER['HTTP_ORIGIN'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, $allowed)) {
    respond(false, 'Cross-site requests are not allowed.', $isAjax, 403);
}

// ---- rate limit: 5 per 15 minutes per IP (file-based, fail-open) -----------
$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
if ($ip !== '') {
    $rlFile = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR
            . 'greenarc_rl_' . hash('sha256', 'ga-rl-2026|' . $ip) . '.json';
    $now = time();
    $window = 900;
    $max = 5;
    $times = [];
    $raw = @file_get_contents($rlFile);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $t) {
                if (is_int($t) && $t > $now - $window) {
                    $times[] = $t;
                }
            }
        }
    }
    if (!ga_rate_ok($times, $now, $window, $max)) {
        respond(false, 'You have reached the message limit for now. Please try again in a little while, or email finance@greenarc.solutions directly.', $isAjax, 429);
    }
    $times[] = $now;
    @file_put_contents($rlFile, json_encode($times), LOCK_EX);
}

// ---- collect + validate ----------------------------------------------------
$name    = ga_clean((string) ($_POST['name']    ?? ''));
$email   = ga_clean((string) ($_POST['email']   ?? ''));
$company = ga_clean((string) ($_POST['company'] ?? ''));
$message = trim((string) ($_POST['message']  ?? ''));

$errors = ga_validate_contact([
    'name' => $name, 'email' => $email, 'company' => $company, 'message' => $message,
]);
if ($errors) {
    respond(false, 'Please provide ' . implode(', ', $errors) . '.', $isAjax);
}

// ---- load config -----------------------------------------------------------
$cfgPath = __DIR__ . '/config.php';
if (!file_exists($cfgPath)) {
    respond(false, 'The contact form is not configured yet. Please email finance@greenarc.solutions.', $isAjax, 500);
}
$cfg = require $cfgPath;

// ---- store the lead (best-effort; a DB outage never blocks the email) ------
$pdo = greenarc_db($cfg);
greenarc_store_lead($pdo, [
    'name'       => $name,
    'email'      => $email,
    'company'    => $company,
    'message'    => $message,
    'source'     => 'website',
    'ip_hash'    => $ip !== '' ? hash('sha256', 'ga-ip|' . $ip) : null,
    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
]);

// ---- build + send ----------------------------------------------------------
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $cfg['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $cfg['smtp_user'];
    $mail->Password   = $cfg['smtp_pass'];
    $mail->SMTPSecure = $cfg['smtp_secure'];
    $mail->Port       = (int) $cfg['smtp_port'];
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 15;

    $mail->setFrom($cfg['from_email'], $cfg['from_name']);
    $mail->addAddress($cfg['to_email'], $cfg['to_name']);
    $mail->addReplyTo($email, $name);

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
