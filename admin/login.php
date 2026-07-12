<?php
require __DIR__ . '/auth.php';

if (admin_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (!admin_login_allowed($ip)) {
        $error = 'Too many attempts. Please wait a few minutes and try again.';
    } else {
        admin_login_record($ip);
        $user = trim((string) ($_POST['username'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $okUser = hash_equals((string) ($cfg['admin_user'] ?? ''), $user);
        $okPass = password_verify($pass, (string) ($cfg['admin_pass_hash'] ?? ''));
        if ($okUser && $okPass) {
            session_regenerate_id(true);
            $_SESSION['admin'] = $user;
            header('Location: index.php');
            exit;
        }
        $error = 'Invalid username or password.';
    }
}
$token = admin_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Admin Login | GreenArc Solutions</title>
<link rel="icon" type="image/png" sizes="64x64" href="/assets/img/favicon-64.png">
<link rel="stylesheet" href="admin.css">
</head>
<body class="login-body">
  <main class="login-card">
    <div class="login-brand">Green<b>Arc</b> <span>Admin</span></div>
    <h1>Sign in</h1>
    <?php if ($error): ?><p class="alert bad"><?= admin_h($error) ?></p><?php endif; ?>
    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= admin_h($token) ?>">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required autofocus>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
      <button type="submit" class="btn">Sign in</button>
    </form>
    <p class="login-foot"><a href="/">Back to website</a></p>
  </main>
</body>
</html>
