<?php
/**
 * GreenArc - Insights CMS data layer (posts). PDO, prepared statements.
 * Body is stored as plain text and rendered escaped (no HTML injection).
 */

declare(strict_types=1);

function ga_slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    return trim($s, '-');
}

/** Escape body and turn blank-line-separated blocks into paragraphs. */
function ga_render_body(string $body): string
{
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $blocks = preg_split('/\n{2,}/', trim($body)) ?: [];
    $html = '';
    foreach ($blocks as $b) {
        $b = trim($b);
        if ($b === '') {
            continue;
        }
        $html .= '<p>' . nl2br(htmlspecialchars($b, ENT_QUOTES, 'UTF-8')) . "</p>\n";
    }
    return $html;
}

function ga_posts_all(?PDO $pdo): array
{
    if (!$pdo) {
        return [];
    }
    try {
        return $pdo->query('SELECT id, slug, title, tag, status, updated_at FROM posts ORDER BY updated_at DESC')->fetchAll();
    } catch (Throwable $e) {
        error_log('posts_all: ' . $e->getMessage());
        return [];
    }
}

function ga_posts_published(?PDO $pdo): array
{
    if (!$pdo) {
        return [];
    }
    try {
        $s = $pdo->query("SELECT slug, title, tag, excerpt, created_at FROM posts WHERE status='published' ORDER BY created_at DESC");
        return $s->fetchAll();
    } catch (Throwable $e) {
        error_log('posts_published: ' . $e->getMessage());
        return [];
    }
}

function ga_post_by_slug(?PDO $pdo, string $slug, bool $publishedOnly = true): ?array
{
    if (!$pdo) {
        return null;
    }
    try {
        $sql = 'SELECT * FROM posts WHERE slug = :slug' . ($publishedOnly ? " AND status='published'" : '') . ' LIMIT 1';
        $st = $pdo->prepare($sql);
        $st->execute([':slug' => $slug]);
        $row = $st->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        error_log('post_by_slug: ' . $e->getMessage());
        return null;
    }
}

function ga_post_by_id(?PDO $pdo, int $id): ?array
{
    if (!$pdo) {
        return null;
    }
    try {
        $st = $pdo->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $st->execute([':id' => $id]);
        $row = $st->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Insert or update a post. Returns [id, error]. Ensures a unique slug.
 */
function ga_post_save(?PDO $pdo, array $d): array
{
    if (!$pdo) {
        return [0, 'Database not available.'];
    }
    $id      = (int) ($d['id'] ?? 0);
    $title   = trim((string) ($d['title'] ?? ''));
    $tag     = trim((string) ($d['tag'] ?? ''));
    $excerpt = trim((string) ($d['excerpt'] ?? ''));
    $body    = (string) ($d['body'] ?? '');
    $status  = in_array(($d['status'] ?? 'draft'), ['draft', 'published'], true) ? $d['status'] : 'draft';
    $slug    = ga_slugify((string) ($d['slug'] ?? '') !== '' ? (string) $d['slug'] : $title);

    if ($title === '' || $slug === '' || trim($body) === '') {
        return [0, 'Title and body are required.'];
    }

    try {
        // ensure slug uniqueness (append -2, -3, ... if needed)
        $base = $slug;
        $n = 1;
        while (true) {
            $chk = $pdo->prepare('SELECT id FROM posts WHERE slug = :s AND id <> :id LIMIT 1');
            $chk->execute([':s' => $slug, ':id' => $id]);
            if (!$chk->fetch()) {
                break;
            }
            $n++;
            $slug = $base . '-' . $n;
        }

        if ($id > 0) {
            $st = $pdo->prepare('UPDATE posts SET slug=:slug, title=:title, tag=:tag, excerpt=:excerpt, body=:body, status=:status WHERE id=:id');
            $st->execute([':slug' => $slug, ':title' => $title, ':tag' => $tag ?: null, ':excerpt' => $excerpt ?: null, ':body' => $body, ':status' => $status, ':id' => $id]);
            return [$id, ''];
        }
        $st = $pdo->prepare('INSERT INTO posts (slug, title, tag, excerpt, body, status) VALUES (:slug,:title,:tag,:excerpt,:body,:status)');
        $st->execute([':slug' => $slug, ':title' => $title, ':tag' => $tag ?: null, ':excerpt' => $excerpt ?: null, ':body' => $body, ':status' => $status]);
        return [(int) $pdo->lastInsertId(), ''];
    } catch (Throwable $e) {
        error_log('post_save: ' . $e->getMessage());
        return [0, 'Could not save the post.'];
    }
}

function ga_post_delete(?PDO $pdo, int $id): bool
{
    if (!$pdo) {
        return false;
    }
    try {
        $pdo->prepare('DELETE FROM posts WHERE id = :id')->execute([':id' => $id]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
