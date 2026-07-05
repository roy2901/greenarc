/* ============================================================
   GreenArc Solutions - main.js
   Mobile nav toggle + accessible AJAX contact form
   ============================================================ */
(function () {
  "use strict";

  /* ---------- Image fallback (hide broken photos before real ones are added) ---------- */
  document.querySelectorAll("img.imgph").forEach(function (img) {
    img.addEventListener("error", function () { img.classList.add("broken"); });
    // handle images that already failed before JS ran
    if (img.complete && img.naturalWidth === 0) img.classList.add("broken");
  });

  /* ---------- Industry tile slideshows (cycle on hover) ---------- */
  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  document.querySelectorAll(".ind-slides").forEach(function (box) {
    var slides = box.querySelectorAll("img");
    slides.forEach(function (s) {
      s.addEventListener("error", function () { s.style.display = "none"; });
    });
    if (!slides.length) return;
    slides[0].classList.add("active");
    if (slides.length < 2) return;
    var tile = box.closest(".ind") || box;
    var i = 0, timer = null;
    function show(n){ slides[i].classList.remove("active"); i = n; slides[i].classList.add("active"); }
    function adv(){ show((i + 1) % slides.length); }
    function start(){ if (reduce || timer) return; timer = setInterval(adv, 1500); }
    function stop(){ if (timer) { clearInterval(timer); timer = null; } show(0); }
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
    // close when a link is tapped
    menu.querySelectorAll("a").forEach(function (a) {
      a.addEventListener("click", closeMenu);
    });
    // close on Escape
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeMenu();
    });
    // close when clicking outside
    document.addEventListener("click", function (e) {
      if (!menu.contains(e.target) && !burger.contains(e.target)) closeMenu();
    });
  }

  /* ---------- Contact form ---------- */
  var form = document.getElementById("contactForm");
  if (!form) return;

  var statusEl = form.querySelector(".form-status");

  function setStatus(type, msg) {
    if (!statusEl) return;
    statusEl.className = "form-status " + type; // ok | bad
    statusEl.textContent = msg;
  }

  function validEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
  }

  function markField(field, ok) {
    if (!field) return;
    field.classList.toggle("invalid", !ok);
  }

  function validate() {
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
  }

  // clear invalid state as the user types
  form.querySelectorAll("input, textarea").forEach(function (el) {
    el.addEventListener("input", function () {
      var f = el.closest(".field");
      if (f) f.classList.remove("invalid");
    });
  });

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    setStatus("", "");
    statusEl && (statusEl.className = "form-status");

    if (!validate()) {
      setStatus("bad", "Please complete the highlighted fields.");
      return;
    }

    var btn = form.querySelector('button[type="submit"]');
    var original = btn ? btn.innerHTML : "";
    if (btn) { btn.disabled = true; btn.innerHTML = "Sending…"; }

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

  /* ---------- Footer year ---------- */
  var yr = document.getElementById("year");
  if (yr) yr.textContent = new Date().getFullYear();
})();
