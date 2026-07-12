<?php
require __DIR__ . '/auth.php';
admin_require_login();

$pdo = greenarc_db($cfg);
if (!$pdo) {
    http_response_code(503);
    exit('Database not configured.');
}

$statuses = ['new', 'read', 'replied', 'archived'];
$q       = trim((string) ($_GET['q'] ?? ''));
$fstatus = (string) ($_GET['status'] ?? '');

$where = [];
$args  = [];
if ($q !== '') {
    $where[] = '(name LIKE :q OR email LIKE :q OR company LIKE :q OR message LIKE :q)';
    $args[':q'] = '%' . $q . '%';
}
if (in_array($fstatus, $statuses, true)) {
    $where[] = 'status = :st';
    $args[':st'] = $fstatus;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    $st = $pdo->prepare("SELECT id, created_at, name, email, company, message, status, source FROM leads $whereSql ORDER BY created_at DESC");
    $st->execute($args);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Export failed.');
}

$filename = 'greenarc-leads-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
fprintf($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
fputcsv($out, ['ID', 'Date', 'Name', 'Email', 'Company', 'Message', 'Status', 'Source']);
while ($row = $st->fetch()) {
    fputcsv($out, [
        $row['id'], $row['created_at'], $row['name'], $row['email'],
        $row['company'], $row['message'], $row['status'], $row['source'],
    ]);
}
fclose($out);
