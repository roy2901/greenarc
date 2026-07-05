<?php
/**
 * GreenArc Solutions, contact form handler
 * Sends inquiries via authenticated SMTP (PHPMailer).
 * Responds with JSON for AJAX; falls back to an HTML page for no-JS submits.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/lib/PHPMailer/Exception.php';
require __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/SMTP.php';

// ---- helpers -------------------------------------------------------------
$isAjax = (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
);

function respond(bool $ok, string $message, bool $isAjax): void
{
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($ok ? 200 : 400);
        echo json_encode(['ok' => $ok, 'message' => $message]);
        exit;
    }
    // No-JS fallback: simple HTML response
    $color = $ok ? '#123c26' : '#8a2020';
    $title = $ok ? 'Message sent' : 'Something went wrong';
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>' . $title . ', GreenArc Solutions</title>'
       . '<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:12vh auto;padding:0 24px;text-align:center">'
       . '<h1 style="color:' . $color . ';font-size:26px">' . $title . '</h1>'
       . '<p style="color:#444;font-size:16px;line-height:1.6">' . htmlspecialchars($message) . '</p>'
       . '<p><a href="/#contact" style="color:#123c26;font-weight:bold">← Back to GreenArc Solutions</a></p></div>';
    exit;
}

function clean(string $v): string
{
    return trim(str_replace(["\r", "\n", "%0a", "%0d"], ' ', $v));
}

// ---- only accept POST ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.', $isAjax);
}

// ---- honeypot: bots fill the hidden "website" field ----------------------
if (!empty($_POST['website'])) {
    // Pretend success so bots don't retry
    respond(true, 'Thank you, your message has been sent.', $isAjax);
}

// ---- collect + validate --------------------------------------------------
$name    = clean($_POST['name']    ?? '');
$email   = clean($_POST['email']   ?? '');
$company = clean($_POST['company'] ?? '');
$message = trim($_POST['message']  ?? '');

$errors = [];
if (mb_strlen($name) < 2)                              $errors[] = 'a valid name';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))        $errors[] = 'a valid email address';
if (mb_strlen($message) < 10)                          $errors[] = 'a short message';

if ($errors) {
    respond(false, 'Please provide ' . implode(', ', $errors) . '.', $isAjax);
}

// ---- load config ---------------------------------------------------------
$cfgPath = __DIR__ . '/config.php';
if (!file_exists($cfgPath)) {
    respond(false, 'The contact form is not configured yet. Please email finance@greenarc.solutions.', $isAjax);
}
$cfg = require $cfgPath;

// ---- build + send --------------------------------------------------------
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

    $mail->setFrom($cfg['from_email'], $cfg['from_name']);
    $mail->addAddress($cfg['to_email'], $cfg['to_name']);
    $mail->addReplyTo($email, $name); // replies go straight to the visitor

    $mail->Subject = $cfg['subject'] . ', ' . $name . ($company ? ' (' . $company . ')' : '');

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
    respond(false, 'We could not send your message right now. Please email finance@greenarc.solutions directly.', $isAjax);
}
