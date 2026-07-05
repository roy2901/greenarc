# GreenArc Solutions, Deployment Guide (cPanel)

This is a static website (HTML/CSS/JS) with one PHP script for the contact form.
No build step, no database. You just upload the files and configure mail.

---

## 1. Upload the files

1. Zip the **contents** of the `greenarc/` folder (not the folder itself).
2. In cPanel → **File Manager**, open `public_html` (the document root for greenarc.solutions).
3. Upload the zip and **Extract** it there.

Your `public_html` should now contain:
```
index.html      services.html   industries.html  process.html
technology.html about.html      contact.html     privacy.html
terms.html      404.html
contact.php  config.sample.php  .htaccess
robots.txt  sitemap.xml  site.webmanifest  favicon.ico (add this)
assets/   lib/
```
> Do **not** upload `.dev-server.ps1` if you see it, it's a local-preview helper only, not part of the site.
> If `.htaccess` doesn't appear, enable **Settings → Show Hidden Files** in File Manager.

---

## 2. Add your logo, photos, and favicon

Follow **ASSETS.md**, drop your images into `assets/img/` using the exact filenames listed.
Generate `favicon.ico` + `apple-touch-icon.png` (e.g. realfavicongenerator.net) and place them
as noted. The site works before you do this (placeholders show), but do it before promoting the site.

---

## 3. Configure the contact form

1. In cPanel → **Email Accounts**, make sure `finance@greenarc.solutions` exists (create it if not) and note its password.
2. In File Manager, **copy** `config.sample.php` to `config.php`.
3. Edit `config.php` and set:
   - `smtp_host` → usually `mail.greenarc.solutions` (or the value under cPanel → Email Accounts → *Connect Devices* → *Mail Client Manual Settings* → **Outgoing SMTP host**).
   - `smtp_user` → `finance@greenarc.solutions`
   - `smtp_pass` → that mailbox's password
   - `smtp_port` / `smtp_secure` → `465` + `ssl` (recommended), or `587` + `tls`.
4. Save. `config.php` is already blocked from the web by `.htaccess`.

**Test:** open the site, submit the contact form, and confirm the email arrives at
`finance@greenarc.solutions`. Reply-To is set to the visitor, so you can reply directly.

> Requires PHP (any modern cPanel host has it). If sending fails, double-check the SMTP
> host/port/password, and see cPanel → **Errors** or the PHP error log.

---

## 4. Enable HTTPS (SSL)

1. cPanel → **SSL/TLS Status** → select the domain → **Run AutoSSL** (free Let's Encrypt cert).
2. Once the padlock works, the `.htaccess` rules automatically force `http → https` and `www → non-www`.
3. (Optional) Keep the `Strict-Transport-Security` header, it's already in `.htaccess`. Only remove it if you ever need to disable HTTPS.

---

## 5. Point the domain (only if DNS isn't already here)

If `greenarc.solutions` isn't already served by this host:
- At your domain registrar, set the **nameservers** to your host's (shown in cPanel welcome email), **or**
- Create an **A record** for `@` and `www` pointing to your server's IP (cPanel → *Shared IP Address*).
- Allow up to a few hours for DNS to propagate.

---

## 6. Improve email deliverability (highly recommended)

So contact-form email (and your outbound mail) doesn't land in spam:
- cPanel → **Email Deliverability** → for `greenarc.solutions`, click **Manage / Repair** and apply the
  suggested **SPF** and **DKIM** records.
- Add a **DMARC** record (TXT at `_dmarc.greenarc.solutions`), e.g.
  `v=DMARC1; p=none; rua=mailto:finance@greenarc.solutions`.

---

## 7. Post-launch checklist

- [ ] Real logo, photos, favicon, and og-image in place (ASSETS.md)
- [ ] Real testimonials swapped in (placeholder note removed)
- [ ] Contact form test email received
- [ ] HTTPS padlock shows; http/www redirect to https non-www
- [ ] Update the footer social links (LinkedIn / X / Facebook, currently `#`)
- [ ] Submit `sitemap.xml` in [Google Search Console](https://search.google.com/search-console)
- [ ] (Optional) Add analytics, Plausible or Cloudflare Web Analytics (no cookie banner needed)
- [ ] (Optional) Add a Calendly/Cal.com "Book a consultation" link to the hero/CTA buttons

---

## Editing later

- **Text/services:** edit `index.html`.
- **Colors/spacing:** edit the `:root` variables at the top of `assets/css/styles.css`.
- **Form behavior:** `contact.php` (server) and `assets/js/main.js` (client).
