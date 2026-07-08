/* ============================================================
   GreenArc Solutions - main.js
   Progressive enhancement: everything here is optional; the
   site renders and navigates fully without JavaScript.
   ============================================================ */
(function () {
  "use strict";

  // mark JS availability so CSS can gate JS-dependent hidden states
  document.documentElement.classList.add("js");

  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------- Industry tile slideshows (cycle on hover) ---------- */
  document.querySelectorAll(".ind-slides").forEach(function (box) {
    var slides = box.querySelectorAll("img");
    slides.forEach(function (s) {
      s.addEventListener("error", function () { s.style.display = "none"; });
    });
    if (!slides.length) return;
    box.classList.add("jsready");
    slides[0].classList.add("active");
    if (slides.length < 2) return;
    var tile = box.closest(".ind") || box;
    var i = 0, timer = null;
    function show(n) { slides[i].classList.remove("active"); i = n; slides[i].classList.add("active"); }
    function adv() { show((i + 1) % slides.length); }
    function start() { if (reduce || timer) return; timer = setInterval(adv, 1500); }
    function stop() { if (timer) { clearInterval(timer); timer = null; } show(0); }
    tile.addEventListener("mouseenter", start);
    tile.addEventListener("mouseleave", stop);
    tile.addEventListener("focusin", start);
    tile.addEventListener("focusout", stop);
    tile.addEventListener("click", adv); // tap fallback on touch devices
  });

  /* ---------- Mobile navigation ---------- */
  var burger = document.querySelector(".burger");
  var menu = document.querySelector("nav.menu");

  function closeMenu() {
    if (!menu) return;
    menu.classList.remove("open");
    if (burger) burger.setAttribute("aria-expanded", "false");
  }

  if (burger && menu) {
    burger.addEventListener("click", function () {
      var open = menu.classList.toggle("open");
      burger.setAttribute("aria-expanded", open ? "true" : "false");
    });
    menu.querySelectorAll("a").forEach(function (a) {
      a.addEventListener("click", closeMenu);
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeMenu();
    });
    document.addEventListener("click", function (e) {
      if (!menu.contains(e.target) && !burger.contains(e.target)) closeMenu();
    });
  }

  /* ---------- Contact form ---------- */
  var form = document.getElementById("contactForm");
  if (form) {
    var statusEl = form.querySelector(".form-status");
    var isStaticHost = /\.github\.io$/.test(location.hostname);
    var loadStart = Date.now(); // for the anti-bot time-trap

    var setStatus = function (type, msg) {
      if (!statusEl) return;
      statusEl.className = "form-status " + type; // ok | bad
      statusEl.textContent = msg;
    };

    var validEmail = function (v) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    };

    var markField = function (field, ok) {
      if (!field) return;
      field.classList.toggle("invalid", !ok);
    };

    var validate = function () {
      var ok = true;
      var name = form.querySelector('[name="name"]');
      var email = form.querySelector('[name="email"]');
      var message = form.querySelector('[name="message"]');

      var nOk = name.value.trim().length >= 2;
      markField(name.closest(".field"), nOk); if (!nOk) ok = false;

      var eOk = validEmail(email.value.trim());
      markField(email.closest(".field"), eOk); if (!eOk) ok = false;

      var mOk = message.value.trim().length >= 10;
      markField(message.closest(".field"), mOk); if (!mOk) ok = false;

      return ok;
    };

    form.querySelectorAll("input, textarea").forEach(function (el) {
      el.addEventListener("input", function () {
        var f = el.closest(".field");
        if (f) f.classList.remove("invalid");
      });
    });

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      if (statusEl) statusEl.className = "form-status";

      if (!validate()) {
        setStatus("bad", "Please complete the highlighted fields.");
        return;
      }

      // stamp elapsed time since page load so the backend can drop instant bot posts
      var tsField = form.querySelector('[name="ts"]');
      if (tsField) tsField.value = String(Date.now() - loadStart);

      // On static preview hosts (GitHub Pages) PHP cannot run:
      // open a prefilled email instead of posting to contact.php.
      if (isStaticHost) {
        var n = form.querySelector('[name="name"]').value.trim();
        var em = form.querySelector('[name="email"]').value.trim();
        var co = form.querySelector('[name="company"]').value.trim();
        var msg = form.querySelector('[name="message"]').value.trim();
        var body = "Name: " + n + "\nEmail: " + em + (co ? "\nCompany: " + co : "") + "\n\n" + msg;
        location.href = "mailto:finance@greenarc.solutions?subject=" +
          encodeURIComponent("Website inquiry from " + n) +
          "&body=" + encodeURIComponent(body);
        setStatus("ok", "Your email app should open with your message ready to send.");
        return;
      }

      var btn = form.querySelector('button[type="submit"]');
      var original = btn ? btn.innerHTML : "";
      if (btn) { btn.disabled = true; btn.innerHTML = "Sending..."; }

      var data = new FormData(form);

      fetch(form.getAttribute("action") || "contact.php", {
        method: "POST",
        body: data,
        headers: { "X-Requested-With": "XMLHttpRequest" }
      })
        .then(function (r) { return r.json().catch(function () { return { ok: r.ok }; }); })
        .then(function (res) {
          if (res && res.ok) {
            form.reset();
            setStatus("ok", (res.message) || "Thank you, your message has been sent. We'll be in touch shortly.");
          } else {
            setStatus("bad", (res && res.message) || "Something went wrong. Please email us directly at finance@greenarc.solutions.");
          }
        })
        .catch(function () {
          setStatus("bad", "Network error. Please email us directly at finance@greenarc.solutions.");
        })
        .finally(function () {
          if (btn) { btn.disabled = false; btn.innerHTML = original; }
        });
    });
  }

  /* ---------- Footer year ---------- */
  var yr = document.getElementById("year");
  if (yr) yr.textContent = new Date().getFullYear();

  /* ---------- Floating actions: WhatsApp + back to top ---------- */
  var wa = document.createElement("a");
  wa.className = "fab fab-wa";
  wa.href = "https://wa.me/919049046949?text=" + encodeURIComponent("Hi GreenArc, I'd like to talk about my books.");
  wa.target = "_blank";
  wa.rel = "noopener";
  wa.setAttribute("aria-label", "Chat with us on WhatsApp");
  wa.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 0 0-8.5 14.98L2 22l5.16-1.5A9.9 9.9 0 1 0 12.04 2zm0 18.02a8.1 8.1 0 0 1-4.13-1.13l-.3-.18-3.06.89.84-2.98-.2-.31a8.12 8.12 0 1 1 6.85 3.71zm4.45-6.08c-.24-.12-1.44-.71-1.66-.79-.22-.08-.39-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06a6.63 6.63 0 0 1-1.95-1.2 7.3 7.3 0 0 1-1.35-1.68c-.14-.24-.02-.37.1-.5.11-.11.24-.28.37-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.32-.75-1.81-.2-.48-.4-.41-.55-.42h-.47c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2s.86 2.32.98 2.48c.12.16 1.7 2.6 4.12 3.64.58.25 1.03.4 1.38.51.58.19 1.1.16 1.52.1.46-.07 1.44-.59 1.64-1.16.2-.57.2-1.05.14-1.16-.06-.1-.22-.16-.46-.28z"/></svg>';
  document.body.appendChild(wa);

  var topBtn = document.createElement("button");
  topBtn.className = "fab fab-top";
  topBtn.type = "button";
  topBtn.setAttribute("aria-label", "Back to top");
  topBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>';
  topBtn.addEventListener("click", function () {
    window.scrollTo({ top: 0, behavior: reduce ? "auto" : "smooth" });
  });
  document.body.appendChild(topBtn);

  /* ---------- Scroll progress bar ---------- */
  var bar = document.createElement("div");
  bar.className = "scroll-progress";
  bar.setAttribute("aria-hidden", "true");
  document.body.appendChild(bar);

  var ticking = false;
  function onScroll() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(function () {
      var doc = document.documentElement;
      var max = doc.scrollHeight - doc.clientHeight;
      var pct = max > 0 ? (doc.scrollTop / max) * 100 : 0;
      bar.style.width = pct + "%";
      topBtn.classList.toggle("show", doc.scrollTop > 600);
      ticking = false;
    });
  }
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  /* ---------- Scroll reveal ---------- */
  var revealTargets = document.querySelectorAll(
    ".svc, .tool, .quote, .step, .post-card, .frow, .section-intro, .why-list li, .contact-details .row"
  );
  if ("IntersectionObserver" in window && !reduce && revealTargets.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          en.target.classList.add("in");
          io.unobserve(en.target);
        }
      });
    }, { rootMargin: "0px 0px -8% 0px", threshold: 0.08 });
    revealTargets.forEach(function (el, idx) {
      el.classList.add("reveal");
      el.style.transitionDelay = ((idx % 4) * 70) + "ms";
      io.observe(el);
    });
  }

  /* ---------- Animated counters (trust strip) ---------- */
  var counters = document.querySelectorAll("[data-count]");
  if (counters.length && "IntersectionObserver" in window) {
    var cio = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        cio.unobserve(en.target);
        var el = en.target;
        var end = parseInt(el.getAttribute("data-count"), 10);
        if (isNaN(end) || reduce) { el.textContent = String(end); return; }
        var t0 = null, dur = 900;
        function tick(ts) {
          if (!t0) t0 = ts;
          var p = Math.min((ts - t0) / dur, 1);
          el.textContent = String(Math.round(end * (0.2 + 0.8 * p * p)));
          if (p < 1) requestAnimationFrame(tick); else el.textContent = String(end);
        }
        requestAnimationFrame(tick);
      });
    }, { threshold: 0.6 });
    counters.forEach(function (c) { cio.observe(c); });
  }
})();
