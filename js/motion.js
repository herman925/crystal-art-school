/* Crystal's Art School — shared GSAP motion pass (every page except index,
   which carries its own bespoke choreography).
   Waits for the DC runtime to mount the page, then layers scroll-triggered
   entrances over the static layout. If GSAP is missing or the visitor
   prefers reduced motion, the page simply stays fully visible. */
(function () {
  if (window.__casMotion) return; window.__casMotion = true;
  var reduce = false;
  try { reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}
  /* headless / automation (screenshots, QA): render the final state, no tweens */
  try {
    if (navigator.webdriver === true || /Headless/i.test(navigator.userAgent) || /[?&]nomotion=1/.test(location.search)) reduce = true;
  } catch (e) {}

  /* the x-dc template mounts asynchronously — poll briefly for real content */
  var tries = 0;
  function ready(fn) {
    var root = document.querySelector('.cas-root');
    var main = document.getElementById('content') || document.querySelector('main');
    if (root && main && main.children.length) { fn(); return; }
    if (++tries > 100) return;
    setTimeout(function () { ready(fn); }, 60);
  }

  document.addEventListener('DOMContentLoaded', function () {
    ready(function () {
      var g = window.gsap;
      if (!g || reduce) return;
      var ST = window.ScrollTrigger;
      if (ST) g.registerPlugin(ST);

      var qa = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
      var inView = function (el) { var r = el.getBoundingClientRect(); return r.top < (window.innerHeight || 800) * 0.9 && r.bottom > 0; };

      /* ---- 1. hero block: signature ink-wipe, then eyebrow/title/lead cascade ---- */
      var sig = document.querySelector('.sig-script');
      if (sig) {
        g.fromTo(sig,
          { clipPath: 'inset(0 100% -20% 0)', opacity: 1, rotate: -7 },
          { clipPath: 'inset(0 -10% -20% 0)', rotate: -4, duration: 1.3, ease: 'power2.inOut', delay: .25 });
      }
      var heroScope = document.querySelector('.about-hero > div:last-child') || document.querySelector('.page-hero .container');
      if (heroScope) {
        var heroBits = qa(':scope > .eyebrow, :scope > h1, :scope > .reveal-one > *, :scope > .lead, :scope > p', heroScope);
        if (heroBits.length) g.fromTo(heroBits, { opacity: 0, y: 26 }, { opacity: 1, y: 0, duration: .8, stagger: .1, ease: 'power3.out', delay: sig ? .5 : .15 });
      }
      var portrait = document.querySelector('.about-hero .portrait-wrap');
      if (portrait) {
        g.fromTo(portrait, { opacity: 0, x: -46, rotate: -3 }, { opacity: 1, x: 0, rotate: 0, duration: 1, ease: 'power3.out', delay: .2 });
        var arch = portrait.querySelector('.arch-duo img');
        if (arch) g.fromTo(arch, { scale: 1.16 }, { scale: 1, duration: 1.5, ease: 'power2.out', delay: .2 });
        var note = portrait.querySelector('.portrait-note');
        if (note) g.fromTo(note, { opacity: 0, scale: .4, rotate: 14 }, { opacity: 1, scale: 1, rotate: -3, duration: .7, ease: 'back.out(2)', delay: 1.05 });
      }

      /* ---- 2. section-title plaques: hang the workshop sign (matches homepage) ---- */
      qa('.section-title').forEach(function (t) {
        var left = t.classList.contains('is-left');
        var vars = { opacity: 0, y: -26, rotateZ: left ? -5 : 4, scale: .9, transformOrigin: left ? 'left top' : 'center top' };
        var play = function () { g.fromTo(t, vars, { opacity: 1, y: 0, rotateZ: 0, scale: 1, duration: .9, ease: 'elastic.out(1, .65)' }); };
        if (ST) { g.set(t, { opacity: 0 }); ST.create({ trigger: t, start: 'top 88%', once: true, onEnter: play }); }
      });

      /* ---- 3. grouped card/tile staggers ---- */
      var GROUPS = [
        '.lesson-grid .lesson-cell', '.cert-grid .cert-card', '.tier-grid .tier-card',
        '.blog-grid .blog-card', '.store-grid .art-card', '.sw-grid .sw-tile',
        '.acc-list .acc-item', '.contact-wrap > *', '.bio-layout > *', '.story-layout > *',
        '.syllabus .syl-row', '.mycourse-grid .mc-course', '.res-list .res-item',
        '.tc-grid .card', '.approach-grid .card', '.learn-list li'
      ];
      GROUPS.forEach(function (sel) {
        var els = qa(sel).filter(function (el) { return !el.__casM; });
        if (!els.length) return;
        els.forEach(function (el) { el.__casM = 1; });
        if (!ST) return;
        g.set(els, { opacity: 0, y: 42 });
        ST.batch(els, {
          start: 'top 90%', once: true,
          onEnter: function (batch) { g.to(batch, { opacity: 1, y: 0, duration: .7, stagger: .09, ease: 'power3.out', overwrite: true }); }
        });
        /* anything already in the first viewport plays immediately with a gentle cascade */
        var above = els.filter(inView);
        if (above.length) g.to(above, { opacity: 1, y: 0, duration: .7, stagger: .09, ease: 'power3.out', delay: .35, overwrite: true });
      });

      /* ---- 4. wide bands (signup / forest / dash hero) drift up ---- */
      qa('.signup-band, .band-forest, .band-oak, .dash-hero, .course-teacher').forEach(function (band) {
        if (!ST) return;
        g.set(band, { opacity: 0, y: 34 });
        var play = function () { g.to(band, { opacity: 1, y: 0, duration: .85, ease: 'power3.out' }); };
        if (inView(band)) { g.delayedCall(.3, play); }
        else ST.create({ trigger: band, start: 'top 88%', once: true, onEnter: play });
      });

      /* ---- 5. footer eases in once ---- */
      var foot = document.querySelector('.site-footer .inner');
      if (foot && ST) {
        g.set(foot, { opacity: 0, y: 26 });
        ST.create({ trigger: foot, start: 'top 96%', once: true, onEnter: function () { g.to(foot, { opacity: 1, y: 0, duration: .8, ease: 'power2.out' }); } });
      }

      /* ---- 6. accordion open/close: animate the body height ---- */
      qa('details.acc-item').forEach(function (d) {
        var body = d.querySelector('.acc-body');
        if (!body) return;
        var sum = d.querySelector('summary');
        sum.addEventListener('click', function (e) {
          if (reduce) return;
          e.preventDefault();
          if (d.open) {
            g.to(body, { height: 0, opacity: 0, duration: .3, ease: 'power2.in', onComplete: function () { d.open = false; g.set(body, { clearProps: 'all' }); } });
          } else {
            d.open = true;
            g.fromTo(body, { height: 0, opacity: 0 }, { height: 'auto', opacity: 1, duration: .42, ease: 'power3.out', onComplete: function () { g.set(body, { clearProps: 'height' }); } });
          }
        });
      });

      /* ---- 7. smooth-scroll for same-page anchors (nav FAQ → #faq etc.) ---- */
      document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href*="#"]');
        if (!a) return;
        var url = a.getAttribute('href') || '';
        var hashIdx = url.indexOf('#');
        if (hashIdx < 0) return;
        var path = url.slice(0, hashIdx);
        var here = location.pathname.split('/').pop();
        if (path && path !== here) return; /* different page — let the browser navigate */
        var target = document.getElementById(url.slice(hashIdx + 1));
        if (!target) return;
        e.preventDefault();
        var y = target.getBoundingClientRect().top + (window.pageYOffset || 0) - 96;
        try { window.scrollTo({ top: y, behavior: 'smooth' }); } catch (er) { window.scrollTo(0, y); }
        history.replaceState(null, '', '#' + url.slice(hashIdx + 1));
      });

      /* ---- 8. arriving on a #hash from another page: settle scroll under sticky nav ---- */
      if (location.hash) {
        var t = document.getElementById(location.hash.slice(1));
        if (t) setTimeout(function () {
          var y = t.getBoundingClientRect().top + (window.pageYOffset || 0) - 96;
          try { window.scrollTo({ top: y, behavior: 'smooth' }); } catch (er) { window.scrollTo(0, y); }
        }, 350);
      }
    });
  });
})();
