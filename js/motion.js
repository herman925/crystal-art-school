/* Crystal's Art School — shared GSAP motion pass.
   Loaded on every page. index.html keeps its own hero/section choreography,
   so there we only layer the micro-interactions (detected via .hero__slide).
   If GSAP is missing, the visitor prefers reduced motion, or we're running
   under automation/headless (QA screenshots), the page renders fully static. */
(function () {
  if (window.__casMotion) return; window.__casMotion = true;
  var reduce = false;
  try { reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}
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
      var isIndex = !!document.querySelector('.hero__slide'); /* homepage has its own scenes */
      var finePointer = false;
      try { finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches; } catch (e) {}

      /* ============================================================
         0. nav drops in + links cascade (every page, once per session feel)
         ============================================================ */
      var nav = document.querySelector('.site-nav');
      if (nav) {
        g.from(nav, { yPercent: -100, duration: .65, ease: 'power3.out' });
        var navBits = qa('.nav-links > li, .nav-auth > *', nav);
        if (navBits.length) g.from(navBits, { opacity: 0, y: -14, duration: .5, stagger: .045, ease: 'power2.out', delay: .3, clearProps: 'all' });
        var brand = nav.querySelector('.nav-brand img');
        if (brand) g.from(brand, { opacity: 0, scale: .6, rotate: -12, duration: .8, ease: 'back.out(1.8)', delay: .35, clearProps: 'all' });
      }

      /* ============================================================
         1. hover micro-interactions (all pages, index included)
         ============================================================ */
      if (finePointer) {
        /* magnetic primary buttons */
        qa('.btn.primary, .btn-big, .cc-btn.primary, .cover-btn.primary').forEach(function (b) {
          var qx = g.quickTo(b, 'x', { duration: .35, ease: 'power3.out' });
          var qy = g.quickTo(b, 'y', { duration: .35, ease: 'power3.out' });
          b.addEventListener('mousemove', function (e) {
            var r = b.getBoundingClientRect();
            qx((e.clientX - r.left - r.width / 2) * .18);
            qy((e.clientY - r.top - r.height / 2) * .3);
          });
          b.addEventListener('mouseleave', function () { qx(0); qy(0); });
        });

        /* card tilt — follows the cursor like a loose canvas on a wall */
        var TILT = '.lesson-cell .cover-card, .blog-card, .art-card, .mc-course, .tier-card, .share-card, .course-card, .bc-card, .cert-card';
        qa(TILT).forEach(function (c) {
          var rx = g.quickTo(c, 'rotationX', { duration: .4, ease: 'power2.out' });
          var ry = g.quickTo(c, 'rotationY', { duration: .4, ease: 'power2.out' });
          c.addEventListener('mousemove', function (e) {
            var r = c.getBoundingClientRect();
            g.set(c, { transformPerspective: 700 });
            ry(((e.clientX - r.left) / r.width - .5) * 7);
            rx(-((e.clientY - r.top) / r.height - .5) * 6);
          });
          c.addEventListener('mouseleave', function () { rx(0); ry(0); });
        });
      }

      /* ambient: the ✦ brand stars twinkle gently */
      qa('.eyebrow .star, .section-title .star').slice(0, 24).forEach(function (s, i) {
        g.to(s, { scale: 1.35, opacity: .55, duration: 1.1 + (i % 5) * .18, ease: 'sine.inOut', repeat: -1, yoyo: true, delay: (i % 7) * .4, transformOrigin: '50% 50%' });
      });

      if (isIndex) return; /* homepage: its inline script owns the rest */

      /* ============================================================
         2. hero block — signature ink-wipe, split-word headline, cascade
         ============================================================ */
      var sig = document.querySelector('.sig-script');
      if (sig) {
        g.fromTo(sig,
          { clipPath: 'inset(-25% 100% -25% -5%)', rotate: -6 },
          { clipPath: 'inset(-25% -8% -25% -5%)', rotate: -3, duration: 1.25, ease: 'power2.inOut', delay: .3, clearProps: 'clipPath' });
      }

      /* split the main headline into words that rise from behind a mask */
      var h1 = document.querySelector('.about-hero h1, .page-hero h1, .course-hero h1');
      if (h1 && !h1.__split) {
        h1.__split = 1;
        var words = h1.textContent.split(/\s+/).filter(Boolean);
        h1.innerHTML = words.map(function (w) {
          return '<span style="display:inline-block;overflow:hidden;vertical-align:bottom;"><span style="display:inline-block;">' + w.replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</span></span>';
        }).join(' ');
        g.from(qa(':scope > span > span', h1), { yPercent: 115, rotate: 4, duration: .85, stagger: .07, ease: 'power3.out', delay: sig ? .45 : .2 });
      }

      var heroScope = document.querySelector('.about-hero > div:last-child') || document.querySelector('.page-hero .container');
      if (heroScope) {
        var heroBits = qa(':scope > .eyebrow, :scope > .reveal-one > *, :scope > .lead, :scope > p, :scope > .crumbs, :scope > .course-meta-chips', heroScope);
        heroBits = heroBits.filter(function (el) { return el.tagName !== 'H1'; });
        if (heroBits.length) g.fromTo(heroBits, { opacity: 0, y: 26 }, { opacity: 1, y: 0, duration: .8, stagger: .09, ease: 'power3.out', delay: sig ? .55 : .3, clearProps: 'opacity,transform' });
      }

      var portrait = document.querySelector('.about-hero .portrait-wrap');
      if (portrait) {
        g.fromTo(portrait, { opacity: 0, x: -46, rotate: -3 }, { opacity: 1, x: 0, rotate: 0, duration: 1, ease: 'power3.out', delay: .25 });
        var arch = portrait.querySelector('.arch-duo img');
        if (arch) g.fromTo(arch, { scale: 1.16 }, { scale: 1, duration: 1.6, ease: 'power2.out', delay: .25 });
        var note = portrait.querySelector('.portrait-note');
        if (note) {
          g.fromTo(note, { opacity: 0, scale: .4, rotate: 14 }, { opacity: 1, scale: 1, rotate: -3, duration: .7, ease: 'back.out(2)', delay: 1.15 });
          g.to(note, { y: -6, rotate: -1, duration: 2.6, ease: 'sine.inOut', repeat: -1, yoyo: true, delay: 2 });
        }
        /* slow parallax drift while scrolling past */
        if (ST) g.to(portrait, { yPercent: 9, ease: 'none', scrollTrigger: { trigger: portrait, start: 'top top', end: 'bottom top', scrub: 1.2 } });
      }

      /* course-hero cover gets the Ingenioso masked wipe */
      var cover = document.querySelector('.course-cover');
      if (cover) {
        g.fromTo(cover, { clipPath: 'inset(0 100% 0 0)' }, { clipPath: 'inset(0 0% 0 0)', duration: 1.1, ease: 'power3.out', delay: .25, clearProps: 'clipPath' });
        var cimg = cover.querySelector('img');
        if (cimg) g.fromTo(cimg, { scale: 1.18, skewX: 3 }, { scale: 1, skewX: 0, duration: 1.4, ease: 'power3.out', delay: .25 });
      }

      /* page-hero wood texture drifts as you scroll away (depth) */
      var ph = document.querySelector('.page-hero');
      if (ph && ST) {
        g.to(ph, { backgroundPositionY: '65%', ease: 'none', scrollTrigger: { trigger: ph, start: 'top top', end: 'bottom top', scrub: 1 } });
        var phc = ph.querySelector('.container');
        if (phc) g.to(phc, { yPercent: 22, opacity: .35, ease: 'none', scrollTrigger: { trigger: ph, start: 'top top', end: 'bottom top', scrub: 1 } });
      }

      /* ============================================================
         3. section-title plaques — hang the workshop sign
         ============================================================ */
      qa('.section-title').forEach(function (t) {
        var left = t.classList.contains('is-left');
        var vars = { opacity: 0, y: -34, rotateZ: left ? -6 : 5, scale: .88, transformOrigin: left ? 'left top' : 'center top' };
        var play = function () {
          g.fromTo(t, vars, { opacity: 1, y: 0, rotateZ: 0, scale: 1, duration: 1, ease: 'elastic.out(1, .55)' });
        };
        if (ST) { g.set(t, { opacity: 0 }); ST.create({ trigger: t, start: 'top 88%', once: true, onEnter: play }); }
      });

      /* ============================================================
         4. image frames wipe in; card groups deal in with variety
         ============================================================ */
      /* masked wipes for framed photography */
      qa('.bio-frame, .contact-card').forEach(function (f, i) {
        if (!ST) return;
        var fromLeft = i % 2 === 0;
        g.set(f, { clipPath: fromLeft ? 'inset(0 100% 0 0)' : 'inset(0 0 0 100%)' });
        var img = f.querySelector('img');
        if (img) g.set(img, { scale: 1.16 });
        var play = function () {
          g.to(f, { clipPath: 'inset(0 0% 0 0%)', duration: 1, ease: 'power3.out', clearProps: 'clipPath' });
          if (img) g.to(img, { scale: 1, duration: 1.35, ease: 'power3.out' });
        };
        if (inView(f)) g.delayedCall(.45 + i * .18, play);
        else ST.create({ trigger: f, start: 'top 88%', once: true, onEnter: play });
      });

      /* grouped staggers — alternating drift directions so rows feel dealt, not faded */
      var GROUPS = [
        '.lesson-grid .lesson-cell', '.tier-grid .tier-card',
        '.blog-grid .blog-card', '.store-grid .art-card', '.sw-grid .sw-tile',
        '.acc-list .acc-item', '.contact-intro > *', '.bio-copy > *',
        '.syllabus .syl-item', '.mycourse-grid .mc-course', '.res-list .res-item',
        '.tc-grid .card', '.learn-list li', '.bc-row .bc-card', '.contact-channels li'
      ];
      GROUPS.forEach(function (sel, gi) {
        var els = qa(sel).filter(function (el) { return !el.__casM; });
        if (!els.length || !ST) return;
        els.forEach(function (el) { el.__casM = 1; });
        var alt = gi % 3; /* 0: rise, 1: slide-left, 2: slide-right */
        var fromVars = alt === 1 ? { opacity: 0, x: 54, rotate: 1.5 } : alt === 2 ? { opacity: 0, x: -54, rotate: -1.5 } : { opacity: 0, y: 48, rotate: gi % 2 ? .8 : -.8 };
        g.set(els, fromVars);
        var toVars = { opacity: 1, x: 0, y: 0, rotate: 0, duration: .75, stagger: .08, ease: 'power3.out', overwrite: true, clearProps: 'rotate' };
        ST.batch(els, {
          start: 'top 90%', once: true,
          onEnter: function (batch) { g.to(batch, toVars); }
        });
        var above = els.filter(inView);
        if (above.length) g.to(above, Object.assign({}, toVars, { delay: .4 }));
      });

      /* ============================================================
         5. wide bands sweep in; footer eases up
         ============================================================ */
      qa('.signup-band, .band-forest, .band-oak, .dash-hero, .course-teacher').forEach(function (band) {
        if (!ST) return;
        g.set(band, { opacity: 0, y: 44, scale: .985 });
        var play = function () { g.to(band, { opacity: 1, y: 0, scale: 1, duration: .9, ease: 'power3.out' }); };
        if (inView(band)) g.delayedCall(.35, play);
        else ST.create({ trigger: band, start: 'top 88%', once: true, onEnter: play });
      });

      var foot = document.querySelector('.site-footer .inner');
      if (foot && ST) {
        g.set(foot, { opacity: 0, y: 30 });
        ST.create({ trigger: foot, start: 'top 96%', once: true, onEnter: function () { g.to(foot, { opacity: 1, y: 0, duration: .8, ease: 'power2.out' }); } });
      }

      /* ============================================================
         6. accordion open/close with animated height + icon spin
         ============================================================ */
      qa('details.acc-item').forEach(function (d) {
        var body = d.querySelector('.acc-body');
        if (!body) return;
        var sum = d.querySelector('summary');
        sum.addEventListener('click', function (e) {
          e.preventDefault();
          if (d.open) {
            g.to(body, { height: 0, opacity: 0, duration: .28, ease: 'power2.in', onComplete: function () { d.open = false; g.set(body, { clearProps: 'all' }); } });
          } else {
            d.open = true;
            g.fromTo(body, { height: 0, opacity: 0, y: -6 }, { height: 'auto', opacity: 1, y: 0, duration: .45, ease: 'power3.out', onComplete: function () { g.set(body, { clearProps: 'height' }); } });
          }
        });
      });

      /* ============================================================
         7. same-page anchor smooth-scroll + cross-page hash arrival
         ============================================================ */
      document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href*="#"]');
        if (!a) return;
        var url = a.getAttribute('href') || '';
        var hashIdx = url.indexOf('#');
        if (hashIdx < 0) return;
        var path = url.slice(0, hashIdx);
        var here = location.pathname.split('/').pop();
        if (path && path !== here) return;
        var target = document.getElementById(url.slice(hashIdx + 1));
        if (!target) return;
        e.preventDefault();
        var y = target.getBoundingClientRect().top + (window.pageYOffset || 0) - 96;
        try { window.scrollTo({ top: y, behavior: 'smooth' }); } catch (er) { window.scrollTo(0, y); }
        history.replaceState(null, '', '#' + url.slice(hashIdx + 1));
      });

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
