# GreenArc Solutions - Go Live on cPanel + Cloudflare (Option A)

This is the step-by-step to take greenarc.solutions live on your existing cPanel host,
with Cloudflare Free in front for WAF, DDoS protection, and SSL. This path keeps the PHP
contact form and the finance@ mailbox working exactly as built.

Work top to bottom. Read Section 9 (complications) before you change nameservers.

---

## 1. Prerequisites
- cPanel login for the account that serves greenarc.solutions.
- Access to the domain registrar (to change nameservers later).
- PHP 8.1 or newer selected in cPanel > MultiPHP Manager for the domain.
- The mailbox `finance@greenarc.solutions` exists (cPanel > Email Accounts). Note its password.

## 2. Upload the site
Option A (recommended, via SSH / cPanel Terminal):
```
cd ~/public_html
git clone https://github.com/roy2901/greenarc.git .
```
Option B (no SSH): download the repo ZIP from GitHub, upload via cPanel > File Manager
into `public_html`, and Extract.

The local-only helpers (`.dev-server.ps1`, `preview.cmd`) are gitignored, so they are not
in the repo and will not ship. Good.

## 3. Configure the contact form
1. In File Manager, copy `config.sample.php` to `config.php`.
2. Edit `config.php`:
   - `smtp_host`  = mail.greenarc.solutions (or the host shown in cPanel > Email Accounts > Connect Devices)
   - `smtp_user`  = finance@greenarc.solutions
   - `smtp_pass`  = the mailbox password
   - `smtp_port`  = 465, `smtp_secure` = ssl (or 587 / tls)
3. Save. Never commit `config.php` (it is gitignored and blocked by .htaccess).

## 4. File permissions
- Folders: 755
- Files: 644
- `config.php`: 600 (owner read/write only)

## 5. Enable SSL on the origin (before Cloudflare)
- cPanel > SSL/TLS Status > select the domain > Run AutoSSL.
- Wait until the padlock works on https://greenarc.solutions directly.
- Do this BEFORE turning on Cloudflare strict SSL, or you will get a 525 error.

## 6. Email deliverability (do this while DNS is still at cPanel)
- cPanel > Email Deliverability > Manage/Repair for greenarc.solutions: apply the suggested
  SPF and DKIM records.
- Add a DMARC TXT record at `_dmarc.greenarc.solutions`:
  `v=DMARC1; p=quarantine; rua=mailto:finance@greenarc.solutions`

## 7. Put Cloudflare in front (free plan)
1. Create a free Cloudflare account, Add a Site, enter greenarc.solutions.
2. Cloudflare scans your existing DNS. Confirm these records exist:
   - A record `@` -> your cPanel server IP, Proxied (orange cloud)
   - A/CNAME `www` -> root, Proxied (orange cloud)
   - MX records -> your mail host, DNS only (grey cloud)
   - The mail A record (e.g. `mail`) -> cPanel IP, DNS only (grey cloud)
3. Cloudflare gives you two nameservers. At your registrar, replace the current
   nameservers with Cloudflare's. (This is the switch that makes Cloudflare active.)
4. In Cloudflare SSL/TLS: set encryption mode to Full (strict).
5. Enable: Always Use HTTPS, Automatic HTTPS Rewrites, HSTS (optional, already sent by app),
   WAF Managed Ruleset (free), and Bot Fight Mode.
6. Optional: a Rate Limiting rule on `/contact.php` (for example, 5 requests per minute per IP)
   as a second layer over the app-level limiter.

## 8. Post-deploy verification
Run these once DNS has propagated (see Section 9). From any terminal:
```
curl -I https://greenarc.solutions/config.php        # expect 403
curl -I https://greenarc.solutions/.git/HEAD         # expect 404
curl -I https://greenarc.solutions/README-DEPLOY.md  # expect 403
curl -I http://greenarc.solutions/                   # expect 301 to https
```
Then:
- Submit the contact form; confirm the email arrives at finance@greenarc.solutions.
- Submit six times quickly; the sixth should return the rate-limit message.
- Scan https://securityheaders.com for greenarc.solutions (aim for A or higher).
- Full checklist and the security tests live in SECURITY.md.

## 9. Complications to expect (read before Section 7)
- **DNS propagation:** after the nameserver change, it can take a few hours (up to 24) to go
  fully live. During that window some visitors see the old host, some the new. No downtime if
  the DNS records match what cPanel already used.
- **SSL 525 error:** happens if Cloudflare is set to Full (strict) before the cPanel AutoSSL
  cert is valid. Finish Section 5 first.
- **HTML caching:** Cloudflare may cache pages. If an update does not show, purge cache in
  Cloudflare, or add a cache rule to bypass cache for HTML. Static assets are safe to cache
  (they are versioned, e.g. styles.css?v=3).
- **Email must stay grey-clouded:** never proxy (orange) the MX or mail records, or inbound
  and form email will break. Keep them DNS only.
- **Rate-limit temp files:** the form writes small counter files to the server temp dir. This
  is expected and self-clearing; no action needed.
- **Keep PHPMailer updated:** review /lib/PHPMailer quarterly for security releases.

## Rollback
If anything goes wrong after the nameserver switch, set the registrar nameservers back to your
host's originals. DNS reverts within the TTL window and you are back on plain cPanel.
