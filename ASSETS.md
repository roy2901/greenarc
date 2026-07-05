# Assets to add before launch

Drop your real files into `assets/img/` using the **exact filenames** below. Until you do,
the site shows tasteful green placeholders instead of broken images, so it's safe to deploy first
and add assets after.

## Photos (JPG, optimized, aim for < 300 KB each)

| Filename | Where it appears | Recommended size | Orientation |
|---|---|---|---|
| `hero.jpg` | Full-width hero background | 1920 × 1080 | Landscape |
| `why.jpg` | "Why GreenArc" section photo | 1100 × 900 | Portrait-ish |
| `cta.jpg` | Call-to-action band background | 1920 × 1080 | Landscape |
| `ind-cpg.jpg` | Industries tile, CPG & Distribution | 800 × 800 | Square |
| `ind-food.jpg` | Industries tile, Food & Beverage | 800 × 800 | Square |
| `ind-retail.jpg` | Industries tile, Retail | 800 × 800 | Square |
| `ind-services.jpg` | Industries tile, Service Businesses | 800 × 800 | Square |
| `og-image.jpg` | Social-share preview (LinkedIn/WhatsApp) | 1200 × 630 | Landscape |

**Tip:** compress with [squoosh.app](https://squoosh.app) or [tinypng.com](https://tinypng.com) before uploading.
Dark, professional imagery works best because the hero/CTA overlays are dark green.

## Logo & favicons

The real GreenArc logo is now in place. These were generated from your logo artwork and are already live on the site:

| Filename | Purpose | Status |
|---|---|---|
| `assets/img/logo-full.png` | Header logo (full lockup) | Done, 340px |
| `assets/img/logo-mark.png` | Icon mark only (spare asset) | Done, 256px |
| `assets/img/favicon-64.png` | Browser tab icon | Done, from mark |
| `assets/img/apple-touch-icon.png` | iOS home-screen icon | Done, 180 × 180 |
| `assets/img/og-image.jpg` | Social-share card | Done, 1200 × 630 |

Nothing more is needed for branding. If you later want a **legacy `favicon.ico`** for very old browsers, generate one from the logo at
[realfavicongenerator.net](https://realfavicongenerator.net) and drop it in the site root.

## Testimonials (important)

The homepage ships with **3 placeholder testimonials** marked `[Client Name]` / `[Company]`.
Replace the quote text, names, roles, and the avatar initials in `index.html`
(search for `id="testimonials"`) with **real client quotes** before going live.
Delete the italic note line ("Placeholder testimonials …") once done.
