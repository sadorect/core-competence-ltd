# CCFM — Core-Competence Facility Management Ltd

Marketing website for **Core-Competence Facility Management Limited (CCFM)**, a Lagos-based
integrated facility management company. Live at **https://ccfmltd.com.ng**.

## Stack

Hand-authored **static site** — plain HTML + one CSS file + a little vanilla JS, plus a small
PHP contact-form handler. No build step, no framework.

## Structure

```
index.html                 Home
about.html                 About Us
services.html              Services
sectors.html               Sectors We Serve
why-us.html                Why Choose Us
sustainability.html        Sustainability & HSE
contact.html               Contact (form)
contact.php                Contact-form mail handler
assets/css/styles.css      All styles + design tokens (:root) — light/dark themeable
assets/js/main.js          Theme toggle, mobile nav, form UX, anti-spam time-trap
assets/img/                Logos (SVG + 512px PNG), favicon, OG image
robots.txt, sitemap.xml    SEO
.htaccess                  Clean/extension-less URLs (mod_rewrite)
design/og-image.svg        Source for the 1200×630 OG image (rasterised to assets/img/og-image.png)
```

## Design system

All colours, spacing, and type are CSS custom properties in the `:root` block of
`assets/css/styles.css`, with a `:root[data-theme="dark"]` override and a persisted
light/dark toggle. **Re-theme the whole site by editing that one block** — no markup changes.

## Assets & cache-busting

`mod_expires`/`mod_headers` are not enabled on the host, so CSS/JS cache lifetime is set by
Cloudflare's edge defaults. To force clients to pick up changes, bump the `?v=N` query on the
`styles.css` / `main.js` links in every HTML file when those assets change.

## Contact form

`contact.php` emails submissions to `info@ccfmltd.com.ng` via the server's Postfix
(`From: no-reply@ccfmltd.com.ng` so SPF/DKIM pass, `Reply-To` the sender). Spam protection:
a honeypot field + a JS "time-trap" (near-instant submits are dropped).

## Deployment

The site is served by Apache from `/home/ccfmltd/public_html` on the host (Virtualmin domain,
Cloudflare-proxied). Deploy by syncing the repo contents into that docroot and setting owner
`ccfmltd:ccfmltd`, `755` dirs / `644` files.
