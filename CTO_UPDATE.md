# CTO Update - GreenArc Solutions Website

*Prepared 12 July 2026. Repo: github.com/roy2901/greenarc. HEAD `df55aaf`.*

## TL;DR
- Marketing site for a bookkeeping firm (17 pages) plus a PHP/MySQL backend: contact form that stores leads and a password-protected admin dashboard.
- **Frontend is production-quality and effectively live** on GitHub Pages. **Backend is feature-complete in code but has never run anywhere.**
- **Biggest risk: hosting mismatch.** The live domain `greenarc.solutions` runs a **GoDaddy Website Builder** page (unrelated, no-code) that **cannot execute our PHP or MySQL**. None of our code is deployed to the real domain.
- No tests, no CI/CD, no PHP runtime used in dev. The backend is unverified: not lint-checked by an interpreter, never executed against a database.
- Secrets are handled correctly (nothing sensitive committed).

## What's built
**Frontend (static, done):** 17 hand-built HTML pages (home, services, industries, process, technology, about, contact, 6 Insights articles, privacy, terms, 404). One shared CSS file, vanilla JS (mobile nav, hover slideshows, form validation, scroll effects, WhatsApp/back-to-top). Real logo, favicons, OG image, most photos. Accessibility and responsive checks pass; relative paths so it renders on any host.

**Backend (PHP, code-complete, unrun):**
- `contact.php` - PHPMailer over SMTP; also inserts each lead into MySQL (best-effort).
- `lib/db.php` - PDO layer, prepared statements.
- `db/schema.sql` - single `leads` table.
- `admin/` - login (bcrypt, CSRF, 8/15min throttle, hardened sessions), dashboard (stats, search, filter, pagination, inline status updates), CSV export.
- `config.sample.php` - SMTP + DB + admin credentials template.

**Stack:** PHP 8 + PDO/MySQL, PHPMailer 6.9.1 (hand-vendored, no Composer), static HTML/CSS/vanilla JS. No package.json/lockfile. Infra config: only `.nojekyll`.

## Current state (deployment)
- **GitHub Pages (`roy2901.github.io/greenarc/`): LIVE and current.** Serves HEAD; assets at `?v=4`. This is a static preview only.
- **Production `greenarc.solutions`: NOT our site.** It returns 200 but is a GoDaddy Website Builder page ("generator: Go Daddy Website Builder 8.0"). `/contact.php` and `/admin/` return 404 there. Our repo has never been deployed to production.
- On GitHub Pages the PHP files are served as **readable source text** (inert, no secrets, repo is public anyway) - low severity, but `admin/index.php` etc. are viewable.
- Repo is fully in sync: local == origin/main, clean tree.

## Backend gaps (specific)
- **Never executed.** No PHP available in the dev environment; verified only by manual review and brace/lint-by-eye. `php -l` has not run on any file. High risk of a runtime error surfacing on first real deploy.
- **No hosting that can run it.** GoDaddy Website Builder is not PHP hosting. Needs real cPanel/LAMP (or a rewrite to serverless/static-form).
- **No tests, no CI.** Zero automated coverage; no GitHub Actions, no CodeQL/Semgrep despite being recommended in the repo's own SECURITY.md.
- **Rate limiting is per-IP file-based** (fine for spam, useless against distributed abuse; depends on Cloudflare, not yet in front).
- **Admin is single-user, config-file credentials.** No user table, no password reset, no audit log. Acceptable for one operator, not for a team.
- **DB migrations are a single .sql file** run by hand; no versioning.

## Does it meet requirements?
**No formal PRD/spec exists in the repo.** Judged against production-readiness for a small-firm marketing site with lead capture: **PARTIAL.**
- Frontend: **Yes** - polished, accessible, live (on Pages).
- Backend: **No, not in production** - the code is reasonable and security-aware, but it is unhosted, unexecuted, and the current domain physically cannot run it. Until that is resolved, the contact form and admin are non-functional in the real world (the form falls back to a `mailto:` link on static hosts).

## Next steps (prioritized)
1. **Resolve hosting (blocker).** Decide: buy real PHP/MySQL hosting (cPanel) for greenarc.solutions and repoint DNS off the GoDaddy builder, **or** drop the PHP backend and use a static form service (Formspree/Web3Forms) + a hosted DB. Everything else depends on this.
2. **First real backend run.** On a PHP host: set `config.php` secrets, import `schema.sql`, generate the admin bcrypt hash, then run the acceptance tests in FULLSTACK-SETUP.md. Expect and fix first-run bugs.
3. **Add CI.** PHP lint on push + CodeQL/Semgrep; catches the errors no one has been able to run locally.
4. **Point the domain + Cloudflare** in front for WAF/DDoS/SSL (per GO-LIVE-CPANEL.md).
5. **Close content gaps:** real social URLs, founder bio, remaining photos, and trim the tool/method lists to what the firm actually uses (currently client-facing claims that may overstate).
