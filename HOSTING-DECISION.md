# GreenArc - Hosting Decision (blocker)

The domain `greenarc.solutions` currently serves a **GoDaddy Website Builder** page, which is a
closed no-code product that **cannot run this repo's PHP or MySQL**. Nothing here is deployed to
production. You must pick one of the two paths below before the contact form, leads database, or
admin can work in the real world.

## The core trade-off
The codebase is PHP + MySQL. Either give it a host that runs PHP, or drop the PHP backend.

---

## Path 1: Real PHP hosting (keep everything as built) - RECOMMENDED
Move `greenarc.solutions` onto a cPanel/LAMP plan and deploy the repo as-is.

**What it costs**
- cPanel/LAMP shared hosting: about $3 to $10 per month (GoDaddy also sells this; or Hostinger,
  Namecheap, etc.). Includes MySQL and free SSL.
- Domain: already owned.
- Cloudflare Free in front: $0 (WAF, DDoS, SSL).

**How to go live**
1. Buy a cPanel/Linux hosting plan (not "Website Builder"). If staying with GoDaddy, this is their
   "Web Hosting" / "cPanel" product, a different SKU from the builder.
2. Point the domain at that host (change the A record / nameservers off the builder).
3. Deploy per `GO-LIVE-CPANEL.md` and `FULLSTACK-SETUP.md`: upload files, set `config.php`,
   import `db/schema.sql`, generate the admin hash, run AutoSSL, add Cloudflare.

**Pros:** zero code changes; contact form + leads DB + admin all work; cheap.
**Cons:** you manage a server (updates, backups); shared-host performance ceiling (fine for this).

---

## Path 2: Static hosting + form service (drop the PHP backend)
Host the static site on Cloudflare Pages / Netlify (free) and replace the PHP.

**What it costs**
- Static hosting: $0 (Cloudflare Pages or Netlify free tier).
- Form handling: Formspree/Web3Forms free tier (about 50 to 100 submissions/month), or a serverless
  function.
- Email for `finance@`: needs a new home (Zoho Mail free, or Google Workspace ~$6/user/month) since
  you leave cPanel mail.
- If you still want a leads database + admin: needs a hosted DB (e.g. Supabase/Neon free tier) plus
  a serverless API. This is effectively a partial rewrite.

**How to go live**
1. Connect the GitHub repo to Cloudflare Pages or Netlify; deploy the static pages.
2. Replace `contact.php` with a form-service endpoint (or a Pages/Netlify Function).
3. Re-home the mailbox and DNS; add the form/DB services.

**Pros:** best edge security and CI/CD, $0 base hosting.
**Cons:** rewrite the backend; the admin dashboard and MySQL work we built do not carry over
without extra services; more moving parts.

---

## Recommendation
Take **Path 1**. It is the cheapest, fastest route to a fully working site, and it uses the code
exactly as built. Practical monthly cost: about $3 to $10 plus the domain you already own.

Only choose Path 2 if you specifically want a fully serverless/static stack and are willing to
re-platform the contact + admin backend.

## Immediate action
Confirm which path. If Path 1, buy a cPanel plan (or enable cPanel hosting on the existing GoDaddy
account) and I will walk the deployment. If Path 2, I will start the form/DB rewrite.
