# GreenArc Solutions - Security Runbook

This checklist covers the parts of security that live in accounts and DNS, not in code.
Work through it once before go-live, then review the maintenance section each quarter.
This file is blocked from public web access by `.htaccess`; it contains no secrets and is safe to keep in the repo.

## What the code already does
- Forces HTTPS and a single canonical host; sends HSTS.
- Security headers: CSP, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy.
- Directory listing off; `config.php`, `/lib`, dotfiles, `.git`, and internal docs blocked from the web.
- No PHP execution allowed under `/assets`; request body capped at 1 MB.
- Contact form: POST-only, honeypot, JS time-trap, same-origin check, rate limit (5 per 15 min per IP), input length caps, email header-injection stripping, generic error messages, SMTP over SSL.
- Secrets never committed: `config.php` is gitignored and created only on the server.

## One-time setup before go-live

### cPanel account
- [ ] Set a unique, strong password (not reused anywhere else).
- [ ] Turn on two-factor authentication (Security > Two-Factor Authentication).
- [ ] Confirm ModSecurity (WAF) is enabled for the domain.
- [ ] Turn on automatic backups; confirm at least one restore point exists.
- [ ] Set PHP version to 8.1 or newer (MultiPHP Manager).
- [ ] Run AutoSSL and confirm auto-renewal is on (SSL/TLS Status).

### Mailbox (finance@greenarc.solutions)
- [ ] Rotate the mailbox password right before you type it into `config.php` on the server.
- [ ] Never reuse that password anywhere else.
- [ ] `config.php` lives only on the server (copied from `config.sample.php`); never commit it.

### DNS / email authentication (also improves deliverability)
- [ ] SPF record published.
- [ ] DKIM enabled and published (cPanel > Email Deliverability > Manage).
- [ ] DMARC record published, for example: `v=DMARC1; p=quarantine; rua=mailto:finance@greenarc.solutions`.

### GitHub (repo is public: github.com/roy2901/greenarc)
- [ ] Two-factor authentication on the GitHub account.
- [ ] Settings > Code security: enable Dependabot alerts and secret scanning.
- [ ] Confirm no secret was ever committed: `git log --all -- config.php` returns nothing.
- [ ] Remember the repo is public: never commit `config.php`, passwords, or client data.

## Server-scope hardening (ask host or set in WHM if you have it)
- [ ] `TraceEnable off` (blocks HTTP TRACE). Cannot be set in `.htaccess`; set at server config or ask the host.

## Post-deploy verification (run once after uploading)
From any machine, replace the domain if testing a staging URL:
1. `curl -I https://greenarc.solutions/config.php` returns 403.
2. `curl -I https://greenarc.solutions/.git/HEAD` returns 404.
3. `curl -I https://greenarc.solutions/README-DEPLOY.md` returns 403.
4. `curl -I http://greenarc.solutions/` returns 301 to https.
5. Scan https://securityheaders.com for greenarc.solutions (aim for A or higher).
6. Contact form live tests:
   - Normal submit delivers an email to finance@greenarc.solutions.
   - Six rapid submits: the sixth returns the rate-limit message.
   - `curl -X POST -H "Origin: https://evil.example" https://greenarc.solutions/contact.php` is rejected (403).
   - An oversized message (over 5000 characters) is rejected with a validation message.

## Maintenance (quarterly)
- [ ] Check PHPMailer for security releases (currently pinned to 6.9.1 in `/lib/PHPMailer`). Update if a security fix ships.
- [ ] Re-run the securityheaders.com scan after any `.htaccess` change.
- [ ] Review cPanel and GitHub login/activity for anything unexpected.
- [ ] Confirm backups are still running and SSL is still valid.

## Optional stronger protection (recommended, needs your accounts)
- Cloudflare free tier in front of greenarc.solutions: managed WAF, DDoS protection, bot fight mode. Requires pointing DNS at Cloudflare.
- Cloudflare Turnstile on the contact form (stronger than the honeypot). Requires a site key.
- UptimeRobot monitor on the live domain to alert if the site or certificate goes down.
