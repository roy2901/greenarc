<?php
/**
 * GreenArc Solutions - database access layer (PDO).
 * Returns a configured PDO instance, or null if the database is not configured
 * or unreachable (callers must treat null as "storage unavailable" and continue).
 */

declare(strict_types=1);

function greenarc_db(array $cfg): ?PDO
{
    static $pdo = null;
    static $tried = false;

    if ($tried) {
        return $pdo;
    }
    $tried = true;

    if (empty($cfg['db_name']) || empty($cfg['db_user'])) {
        return null; // database intentionally disabled
    }

    $charset = $cfg['db_charset'] ?? 'utf8mb4';
    $dsn = 'mysql:host=' . ($cfg['db_host'] ?? 'localhost')
         . ';dbname=' . $cfg['db_name']
         . ';charset=' . $charset;

    try {
        $pdo = new PDO($dsn, $cfg['db_user'], (string) ($cfg['db_pass'] ?? ''), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (Throwable $e) {
        error_log('GreenArc DB connect failed: ' . $e->getMessage());
        $pdo = null;
    }

    return $pdo;
}

/**
 * Insert a lead. Returns the new id, or null on failure (never throws to caller).
 */
function greenarc_store_lead(?PDO $pdo, array $lead): ?int
{
    if (!$pdo) {
        return null;
    }
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO leads (name, email, company, message, source, ip_hash, user_agent)
             VALUES (:name, :email, :company, :message, :source, :ip_hash, :user_agent)'
        );
        $stmt->execute([
            ':name'       => $lead['name'],
            ':email'      => $lead['email'],
            ':company'    => $lead['company'] !== '' ? $lead['company'] : null,
            ':message'    => $lead['message'],
            ':source'     => $lead['source'] ?? 'website',
            ':ip_hash'    => $lead['ip_hash'] ?? null,
            ':user_agent' => $lead['user_agent'] ?? null,
        ]);
        return (int) $pdo->lastInsertId();
    } catch (Throwable $e) {
        error_log('GreenArc DB insert failed: ' . $e->getMessage());
        return null;
    }
}
