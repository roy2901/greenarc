<?php
/**
 * GreenArc - public renderer for a single CMS Insights post.
 * URL: /post.php?slug=my-post   (only published posts are shown)
 */

declare(strict_types=1);

require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/posts.php';

$cfgPath = __DIR__ . '/config.php';
$post = null;
if (file_exists($cfgPath)) {
    $cfg  = require $cfgPath;
    $pdo  = greenarc_db($cfg);
    $slug = ga_slugify((string) ($_GET['slug'] ?? ''));
    if ($slug !== '') {
        $post = ga_post_by_slug($pdo, $slug, true);
    }
}

if (!$post) {
    http_response_code(404);
    $f = __DIR__ . '/404.html';
    if (file_exists($f)) {
        readfile($f);
    } else {
        echo 'Not found.';
    }
    exit;
}

$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$title = $h($post['title']);
$tag   = $h($post['tag'] ?? 'Insights');
$desc  = $h($post['excerpt'] ?? $post['title']);
$slugE = $h($post['slug']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title ?> | GreenArc Solutions Insights</title>
<meta name="description" content="<?= $desc ?>">
<link rel="canonical" href="https://greenarc.solutions/post.php?slug=<?= $slugE ?>">
<meta name="theme-color" content="#123c26">
<meta property="og:type" content="article">
<meta property="og:title" content="<?= $title ?>">
<meta property="og:description" content="<?= $desc ?>">
<meta property="og:image" content="https://greenarc.solutions/assets/img/og-image.jpg">
<link rel="icon" type="image/png" sizes="64x64" href="assets/img/favicon-64.png">
<link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/styles.css?v=4">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<div class="utility">
  <div class="wrap">
    <a href="about.html">About</a>
    <a href="industries.html">Industries</a>
    <a href="tel:+919049046949">+91 904 904 69 49</a>
    <span class="divider"></span>
    <a href="mailto:finance@greenarc.solutions">finance@greenarc.solutions</a>
  </div>
</div>
<header>
  <div class="wrap nav">
    <a href="index.html" class="brand" aria-label="GreenArc Solutions home"><img class="logo" src="assets/img/logo-full.png" alt="GreenArc Solutions" width="65" height="58"></a>
    <nav class="menu" id="menu">
      <a href="services.html">Services</a>
      <a href="industries.html">Industries</a>
      <a href="process.html">Process</a>
      <a href="technology.html">Technology</a>
      <a href="insights.html" class="active">Insights</a>
      <a href="about.html">About</a>
      <a href="contact.html" class="nav-cta">Get in Touch
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </nav>
    <button class="burger" aria-label="Toggle menu" aria-controls="menu" aria-expanded="false"><span></span><span></span><span></span></button>
  </div>
</header>
<main id="main">
  <section class="page-hero">
    <div class="wrap">
      <div class="crumb"><a href="index.html">Home</a> / <a href="insights.html">Insights</a> / <?= $title ?></div>
      <div class="eyebrow"><?= $tag ?></div>
      <h1 class="display"><?= $title ?></h1>
    </div>
  </section>
  <div class="wrap">
    <article class="legal article">
      <?php if (!empty($post['excerpt'])): ?><p class="meta"><?= $h($post['excerpt']) ?></p><?php endif; ?>
      <?= ga_render_body((string) $post['body']) ?>
      <a class="back" href="insights.html">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        Back to Insights
      </a>
    </article>
  </div>
</main>
<footer>
  <div class="wrap">
    <div class="foot-bottom" style="border-top:none;padding-top:0">
      <p>&copy; <span id="year">2026</span> GreenArc Solutions. Bookkeeping &amp; Accounting Services.</p>
      <div class="links"><a href="/privacy.html">Privacy Policy</a><a href="/terms.html">Terms of Use</a></div>
    </div>
  </div>
</footer>
<script src="assets/js/main.js?v=4" defer></script>
</body>
</html>
