/* Crystal's Art School — shared site behaviour (v2.0)
   Nav sheet · GSAP scroll reveals · carousel arrows · cart · guards */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var isTouch = window.matchMedia('(pointer: coarse)').matches;

  /* ---------- mobile nav sheet ---------- */
  var burger = document.querySelector('.nav-burger');
  var sheet = document.querySelector('.nav-sheet');
  if (burger && sheet) {
    burger.addEventListener('click', function () {
      var open = sheet.classList.toggle('open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    sheet.addEventListener('click', function (e) {
      if (e.target.tagName === 'A') sheet.classList.remove('open');
    });
  }

  /* ---------- cart button (demo count persistence) ---------- */
  var CART_KEY = 'cas-cart-count';
  function cartCount() { return parseInt(localStorage.getItem(CART_KEY) || '0', 10); }
  function renderCart() {
    document.querySelectorAll('.cart-badge').forEach(function (b) {
      var n = cartCount();
      b.textContent = n;
      b.style.display = n > 0 ? 'grid' : 'none';
    });
  }
  window.casAddToCart = function () {
    localStorage.setItem(CART_KEY, String(cartCount() + 1));
    renderCart();
    /* tactile feedback: wiggle the cart */
    document.querySelectorAll('.cart-btn').forEach(function (btn) {
      btn.style.transition = 'transform .18s';
      btn.style.transform = 'translateY(-3px) rotate(-8deg)';
      setTimeout(function () { btn.style.transform = ''; }, 220);
    });
  };
  renderCart();
  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add-to-cart]');
    if (add) { e.preventDefault(); window.casAddToCart(); }
  });

  /* ---------- carousel: arrows are the ONLY control ---------- */
  document.querySelectorAll('.carousel').forEach(function (car) {
    var rail = car.querySelector('.rail');
    if (!rail) return;
    car.querySelectorAll('.c-arrow').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var slide = rail.querySelector('.slide');
        if (!slide) return;
        var step = slide.getBoundingClientRect().width + 18;
        rail.scrollBy({ left: btn.dataset.dir === 'prev' ? -step : step, behavior: reduceMotion ? 'auto' : 'smooth' });
      });
    });
  });

  /* ---------- pencil cursor: opt-in zones, never on touch/reduced ---------- */
  if (!isTouch && !reduceMotion) {
    document.querySelectorAll('[data-cursor="pencil"]').forEach(function (el) {
      el.classList.add('cursor-pencil');
    });
  }

  /* ---------- GSAP scroll choreography ---------- */
  if (!reduceMotion && window.gsap && window.ScrollTrigger) {
    gsap.registerPlugin(ScrollTrigger);

    /* fade-up stagger for any .reveal group */
    document.querySelectorAll('.reveal').forEach(function (group) {
      var items = group.children;
      gsap.fromTo(items,
        { y: 26, opacity: 0 },
        {
          y: 0, opacity: 1,
          duration: 0.9,
          ease: 'power2.out',
          stagger: 0.12,
          scrollTrigger: { trigger: group, start: 'top 84%' }
        });
    });

    /* single elements */
    document.querySelectorAll('.reveal-one').forEach(function (el) {
      gsap.fromTo(el,
        { y: 26, opacity: 0 },
        { y: 0, opacity: 1, duration: 0.9, ease: 'power2.out',
          scrollTrigger: { trigger: el, start: 'top 86%' } });
    });

    /* ✳ ornament spin-in (motion token #3) */
    document.querySelectorAll('.eyebrow .star').forEach(function (star) {
      gsap.fromTo(star,
        { rotation: -90, opacity: 0 },
        { rotation: 0, opacity: 1, duration: 0.8, ease: 'back.out(2)',
          scrollTrigger: { trigger: star, start: 'top 88%' } });
    });

    /* brushstroke wipe — max ONE per viewport: elements tagged .brush-wipe */
    document.querySelectorAll('.brush-wipe').forEach(function (el) {
      gsap.fromTo(el,
        { clipPath: 'inset(0 100% 0 0)' },
        { clipPath: 'inset(0 0% 0 0)', duration: 1.4, ease: 'expo.out',
          scrollTrigger: { trigger: el, start: 'top 80%' } });
    });
  } else {
    /* no GSAP / reduced motion → everything visible */
    document.querySelectorAll('.reveal > *, .reveal-one').forEach(function (el) {
      el.style.opacity = 1;
      el.style.transform = 'none';
    });
  }
})();
