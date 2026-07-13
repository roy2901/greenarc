# GreenArc - Full-Stack Setup (Leads Database + Admin Dashboard)

This adds a MySQL-backed leads store and a password-protected admin dashboard on top of
the existing site. Stack: PHP + MySQL (runs natively on cPanel). The marketing pages are
unchanged; the contact form now also saves each submission to the database (and still emails).

## What was added
```
db/schema.sql          MySQL table for leads
lib/db.php             PDO connection + safe insert (server-side only, web-blocked)
contact.php            now stores each lead in the DB (best-effort) in addition to emailing
admin/                 the dashboard app
  auth.php             hardened session, CSRF, login throttle, auth guard (include only)
  login.php            login screen (bcrypt verify, CSRF, rate-limited)
  index.php            dashboard: stats, search, filter, pagination, status updates
  export.php           CSV export of the current view
  logout.php           ends the session
  admin.css            brand-matched styles
  .htaccess            blocks direct access to auth.php, adds noindex
```

## One-time setup on cPanel

### 1. Create the database
1. cPanel > MySQL Databases.
2. Create a database, e.g. `greenarc_leads`.
3. Create a database user with a strong password.
4. Add that user to the database with ALL PRIVILEGES.
5. Note the final names (cPanel prefixes them, e.g. `cpaneluser_greenarc_leads`).

### 2. Import the schema
1. cPanel > phpMyAdmin > select your database.
2. Import tab > choose `db/schema.sql` > Go. This creates the `leads` table.

### 3. Fill in config.php
Add the database and admin values to `config.php` (copied from `config.sample.php`):
```php
'db_host' => 'localhost',
'db_name' => 'cpaneluser_greenarc_leads',
'db_user' => 'cpaneluser_dbuser',
'db_pass' => 'the-db-user-password',
```

### 4. Set the admin login
Choose a username and generate a bcrypt hash of your password. In cPanel > Terminal (or any
PHP CLI):
```
php -r "echo password_hash('YourStrongPassword', PASSWORD_DEFAULT), \"\n\";"
```
Copy the output into config.php:
```php
'admin_user'      => 'admin',
'admin_pass_hash' => '$2y$10$....the generated hash....',
'https_only'      => true,
```
If you cannot use the Terminal, tell me the password and I will generate the hash for you to paste (never commit the plain password).

### 5. Use it
- Visit `https://greenarc.solutions/admin/` and sign in.
- Dashboard shows totals, new/unread, and last 7 days.
- Search across name/email/company/message, filter by status, change a lead's status inline,
  and Export CSV (respects the current search/filter).

## Insights CMS (admin > Insights)
The admin dashboard can create and edit Insights articles, stored in the `posts` table.
- `admin/posts.php` lists posts; `admin/post-edit.php` creates/edits (title, slug, tag, excerpt,
  body, draft/published). Body is plain text; leave a blank line between paragraphs.
- Published posts render at `/post.php?slug=<slug>` using the site styling.
- Body is stored as text and rendered escaped, so there is no HTML/script injection risk.
- Follow-up (not done): the public Insights index (`insights.html`) and nav are still the static
  articles. To surface CMS posts in the listing/nav, convert `insights.html` to a PHP page that
  also loops `ga_posts_published()`. The single-post renderer already works today.

## Tests and CI
- `lib/validate.php` holds the pure contact-form logic (clean, validate, origin, rate window).
- `tests/ContactValidationTest.php` covers it with PHPUnit.
- GitHub Actions (`.github/workflows/ci.yml`) runs `php -l` on all PHP, PHPUnit, an em-dash guard,
  and an internal-link check on every push. Run tests locally with `composer install && vendor/bin/phpunit`.

## Security built in
- Passwords stored only as a bcrypt hash; verified with `password_verify`.
- Sessions: HttpOnly, SameSite=Strict, Secure (when `https_only` is true), id regenerated on login.
- CSRF tokens on the login and status-change forms.
- Login throttling: max 8 attempts per IP per 15 minutes.
- All database access uses PDO prepared statements (no SQL injection).
- All output is HTML-escaped.
- `config.php`, `/lib`, `/db`, `*.sql`, and `admin/auth.php` are blocked from the web by `.htaccess`.
- The leads store is best-effort: if the database is down, the contact form still emails.

## Local testing note
The admin and database require PHP + MySQL, so they run only on a PHP host (cPanel), not on
GitHub Pages or the static preview. On the live cPanel site, verify:
1. Submit the contact form, then confirm a new row appears in the dashboard and an email arrives.
2. Log in at /admin/, search/filter, change a status, and export CSV.
3. `curl -I https://greenarc.solutions/db/schema.sql` returns 403; `admin/auth.php` returns 403.
