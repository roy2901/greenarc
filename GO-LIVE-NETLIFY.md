# Go Live for Free with Netlify (plain-English, no coding)

This is the simplest way to put greenarc.solutions online for free. Total cost: $0/month.
You do not need any technical knowledge. Follow the steps; your developer (Claude) will help at
each one. Whole thing takes about 15 to 20 minutes, mostly waiting.

What you get:
- Your website live at greenarc.solutions, fast and secure (free padlock/HTTPS).
- Every contact-form enquiry emailed to finance@greenarc.solutions AND saved in a simple list
  you can view on Netlify. No database or server to maintain.

---

## Part A: Make a free Netlify account (about 3 minutes)
1. Go to https://www.netlify.com and click "Sign up".
2. Choose "Sign up with GitHub" (you already have GitHub from this project).
3. Approve the access it asks for. That is it, no card, no cost.

## Part B: Put the website online (about 3 minutes)
4. In Netlify click "Add new site" then "Import an existing project".
5. Click "Deploy with GitHub" and approve access if asked.
6. In the list, pick the repository "roy2901/greenarc".
7. Leave every build setting blank/default (this is a plain website, nothing to build).
   If it asks for a "Publish directory", leave it empty.
8. Click "Deploy site". Wait about a minute.
9. Netlify gives you a temporary address like "sunny-cat-1234.netlify.app". Click it: your
   real website is now online there. (Next we swap that for greenarc.solutions.)

## Part C: Turn on enquiry emails (about 2 minutes)
10. Netlify automatically finds the contact form. In your site, go to "Forms" (left menu or
    under "Site configuration" > "Forms").
11. Open "Form notifications" > "Add notification" > "Email notification".
12. Enter finance@greenarc.solutions as the address to notify. Save.
    Now every enquiry emails you, and all of them are listed under "Forms".

## Part D: Connect your domain greenarc.solutions (about 5 minutes + waiting)
13. In Netlify go to "Domain management" > "Add a domain" > type greenarc.solutions > confirm.
14. Netlify shows how to point the domain. It offers two ways; tell your developer which screen
    you see and they will give you the exact values. Usually one of:
    - Set the domain's nameservers (in GoDaddy) to the ones Netlify shows, OR
    - Add an "A record" pointing to Netlify and a "CNAME" for www.
15. Log in to GoDaddy > your domain > DNS / Nameservers, and enter what Netlify told you.
    Note: greenarc.solutions currently runs "GoDaddy Website Builder". Pointing the domain to
    Netlify replaces that old page with your new site. Your developer will confirm the steps so
    nothing breaks (email/MX records stay as they are).
16. Wait: it can take from a few minutes up to a few hours for the address to switch over.
    Netlify turns on the free HTTPS padlock automatically once it is connected.

## Done
greenarc.solutions now shows your new website, and enquiries reach your inbox.

## Notes
- The advanced PHP contact form and admin dashboard we also built are not used on Netlify. They
  stay saved in your code for the future if you ever move to a PHP host and want them.
- If you later outgrow the free form (over 100 enquiries a month), Netlify has cheap paid tiers,
  or we switch the plan then. No change needed now.
