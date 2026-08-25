/* Crystal's Art School — Showcase.
   Ported from the prototype's Showcase.dc.html componentDidMount. Three jobs:
   the pendulum physics on the pinned prints, the medium filter, and the lightbox.

   The wall itself is rendered by inc/cas-showcase.php, not here. The prototype
   built its tiles in JS because it was a static page with its data inline; on the
   server the pinning is written into the markup, so the board is complete and
   correctly laid out before this file runs and with JavaScript off. */
(function () {
  'use strict';

  var grid = document.getElementById('sw-grid');

  /* The reveal animations are opt-in through .in-view — the elements are already
     opaque, so a page without this is not blank, only still. */
  Array.prototype.forEach.call(
    document.querySelectorAll('#sw-section .reveal, #content .reveal-one, #content .section-title'),
    function (el) { el.classList.add('in-view'); }
  );

  if (!grid) { return; }

  var tiles = Array.prototype.slice.call(grid.querySelectorAll('.pol-tile'));
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ------------------------------------------------------------------
     Scroll-driven physics — the wall is STILL at rest. Scrolling shakes it:
     · scroll down = a moment of lost gravity: prints float up and flip a little,
       randomly clockwise / anticlockwise, faster scroll = faster sway
     · scroll up = a stronger downward tug
     · overlapping prints rub, so their swing couples
     Hover never interferes.
     ------------------------------------------------------------------ */
  if (!reduce && tiles.length) {
    var rnd = function (a, b) { return a + Math.random() * (b - a); };

    var sim = tiles.map(function (el) {
      return {
        el: el,
        base: parseFloat(el.style.getPropertyValue('--rot')) || 0,
        th: 0,
        w: 0,
        k: rnd(7, 12),                        /* rotational spring, back to rest */
        c: rnd(1.6, 2.4),                     /* damping — settles quickly */
        dir: Math.random() < 0.5 ? -1 : 1,    /* preferred flip direction */
        m: rnd(0.7, 1.4)                      /* "mass" — lighter prints react more */
      };
    });

    var pairs = [];
    var findPairs = function () {
      pairs = [];
      var boxes = tiles.map(function (t) { return t.getBoundingClientRect(); });
      for (var a = 0; a < boxes.length; a++) {
        for (var b = a + 1; b < boxes.length; b++) {
          var A = boxes[a], B = boxes[b];
          if (A.width && A.left < B.right - 8 && B.left < A.right - 8 && A.top < B.bottom - 8 && B.top < A.bottom - 8) {
            pairs.push([a, b]);
          }
        }
      }
    };
    setTimeout(findPairs, 450);
    window.addEventListener('resize', function () { setTimeout(findPairs, 350); });

    /* scroll and wheel feed the impulse for the next frame */
    var pending = 0;
    var lastScrollY = window.pageYOffset || 0;
    window.addEventListener('scroll', function () {
      var yNow = window.pageYOffset || document.documentElement.scrollTop || 0;
      pending += yNow - lastScrollY;
      lastScrollY = yNow;
    }, { passive: true });
    window.addEventListener('wheel', function (e) { pending += e.deltaY * 0.35; }, { passive: true });

    var last = performance.now();
    var step = function (now) {
      var dt = Math.min(0.05, (now - last) / 1000);
      last = now;

      var kick = Math.max(-36, Math.min(36, pending));
      pending = 0;
      if (kick) {
        sim.forEach(function (s) {
          /* a light swing around the pin, random cw/ccw, scaled by scroll speed */
          s.w += s.dir * (0.5 + Math.random()) * kick * 0.018 / s.m;
        });
        if (Math.random() < 0.25) { sim[(Math.random() * sim.length) | 0].dir *= -1; }
      }

      sim.forEach(function (s) { s.w += (-s.k * s.th - s.c * s.w) * dt; });

      /* friction where papers overlap: drag swing velocities toward each other */
      var mu = Math.min(1, 5 * dt);
      pairs.forEach(function (pr) {
        var a = sim[pr[0]], b = sim[pr[1]];
        var d = (b.w - a.w) * 0.5 * mu;
        a.w += d;
        b.w -= d;
      });

      /* rotation ONLY, pivoting at the pin (transform-origin = --pinx / -6px), so
         the pin stays anchored to the board while the print swings */
      sim.forEach(function (s) {
        s.th += s.w * dt * 2;
        if (Math.abs(s.th) < 0.02 && Math.abs(s.w) < 0.02) {
          s.th = 0;
          s.w = 0;
          s.el.style.transform = 'rotate(' + s.base + 'deg)';
        } else {
          s.el.style.transform = 'rotate(' + (s.base + s.th).toFixed(3) + 'deg)';
        }
      });

      requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }

  /* ------------------------------------------------------------------
     Medium filter. The chips are rendered by PHP and only exist when two or more
     media are in use, so there is nothing to build here — only to bind.
     ------------------------------------------------------------------ */
  var filter = document.getElementById('sw-filter');
  var emptyEl = document.getElementById('sw-empty');
  if (filter) {
    filter.addEventListener('click', function (e) {
      var btn = e.target.closest ? e.target.closest('.fopt') : null;
      if (!btn) { return; }

      Array.prototype.forEach.call(filter.querySelectorAll('.fopt'), function (b) {
        b.classList.toggle('active', b === btn);
        b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
      });

      var val = btn.getAttribute('data-val');
      var shown = 0;
      tiles.forEach(function (t) {
        var show = (val === 'all' || t.getAttribute('data-med') === val);
        t.style.display = show ? '' : 'none';
        if (show) { shown++; }
      });

      /* .no-results is display:none in the bundle, so '' restores the hidden state */
      if (emptyEl) { emptyEl.style.display = shown ? '' : 'block'; }
      if (window.CasAnnounce) {
        window.CasAnnounce(shown ? shown + (shown === 1 ? ' print' : ' prints') + ' shown.' : 'Nothing in this medium yet.');
      }
      /* re-find the overlaps, or the friction still couples prints that no longer touch */
      try { window.dispatchEvent(new Event('resize')); } catch (err) {}
    });
  }

  /* ------------------------------------------------------------------
     Lightbox
     ------------------------------------------------------------------ */
  var modal = document.getElementById('sw-modal');
  if (!modal) { return; }

  var mImg = document.getElementById('swm-img');
  var mFig = document.getElementById('swm-figcap');
  var mAva = document.getElementById('swm-ava');
  var mCap = document.getElementById('swm-cap');
  var opener = null;

  var AVA_COLORS = ['#9a6436', '#4d6625', '#5e4a3d', '#a96b38', '#6b7a4a', '#876d5c', '#7e4c27', '#8a9a6b'];
  var avaColor = function (name) {
    var h = 0;
    for (var i = 0; i < name.length; i++) { h = (h * 31 + name.charCodeAt(i)) >>> 0; }
    return AVA_COLORS[h % AVA_COLORS.length];
  };

  var FOCUS_SEL = 'a[href],button:not([disabled]),[tabindex]:not([tabindex="-1"])';
  var focusables = function () {
    return Array.prototype.filter.call(
      modal.querySelectorAll(FOCUS_SEL),
      function (el) { return el.getClientRects().length > 0; }
    );
  };

  var open = function (tile) {
    var title = tile.getAttribute('data-title') || '';
    var name = tile.getAttribute('data-name') || '';
    var med = tile.getAttribute('data-med-label') || '';
    var nth = tile.getAttribute('data-index') || '';
    var thumb = tile.querySelector('.pol-photo img');

    /* srcset and sizes go in before src, or the browser picks a candidate from a
       srcset it has not been given yet. The dialog paints far larger than the
       194px tile, so it asks for a bigger rung of the same ladder. */
    if (thumb && thumb.srcset) {
      mImg.srcset = thumb.srcset;
      mImg.sizes = 'min(1080px, 94vw)';
    } else {
      mImg.removeAttribute('srcset');
      mImg.removeAttribute('sizes');
    }
    mImg.src = tile.getAttribute('data-full') || (thumb ? (thumb.currentSrc || thumb.src) : '');
    mImg.alt = title
      ? (title + (name ? ' — student work by ' + name : ''))
      : 'Student artwork ' + nth;

    /* An absent field is not written. The prototype shipped a caption reading
       " ·  · " and an avatar disc with no initial in it. */
    if (name) {
      mAva.textContent = name.charAt(0);
      mAva.style.background = avaColor(name);
      mAva.style.display = '';
    } else {
      mAva.textContent = '';
      mAva.style.display = 'none';
    }

    var bits = [title, name, med].filter(Boolean);
    mCap.textContent = bits.join(' · ');
    mFig.style.display = bits.length ? '' : 'none';

    modal.setAttribute('aria-label', title ? title + ' — student artwork' : 'Student artwork ' + nth);
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    opener = tile;
    var close_btn = modal.querySelector('.sw-close');
    if (close_btn) { close_btn.focus(); }
  };

  var close = function () {
    if (!modal.classList.contains('show')) { return; }
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (opener && opener.focus) { try { opener.focus(); } catch (err) {} }
    opener = null;
  };

  grid.addEventListener('click', function (e) {
    var tile = e.target.closest ? e.target.closest('.pol-tile') : null;
    if (tile) { open(tile); }
  });

  modal.addEventListener('click', function (e) {
    if (e.target === modal || (e.target.closest && e.target.closest('.sw-close'))) { close(); }
  });

  /* The dialog claims aria-modal="true", so Tab has to stay inside it. The
     prototype shipped without this and Tab walked straight out into the wall. */
  modal.addEventListener('keydown', function (e) {
    if (e.key !== 'Tab' || !modal.classList.contains('show')) { return; }
    var f = focusables();
    if (!f.length) { e.preventDefault(); return; }
    var first = f[0], last2 = f[f.length - 1], a = document.activeElement;
    if (e.shiftKey && (a === first || !modal.contains(a))) { e.preventDefault(); last2.focus(); }
    else if (!e.shiftKey && (a === last2 || !modal.contains(a))) { e.preventDefault(); first.focus(); }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('show')) { close(); }
  });
}());
