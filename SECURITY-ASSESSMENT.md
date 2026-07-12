# GreenArc Solutions - Website Security Assessment (Internal)

**Date:** 6 July 2026
**Target:** greenarc.solutions website codebase and repository (github.com/roy2901/greenarc)
**Assessment type:** Internal white-box secure-code review and safe probing
**Assessor:** Internal review

---

## 1. Nature of this assessment (please read)
This is an **internal, white-box self-assessment and secure-code review**. It is **not** a
certified third-party penetration test or a formal VAPT audit. No denial-of-service or load
(stress) testing was performed, because that would risk harming shared hosting infrastructure
and third-party services and is out of scope. Live dynamic application security testing (DAST)
against the production domain is scheduled for after the site is deployed to cPanel.

## 2. Executive summary
**Overall risk rating: LOW.**

The website is a static front end (HTML, CSS, JavaScript) plus a single server-side endpoint,
`contact.php`, which emails contact-form submissions using PHPMailer over authenticated SMTP.
There is **no database, no user login, and no stored client data** (submissions are emailed,
not retained), which keeps the sensitive-data attack surface small by design.

The code review found **no injection, cross-site scripting, or secret-exposure defects**.
The contact endpoint has multiple layered anti-abuse controls, and the server configuration
applies strong transport and header hardening. The residual risks that remain are inherent to
any public contact form and are addressed by the planned Cloudflare Web Application Firewall.

## 3. Scope and methodology
- **Static analysis (SAST):** manual secure-code review plus a dangerous-sink pattern scan of
  `contact.php` and `assets/js/main.js`.
- **Secret scan:** review of git-tracked files and git history for credentials.
- **Configuration review:** `.htaccess`, `assets/.htaccess`, and secret-handling (`config.php`).
- **Safe dynamic probing (DAST-style):** single, non-flooding HTTP requests against the live
  GitHub Pages URL to check for file exposure and response behaviour.

## 4. Findings

| ID | Area | Severity | Status | Notes |
|----|------|----------|--------|-------|
| F1 | Secrets management | Info | PASS | `config.php` is untracked; no hardcoded secrets in tracked files or git history; sample file uses a placeholder password. |
| F2 | PHP injection sinks | Info | PASS | No `eval`/`exec`/`system`/`unserialize`/`extract`. All `$_POST` input is sanitized; `$_SERVER` values used safely. |
| F3 | Cross-site scripting (JS) | Info | PASS | `innerHTML` is used only with static string literals; all user-facing text uses `textContent`. |
| F4 | Email header injection | Info | PASS | Carriage-return/line-feed stripping on inputs, plus PHPMailer header handling. |
| F5 | Open mail relay | Info | PASS | Recipient address is hardcoded from server config; the visitor can influence only the Reply-To. |
| F6 | Contact-form abuse controls | Info | PASS | Honeypot field, JavaScript time-trap, same-origin check, per-IP rate limit (5 per 15 minutes, fail-open), input length caps, generic error messages, PHP error display disabled, SMTP over SSL. |
| F7 | Server hardening (.htaccess) | Info | PASS (Apache only) | Forced HTTPS, HSTS, Content-Security-Policy, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy; directory listing off; `config.php`, `/lib`, dotfiles and `.git` blocked; 1 MB request body cap; no PHP execution under `/assets`. |
| F8 | Information disclosure on GitHub Pages | Low / Info | OBSERVATION | GitHub Pages ignores `.htaccess`, so `contact.php`, `SECURITY.md`, and the PHPMailer source are readable there as text. No secrets are exposed and the repository is already public. Fully mitigated once the site is served from cPanel, where `.htaccess` applies. |
| F9 | Distributed spam / layer-7 abuse | Low | RESIDUAL | The per-IP rate limit does not stop a multi-IP botnet. Mitigate with Cloudflare WAF, Bot Fight Mode, and optionally Cloudflare Turnstile on the form. |
| F10 | Dependency currency | Info | MONITOR | PHPMailer is pinned to 6.9.1. Review quarterly and update if a security release ships. |

## 5. Evidence appendix
**Static analysis result:** 0 dangerous PHP sinks in `contact.php`; JavaScript `innerHTML`
usage confirmed static-literal only.

**Safe live probes (GitHub Pages, static host - not the production cPanel target):**

| Path | Response | Interpretation |
|------|----------|----------------|
| /config.php | 404 | Not present (correct; created only on the server). |
| /config.sample.php | 200 | Template only, contains a placeholder password. |
| /contact.php | 200 (source text) | Served as text by GitHub Pages; contains no secrets. Executed and protected on cPanel. |
| /.git/config | 404 | Repository metadata not exposed. |
| /lib/PHPMailer/PHPMailer.php | 200 | Library source; public and non-sensitive. |
| /SECURITY.md | 200 | Internal runbook; no secrets. Blocked on cPanel by `.htaccess`. |

Note: the 200 responses above are a property of static GitHub Pages hosting, which does not
process `.htaccess`. On the production cPanel host these paths return 403/404 as configured.

## 6. Recommendations (prioritized)
1. Deploy the site on **cPanel + Cloudflare** so that `.htaccess` controls and the WAF are active.
2. Publish **SPF, DKIM, and DMARC** DNS records.
3. Enable **cPanel two-factor authentication**, ModSecurity, and automatic backups.
4. Enable **GitHub two-factor authentication**, Dependabot alerts, and secret scanning.
5. Add a **CodeQL / Semgrep** SAST workflow so every push is scanned automatically.
6. After go-live, run an **OWASP ZAP baseline** scan (DAST) against greenarc.solutions.
7. Consider **Cloudflare Turnstile** on the contact form for stronger bot resistance.

## 7. Retest and next steps
After deployment to cPanel, re-run the probes and an OWASP ZAP baseline against the production
domain and confirm each control returns the expected result:
- `config.php` returns 403, `.git` paths return 404, internal docs return 403.
- `http://` redirects to `https://`; securityheaders.com grade is A or higher.
- A normal form submission is delivered; a burst of submissions triggers the rate limit.

## 8. Out of scope
- Denial-of-service / load / stress testing (documented reason above).
- Certified third-party penetration test.
- Application code changes (none were part of this assessment).
