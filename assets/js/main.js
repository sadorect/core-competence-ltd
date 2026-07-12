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

  /* ---- Footer year ---- */
  var yr = document.getElementById("year");
  if (yr) { yr.textContent = new Date().getFullYear(); }
})();
