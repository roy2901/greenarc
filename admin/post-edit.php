<?php
require __DIR__ . '/auth.php';
admin_require_login();
require __DIR__ . '/../lib/posts.php';

$pdo = greenarc_db($cfg);
$error = '';
$id = (int) ($_GET['id'] ?? 0);

$post = ['id' => 0, 'slug' => '', 'title' => '', 'tag' => '', 'excerpt' => '', 'body' => '', 'status' => 'draft'];
if ($id > 0) {
    $existing = ga_post_by_id($pdo, $id);
    if ($existing) {
        $post = $existing;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Session expired. Please try again.';
        $post = array_merge($post, $_POST);
    } else {
        [$savedId, $err] = ga_post_save($pdo, [
            'id'      => (int) ($_POST['id'] ?? 0),
            'title'   => $_POST['title'] ?? '',
            'slug'    => $_POST['slug'] ?? '',
            'tag'     => $_POST['tag'] ?? '',
            'excerpt' => $_POST['excerpt'] ?? '',
            'body'    => $_POST['body'] ?? '',
            'status'  => $_POST['status'] ?? 'draft',
        ]);
        if ($err) {
            $error = $err;
            $post = array_merge($post, $_POST);
        } else {
            header('Location: posts.php');
            exit;
        }
    }
}
$token = admin_csrf_token();
$isNew = ((int) $post['id']) === 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title><?= $isNew ? 'New post' : 'Edit post' ?> | GreenArc Admin</title>
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
<main class="adm-main narrow">
  <div class="head-row">
    <h1><?= $isNew ? 'New post' : 'Edit post' ?></h1>
    <a class="btn sm ghost" href="posts.php">Back</a>
  </div>
  <?php if ($error): ?><div class="alert bad"><?= admin_h($error) ?></div><?php endif; ?>
  <?php if (!$pdo): ?><div class="alert bad">Database not configured.</div><?php endif; ?>

  <form method="post" class="post-form">
    <input type="hidden" name="csrf" value="<?= admin_h($token) ?>">
    <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">

    <label for="title">Title</label>
    <input type="text" id="title" name="title" value="<?= admin_h($post['title']) ?>" required maxlength="200">

    <label for="slug">Slug <span class="hint">(leave blank to auto-generate from the title)</span></label>
    <input type="text" id="slug" name="slug" value="<?= admin_h($post['slug']) ?>" maxlength="200" placeholder="e.g. cash-vs-accrual">

    <div class="two">
      <div>
        <label for="tag">Tag</label>
        <input type="text" id="tag" name="tag" value="<?= admin_h($post['tag'] ?? '') ?>" maxlength="60" placeholder="e.g. Reconciliation">
      </div>
      <div>
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
          <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        </select>
      </div>
    </div>

    <label for="excerpt">Excerpt <span class="hint">(one-line summary for the listing)</span></label>
    <input type="text" id="excerpt" name="excerpt" value="<?= admin_h($post['excerpt'] ?? '') ?>" maxlength="300">

    <label for="body">Body <span class="hint">(plain text; leave a blank line between paragraphs)</span></label>
    <textarea id="body" name="body" rows="18" required><?= admin_h($post['body']) ?></textarea>

    <div class="form-actions">
      <button type="submit" class="btn gold">Save</button>
      <a class="btn ghost" href="posts.php">Cancel</a>
    </div>
  </form>
</main>
</body>
</html>
