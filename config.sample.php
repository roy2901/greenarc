<?php
/**
 * GreenArc Solutions - configuration
 * ----------------------------------
 * 1. Copy this file to `config.php` on the server.
 * 2. Fill in the real values below.
 * 3. `config.php` is blocked from web access by .htaccess - never commit it.
 */
return [

    // ---- Contact form recipient -------------------------------------------
    'to_email'    => 'finance@greenarc.solutions',
    'to_name'     => 'GreenArc Solutions',

    // ---- Outgoing SMTP (usually the same mailbox, made in cPanel) ----------
    'smtp_host'   => 'mail.greenarc.solutions',
    'smtp_user'   => 'finance@greenarc.solutions',
    'smtp_pass'   => 'YOUR_MAILBOX_PASSWORD_HERE',
    'smtp_port'   => 465,        // 465 = SSL, 587 = STARTTLS (tls)
    'smtp_secure' => 'ssl',
    'from_email'  => 'finance@greenarc.solutions',
    'from_name'   => 'GreenArc Website',
    'subject'     => 'New website inquiry',

    // ---- MySQL database (create in cPanel > MySQL Databases) --------------
    // Leave db_name empty to disable database storage (form will still email).
    'db_host'     => 'localhost',
    'db_name'     => '',                       // e.g. greenarc_leads
    'db_user'     => '',                       // e.g. greenarc_admin
    'db_pass'     => '',
    'db_charset'  => 'utf8mb4',

    // ---- Admin dashboard login --------------------------------------------
    // Username for /admin, plus a bcrypt hash of the password (never store the
    // plain password). Generate the hash once (see FULLSTACK-SETUP.md), e.g.
    //   php -r "echo password_hash('YourStrongPassword', PASSWORD_DEFAULT);"
    'admin_user'      => 'admin',
    'admin_pass_hash' => '$2y$10$REPLACE_WITH_A_REAL_BCRYPT_HASH_0000000000000000000',

    // Set true once you have confirmed HTTPS works (marks the session cookie Secure).
    'https_only'      => true,
];
