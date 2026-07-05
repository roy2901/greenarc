<?php
/**
 * GreenArc Solutions, mail configuration
 * ---------------------------------------
 * 1. Copy this file to `config.php` on the server.
 * 2. Fill in the real values below.
 * 3. `config.php` is blocked from web access by .htaccess, never commit it.
 */
return [
    // Where inquiries are delivered
    'to_email'    => 'finance@greenarc.solutions',
    'to_name'     => 'GreenArc Solutions',

    // SMTP account used to SEND (usually the same mailbox, created in cPanel > Email Accounts)
    'smtp_host'   => 'mail.greenarc.solutions', // or your cPanel server's mail host
    'smtp_user'   => 'finance@greenarc.solutions',
    'smtp_pass'   => 'YOUR_MAILBOX_PASSWORD_HERE',
    'smtp_port'   => 465,        // 465 = SMTPS (ssl), 587 = STARTTLS (tls)
    'smtp_secure' => 'ssl',      // 'ssl' for 465, 'tls' for 587

    // The From address shown on the email (should be on YOUR domain for deliverability)
    'from_email'  => 'finance@greenarc.solutions',
    'from_name'   => 'GreenArc Website',

    // Subject prefix
    'subject'     => 'New website inquiry',
];
