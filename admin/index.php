<?php
require __DIR__ . '/auth.php';
admin_require_login();

$pdo = greenarc_db($cfg);
$statuses = ['new', 'read', 'replied', 'archived'];
$flash = '';

// ---- handle status update --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    if (!admin_csrf_check($_POST['csrf'] ?? null)) {
        $flash = 'Session expired, change not saved.';
    } elseif ($pdo) {
        $id = (int) ($_POST['id'] ?? 0);
        $new = (string) ($_POST['status'] ?? '');
        if ($id > 0 && in_array($new, $statuses, true)) {
            try {
                $s = $pdo->prepare('UPDATE leads SET status = :s WHERE id = :id');
                $s->execute([':s' => $new, ':id' => $id]);
                $flash = 'Lead #' . $id . ' marked ' . $new . '.';
            } catch (Throwable $e) {
                $flash = 'Could not update the lead.';
            }
        }
    }
}

// ---- filters ---------------------------------------------------------------
$q        = trim((string) ($_GET['q'] ?? ''));
$fstatus  = (string) ($_GET['status'] ?? '');
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 20;
$offset   = ($page - 1) * $perPage;

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

$leads = [];
$total = 0;
$stats = ['total' => 0, 'new' => 0, 'week' => 0];
$dbError = false;

if ($pdo) {
    try {
        $c = $pdo->prepare("SELECT COUNT(*) FROM leads $whereSql");
        $c->execute($args);
        $total = (int) $c->fetchColumn();

        $sql = "SELECT * FROM leads $whereSql ORDER BY created_at DESC LIMIT :lim OFFSET :off";
        $st = $pdo->prepare($sql);
        foreach ($args as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        $leads = $st->fetchAll();

        $stats['total'] = (int) $pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn();
        $stats['new']   = (int) $pdo->query("SELECT COUNT(*) FROM leads WHERE status='new'")->fetchColumn();
        $stats['week']  = (int) $pdo->query("SELECT COUNT(*) FROM leads WHERE created_at >= (NOW() - INTERVAL 7 DAY)")->fetchColumn();
    } catch (Throwable $e) {
        $dbError = true;
        error_log('GreenArc admin query failed: ' . $e->getMessage());
    }
}

$pages = max(1, (int) ceil($total / $perPage));
$token = admin_csrf_token();

function qs(array $extra = []): string
{
    $base = ['q' => $_GET['q'] ?? '', 'status' => $_GET['status'] ?? '', 'page' => $_GET['page'] ?? 1];
    return htmlspecialchars(http_build_query(array_merge($base, $extra)), ENT_QUOTES, 'UTF-8');
}
$badge = ['new' => 'b-new', 'read' => 'b-read', 'replied' => 'b-replied', 'archived' => 'b-arch'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Leads | GreenArc Admin</title>
<link rel="icon" type="image/png" sizes="64x64" href="/assets/img/favicon-64.png">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<header class="adm-top">
  <div class="adm-brand">Green<b>Arc</b> <span>Admin</span></div>
  <nav class="adm-nav">
    <a href="/" target="_blank" rel="noopener">View site</a>
    <a href="logout.php" class="danger">Log out</a>
  </nav>
</header>

<main class="adm-main">
  <h1>Leads</h1>

  <?php if ($flash): ?><div class="alert ok"><?= admin_h($flash) ?></div><?php endif; ?>
  <?php if (!$pdo): ?>
    <div class="alert bad">The database is not configured. Add db_name, db_user, and db_pass to config.php and import db/schema.sql. The contact form still sends email in the meantime.</div>
  <?php elseif ($dbError): ?>
    <div class="alert bad">Could not read the leads table. Confirm the schema was imported (db/schema.sql).</div>
  <?php endif; ?>

  <section class="stat-row">
    <div class="stat"><span class="s-num"><?= (int) $stats['total'] ?></span><span class="s-lbl">Total leads</span></div>
    <div class="stat"><span class="s-num"><?= (int) $stats['new'] ?></span><span class="s-lbl">New / unread</span></div>
    <div class="stat"><span class="s-num"><?= (int) $stats['week'] ?></span><span class="s-lbl">Last 7 days</span></div>
  </section>

  <form class="filters" method="get">
    <input type="search" name="q" value="<?= admin_h($q) ?>" placeholder="Search name, email, company, message">
    <select name="status">
      <option value="">All statuses</option>
      <?php foreach ($statuses as $s): ?>
        <option value="<?= $s ?>" <?= $fstatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn sm">Filter</button>
    <a class="btn sm ghost" href="index.php">Reset</a>
    <a class="btn sm gold" href="export.php?<?= qs() ?>">Export CSV</a>
  </form>

  <div class="table-wrap">
    <table class="leads">
      <thead>
        <tr><th>#</th><th>Date</th><th>Name</th><th>Email</th><th>Company</th><th>Message</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$leads): ?>
          <tr><td colspan="8" class="empty">No leads found.</td></tr>
        <?php else: foreach ($leads as $l): ?>
          <tr>
            <td><?= (int) $l['id'] ?></td>
            <td class="nowrap"><?= admin_h(date('d M Y, H:i', strtotime($l['created_at']))) ?></td>
            <td><?= admin_h($l['name']) ?></td>
            <td><a href="mailto:<?= admin_h($l['email']) ?>"><?= admin_h($l['email']) ?></a></td>
            <td><?= admin_h($l['company'] ?? '') ?></td>
            <td class="msg"><?= nl2br(admin_h(mb_strimwidth($l['message'], 0, 240, '...'))) ?></td>
            <td><span class="badge <?= $badge[$l['status']] ?? '' ?>"><?= admin_h($l['status']) ?></span></td>
            <td class="nowrap">
              <form method="post" class="inline">
                <input type="hidden" name="csrf" value="<?= admin_h($token) ?>">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                <select name="status" onchange="this.form.submit()">
                  <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $l['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <nav class="pager">
    <?php if ($page > 1): ?><a href="?<?= qs(['page' => $page - 1]) ?>">Prev</a><?php endif; ?>
    <span>Page <?= $page ?> of <?= $pages ?></span>
    <?php if ($page < $pages): ?><a href="?<?= qs(['page' => $page + 1]) ?>">Next</a><?php endif; ?>
  </nav>
  <?php endif; ?>
</main>
</body>
</html>
