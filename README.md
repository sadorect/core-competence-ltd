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
gallery.php                Gallery — renders tiles from the admin-managed manifest (served at /gallery)
why-us.html                Why Choose Us
sustainability.html        Sustainability & HSE
contact.html               Contact (form)
contact.php                Contact-form mail handler
admin/                     Password-protected gallery manager (upload / edit / delete / reorder)
assets/css/styles.css      All styles + design tokens (:root) — light/dark themeable
assets/js/main.js          Theme toggle, mobile nav, form UX, anti-spam time-trap
assets/img/                Logos (SVG + 512px PNG), favicon, OG image
assets/img/gallery/        Gallery images (uploaded at runtime) + hardening .htaccess; thumbs/ subdir
robots.txt, sitemap.xml    SEO
.htaccess                  Clean/extension-less URLs + /gallery -> gallery.php (mod_rewrite)
design/og-image.svg        Source for the 1200×630 OG image (rasterised to assets/img/og-image.png)
```

## Gallery admin (`/admin`)

Self-contained PHP (no framework), matching `contact.php`'s style. Lets CCFM **upload, edit
(caption/alt/sector), delete, and drag-reorder** gallery photos.

- **Data lives OUTSIDE the web root** at `/home/ccfmltd/gallery-data/` (owned `ccfmltd`, never
  served, untouched by deploys): `manifest.json` (the tile list, source of truth) and `config.php`
  (the admin password hash). Uploaded **images** go in the served docroot `assets/img/gallery/`
  (+ `thumbs/`). `gallery.php` renders tiles from the manifest; the filter/lightbox JS in
  `main.js` is unchanged.
- **First run:** visit `/admin` → it prompts to **set the admin password** (writes `config.php`).
  No password is ever stored in this repo.
- **Security:** session auth (`admin/inc/auth.php`), CSRF on every POST, image-only validation via
  `finfo`, and **every upload is re-encoded through GD** (kills disguised payloads) and saved with a
  random `.jpg` name. `assets/img/gallery/.htaccess` denies serving any executable/markup file there.
- **Deploy note:** the deploy `rsync` runs **without `--delete`**, and the manifest/config/uploads
  are outside the repo — so publishing new site code never disturbs uploaded photos or the manifest.
  See the Deployment section.

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
