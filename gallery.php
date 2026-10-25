<?php
/**
 * CCFM gallery — renders tiles from the out-of-web-root manifest written by the
 * admin manager (/admin). Falls back to a friendly empty state. Server-side
 * render keeps the page usable with no JS and crawlable for SEO. The filter bar
 * + lightbox behaviour live unchanged in /assets/js/main.js (keyed on #gallery-grid).
 */
$MANIFEST = '/home/ccfmltd/gallery-data/manifest.json';
$items = [];
if (is_file($MANIFEST)) {
    $d = json_decode((string) file_get_contents($MANIFEST), true);
    if (is_array($d)) {
        $items = $d;
    }
}
usort($items, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gallery — Our Facilities &amp; Projects | CCFM</title>
  <meta name="description" content="A visual look at Core-Competence Facility Management's work across aviation, maritime, warehousing &amp; logistics, industrial, commercial and residential environments in Nigeria.">
  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="/assets/img/favicon.svg">
  <link rel="canonical" href="https://ccfmltd.com.ng/gallery">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="CCFM — Core-Competence Facility Management">
  <meta property="og:title" content="Gallery — Our Facilities & Projects">
  <meta property="og:description" content="A visual look at CCFM's facility management work across aviation, maritime, logistics, industrial, commercial and residential environments in Nigeria.">
  <meta property="og:url" content="https://ccfmltd.com.ng/gallery">
  <meta property="og:image" content="https://ccfmltd.com.ng/assets/img/og-image.png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:locale" content="en_NG">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Gallery — Our Facilities & Projects">
  <meta name="twitter:description" content="A visual look at CCFM's facility management work across the sectors we serve in Nigeria.">
  <meta name="twitter:image" content="https://ccfmltd.com.ng/assets/img/og-image.png">
  <link rel="stylesheet" href="/assets/css/styles.css?v=4">
  <script src="/assets/js/main.js?v=4" defer></script>
</head>
<body>
  <header class="site-header">
    <nav class="nav container" aria-label="Primary">
      <a class="brand" href="/" aria-label="CCFM home"><img class="brand__logo" src="/assets/img/logo.svg" alt="CCFM — Facility Management" width="188" height="44"></a>
      <ul class="nav__links" id="primary-nav">
        <li><a href="/">Home</a></li>
        <li><a href="/about">About</a></li>
        <li><a href="/services">Services</a></li>
        <li><a href="/sectors">Sectors</a></li>
        <li><a href="/gallery" class="is-active">Gallery</a></li>
        <li><a href="/why-us">Why Us</a></li>
        <li><a href="/sustainability">Sustainability</a></li>
        <li><a href="/contact">Contact</a></li>
      </ul>
      <div class="nav__actions">
        <button class="theme-toggle" type="button" aria-label="Toggle dark mode" title="Toggle dark mode">
          <span class="icon-sun" aria-hidden="true">☀️</span><span class="icon-moon" aria-hidden="true">🌙</span>
        </button>
        <button class="nav__toggle" type="button" aria-label="Menu" aria-controls="primary-nav" aria-expanded="false"><span></span></button>
      </div>
    </nav>
  </header>

  <main>
    <section class="page-hero">
      <div class="container page-hero__inner">
        <div class="breadcrumb"><a href="/">Home</a> / Gallery</div>
        <h1>Gallery</h1>
        <p>A visual look at the environments we keep running — filter by sector to explore our work.</p>
      </div>
    </section>

    <section class="section">
      <div class="container">
<?php if (!$items): ?>
        <p class="lead" style="text-align:center;color:var(--color-muted);">Our gallery is being updated — please check back soon.</p>
<?php else: ?>
        <div class="filter-bar" role="group" aria-label="Filter gallery by sector">
          <button type="button" class="filter-btn" data-filter="all" aria-pressed="true">All</button>
          <button type="button" class="filter-btn" data-filter="aviation" aria-pressed="false">Aviation</button>
          <button type="button" class="filter-btn" data-filter="maritime" aria-pressed="false">Maritime</button>
          <button type="button" class="filter-btn" data-filter="logistics" aria-pressed="false">Warehousing &amp; Logistics</button>
          <button type="button" class="filter-btn" data-filter="industrial" aria-pressed="false">Industrial</button>
          <button type="button" class="filter-btn" data-filter="commercial" aria-pressed="false">Commercial &amp; Residential</button>
        </div>

        <ul class="gallery-grid" id="gallery-grid">
<?php foreach ($items as $it):
        $file = $it['file'] ?? '';
        if ($file === '') continue;
        $thumb = $it['thumb'] ?? $file;
        $cap   = $it['caption'] ?? '';
        $alt   = $it['alt'] ?? $cap;
        $cat   = $it['category'] ?? 'commercial';
?>
          <li class="gallery-item" data-category="<?= $h($cat) ?>">
            <button type="button" class="gallery-item__btn" data-full="<?= $h($file) ?>" data-caption="<?= $h($cap) ?>">
              <img src="<?= $h($thumb) ?>" width="800" height="600" loading="lazy" alt="<?= $h($alt) ?>">
<?php if ($cap !== ''): ?>              <span class="gallery-item__caption"><?= $h($cap) ?></span>
<?php endif; ?>
            </button>
          </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </div>
    </section>

    <section class="section cta-band">
      <div class="container">
        <h2>Want your facility to look like this?</h2>
        <p>Talk to our team about a facility management program built for your environment.</p>
        <p style="margin-top:1.6rem;"><a href="/contact" class="btn btn--accent btn--lg">Request a Consultation</a></p>
      </div>
    </section>

    <!-- Lightbox overlay (hidden until a tile is opened). -->
    <div class="lightbox" id="lightbox" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Image viewer">
      <button type="button" class="lightbox__close" aria-label="Close image viewer">&times;</button>
      <button type="button" class="lightbox__nav lightbox__nav--prev" aria-label="Previous image">&#8249;</button>
      <figure class="lightbox__figure">
        <img class="lightbox__img" id="lightbox-img" src="" alt="">
        <figcaption class="lightbox__caption" id="lightbox-caption"></figcaption>
      </figure>
      <button type="button" class="lightbox__nav lightbox__nav--next" aria-label="Next image">&#8250;</button>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <img class="brand__logo brand__logo--white" src="/assets/img/logo-white.svg" alt="CCFM" width="180" height="42">
          <p style="margin-top:1rem;">Core-Competence Facility Management Limited — integrated, technology-enabled facility management that makes built environments safer, greener, and more efficient across Nigeria.</p>
        </div>
        <div>
          <h4>Company</h4>
          <ul class="footer-links">
            <li><a href="/about">About Us</a></li>
            <li><a href="/services">Services</a></li>
            <li><a href="/sectors">Sectors We Serve</a></li>
            <li><a href="/gallery">Gallery</a></li>
            <li><a href="/why-us">Why Choose Us</a></li>
            <li><a href="/sustainability">Sustainability &amp; HSE</a></li>
          </ul>
        </div>
        <div>
          <h4>Contact</h4>
          <ul class="footer-links">
            <li>Suite D249, Road 2, Ikota Complex, VGC, Ajah-Lekki, Lagos, Nigeria</li>
            <li><a href="tel:+2349095967055">+234 909 596 7055</a></li>
            <li><a href="mailto:info@ccfmltd.com.ng">info@ccfmltd.com.ng</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <span>&copy; <span id="year">2026</span> Core-Competence Facility Management Limited. All rights reserved.</span>
        <span>Lagos, Nigeria</span>
      </div>
    </div>
  </footer>
</body>
</html>
