<?php
require __DIR__ . '/auth.php';
admin_require_login();
require __DIR__ . '/../lib/posts.php';

$pdo = greenarc_db($cfg);
$flash = '';

// delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (admin_csrf_check($_POST['csrf'] ?? null)) {
        if (ga_post_delete($pdo, (int) ($_POST['id'] ?? 0))) {
            $flash = 'Post deleted.';
        }
    }
}

$posts = ga_posts_all($pdo);
$token = admin_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Insights | GreenArc Admin</title>
<link rel="icon" type="image/png" sizes="64x64" href="/assets/img/favicon-64.png">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<header class="adm-top">
  <div class="adm-brand">Green<b>Arc</b> <span>Admin</span></div>
  <nav class="adm-nav">
    <a href="index.php">Leads</a>
    <a href="posts.php">Insights</a>
    <a href="/" target="_blank" rel="noopener">View site</a>
    <a href="logout.php" class="danger">Log out</a>
  </nav>
</header>
<main class="adm-main">
  <div class="head-row">
    <h1>Insights posts</h1>
    <a class="btn gold" href="post-edit.php">New post</a>
  </div>

  <?php if ($flash): ?><div class="alert ok"><?= admin_h($flash) ?></div><?php endif; ?>
  <?php if (!$pdo): ?>
    <div class="alert bad">Database not configured. Add DB credentials to config.php and import db/schema.sql.</div>
  <?php endif; ?>

  <div class="table-wrap">
    <table class="leads">
      <thead><tr><th>Title</th><th>Tag</th><th>Status</th><th>Updated</th><th>Slug</th><th></th></tr></thead>
      <tbody>
        <?php if (!$posts): ?>
          <tr><td colspan="6" class="empty">No posts yet. Create your first one.</td></tr>
        <?php else: foreach ($posts as $p): ?>
          <tr>
            <td><?= admin_h($p['title']) ?></td>
            <td><?= admin_h($p['tag'] ?? '') ?></td>
            <td><span class="badge <?= $p['status'] === 'published' ? 'b-replied' : 'b-read' ?>"><?= admin_h($p['status']) ?></span></td>
            <td class="nowrap"><?= admin_h(date('d M Y, H:i', strtotime($p['updated_at']))) ?></td>
            <td><?php if ($p['status'] === 'published'): ?><a href="/post.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" rel="noopener"><?= admin_h($p['slug']) ?></a><?php else: ?><?= admin_h($p['slug']) ?><?php endif; ?></td>
            <td class="nowrap">
              <a class="btn sm ghost" href="post-edit.php?id=<?= (int) $p['id'] ?>">Edit</a>
              <form method="post" class="inline" onsubmit="return confirm('Delete this post?')">
                <input type="hidden" name="csrf" value="<?= admin_h($token) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button type="submit" class="btn sm danger-btn">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
