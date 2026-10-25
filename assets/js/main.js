/* CCFM — site interactivity: theme toggle, mobile nav, form UX.
   Progressive enhancement only — the site is fully usable without JS. */
(function () {
  "use strict";

  /* ---- Theme (light/dark) driven by the same :root tokens ---- */
  var STORAGE_KEY = "ccfm-theme";
  var root = document.documentElement;

  function applyTheme(theme) {
    if (theme === "dark" || theme === "light") {
      root.setAttribute("data-theme", theme);
    } else {
      root.removeAttribute("data-theme");
    }
  }

  // Initial theme: stored preference wins, else system preference.
  try {
    var stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
      applyTheme(stored);
    } else if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
      applyTheme("dark");
    }
  } catch (e) { /* localStorage may be unavailable */ }

  document.addEventListener("click", function (ev) {
    var toggle = ev.target.closest(".theme-toggle");
    if (!toggle) return;
    var next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
    applyTheme(next);
    try { localStorage.setItem(STORAGE_KEY, next); } catch (e) {}
  });

  /* ---- Mobile nav ---- */
  var navToggle = document.querySelector(".nav__toggle");
  var navLinks = document.getElementById("primary-nav");
  if (navToggle && navLinks) {
    navToggle.addEventListener("click", function () {
      var open = navLinks.classList.toggle("is-open");
      navToggle.setAttribute("aria-expanded", open ? "true" : "false");
    });
    navLinks.addEventListener("click", function (ev) {
      if (ev.target.tagName === "A") {
        navLinks.classList.remove("is-open");
        navToggle.setAttribute("aria-expanded", "false");
      }
    });
  }

  /* ---- Contact form: banners from ?sent / ?error, and inline submit state ---- */
  var form = document.getElementById("contact-form");
  if (form) {
    var status = document.getElementById("form-status");
    var params = new URLSearchParams(window.location.search);
    if (status) {
      if (params.get("sent") === "1") {
        status.innerHTML = '<div class="alert alert--ok" role="status">Thank you — your enquiry has been sent. Our team will be in touch shortly.</div>';
        status.scrollIntoView({ behavior: "smooth", block: "center" });
      } else if (params.get("error")) {
        status.innerHTML = '<div class="alert alert--err" role="alert">Sorry, your message could not be sent. Please try again or email us directly at info@ccfmltd.com.ng.</div>';
      }
    }
    var loadedAt = Date.now();
    form.addEventListener("submit", function () {
      var elapsed = document.getElementById("elapsed");
      if (elapsed) { elapsed.value = String(Date.now() - loadedAt); }
      var btn = form.querySelector('button[type="submit"]');
      if (btn) { btn.disabled = true; btn.textContent = "Sending…"; }
    });
  }

  /* ---- Gallery: sector filtering + lightbox (only on the gallery page) ---- */
  var galleryGrid = document.getElementById("gallery-grid");
  if (galleryGrid) {
    var items = Array.prototype.slice.call(galleryGrid.querySelectorAll(".gallery-item"));
    var filterBar = document.querySelector(".filter-bar");

    /* Filtering — hide tiles whose category doesn't match the pressed button. */
    if (filterBar) {
      filterBar.addEventListener("click", function (ev) {
        var btn = ev.target.closest(".filter-btn");
        if (!btn) return;
        var filter = btn.getAttribute("data-filter");
        filterBar.querySelectorAll(".filter-btn").forEach(function (b) {
          b.setAttribute("aria-pressed", b === btn ? "true" : "false");
        });
        items.forEach(function (item) {
          var show = filter === "all" || item.getAttribute("data-category") === filter;
          item.classList.toggle("is-hidden", !show);
        });
      });
    }

    /* Lightbox */
    var lightbox = document.getElementById("lightbox");
    var lbImg = document.getElementById("lightbox-img");
    var lbCaption = document.getElementById("lightbox-caption");
    if (lightbox && lbImg) {
      var closeBtn = lightbox.querySelector(".lightbox__close");
      var prevBtn = lightbox.querySelector(".lightbox__nav--prev");
      var nextBtn = lightbox.querySelector(".lightbox__nav--next");
      var visibleBtns = [];   // buttons for currently-visible tiles
      var currentIndex = 0;
      var lastFocused = null;

      function visibleTiles() {
        return items.filter(function (item) { return !item.classList.contains("is-hidden"); });
      }
      function show(index) {
        if (!visibleBtns.length) return;
        currentIndex = (index + visibleBtns.length) % visibleBtns.length;
        var btn = visibleBtns[currentIndex];
        lbImg.setAttribute("src", btn.getAttribute("data-full"));
        var cap = btn.getAttribute("data-caption") || "";
        lbImg.setAttribute("alt", cap);
        if (lbCaption) { lbCaption.textContent = cap; }
      }
      function open(btn) {
        visibleBtns = visibleTiles().map(function (item) { return item.querySelector(".gallery-item__btn"); });
        var idx = visibleBtns.indexOf(btn);
        if (idx < 0) return;
        lastFocused = btn;
        show(idx);
        lightbox.classList.add("is-open");
        lightbox.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
        if (closeBtn) { closeBtn.focus(); }
      }
      function close() {
        lightbox.classList.remove("is-open");
        lightbox.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
        if (lastFocused) { lastFocused.focus(); }
      }

      galleryGrid.addEventListener("click", function (ev) {
        var btn = ev.target.closest(".gallery-item__btn");
        if (btn) { ev.preventDefault(); open(btn); }
      });
      if (closeBtn) { closeBtn.addEventListener("click", close); }
      if (prevBtn) { prevBtn.addEventListener("click", function () { show(currentIndex - 1); }); }
      if (nextBtn) { nextBtn.addEventListener("click", function () { show(currentIndex + 1); }); }
      // Click on the backdrop (not the image/controls) closes.
      lightbox.addEventListener("click", function (ev) {
        if (ev.target === lightbox || ev.target.classList.contains("lightbox__figure")) { close(); }
      });
      document.addEventListener("keydown", function (ev) {
        if (!lightbox.classList.contains("is-open")) return;
        if (ev.key === "Escape") { close(); }
        else if (ev.key === "ArrowLeft") { show(currentIndex - 1); }
        else if (ev.key === "ArrowRight") { show(currentIndex + 1); }
      });
    }
  }

  /* ---- Footer year ---- */
  var yr = document.getElementById("year");
  if (yr) { yr.textContent = new Date().getFullYear(); }
})();
