/* Auto-generated from Claude Design handoff bundle.
   The original Component class wrapped its init in componentDidMount and
   only ran setInterval timers in componentWillUnmount. We replicate that
   pair without a runtime, and hoist the timer handles onto `window` so the
   unload hook can find them. */
(function () {
  function start() {
    var comp = {

  coursePool() {
    return [
      { img: 'cover1.jpg', title: 'Light & shadow on the head', tag: 'Oils', date: '2026-06-19', price: 42 },
      { img: 'cover2.jpg', title: 'Blocking in large shapes', tag: 'Oils', date: '2026-06-12', price: 36 },
      { img: 'cover3.jpg', title: 'Mixing greens from scratch', tag: 'Watercolour', date: '2026-06-05', price: 29 },
      { img: 'cover4.jpg', title: 'Loading a brush properly', tag: 'Watercolour', date: '2026-05-22', price: 29 },
      { img: 'Liquidwatercolor.jpg', title: 'Wet-on-wet washes', tag: 'Watercolour', date: '2026-05-10', price: 34 },
      { img: 'Postercolor.jpg', title: 'Flat & opaque in poster colour', tag: 'Poster', date: '2026-04-28', price: 38 },
      { img: 'Sketchbook.jpg', title: 'Fill a sketchbook in 30 days', tag: 'Drawing', date: '2026-04-15', price: 24 },
      { img: 'BrushA.jpg', title: 'Brush & ink studies', tag: 'Ink', date: '2026-04-02', price: 44 },
      { img: 'BrushB.jpg', title: 'Brush care & studio habits', tag: 'Acrylic', date: '2026-03-20', price: 19 },
      { img: 'Rubber.jpg', title: 'Erasing as a drawing tool', tag: 'Drawing', date: '2026-03-08', price: 24 },
      { img: 'cover1.jpg', title: 'Portrait value structure', tag: 'Oils', date: '2026-02-24', price: 46 },
      { img: 'cover3.jpg', title: 'Skies in loose watercolour', tag: 'Watercolour', date: '2026-02-10', price: 32 }
    ];
  },

  componentDidMount() {
    const root = document.querySelector('.cas-root');
    if (!root) return;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---- hero slides (autoplay + arrows + counter) ---- */
    (function () {
      const slides = Array.from(document.querySelectorAll('.hero__slide'));
      if (!slides.length) return;
      const cur = document.querySelector('.hero__counter-current');
      let hi = 0, timer = null;
      const show = (i) => { hi = (i + slides.length) % slides.length; slides.forEach((s, k) => s.classList.toggle('is-active', k === hi)); if (cur) cur.textContent = String(hi + 1).padStart(2, '0'); };
      const restart = () => { if (timer) clearInterval(timer); if (!reduce) timer = setInterval(() => show(hi + 1), 6000); };
      document.querySelectorAll('[data-hero]').forEach(b => b.addEventListener('click', () => { show(b.dataset.hero === 'prev' ? hi - 1 : hi + 1); restart(); }));
      show(0); restart();
    })();

    /* ---- hero entrance: GSAP-driven (content visible by default; this is enhancement) ---- */
    if (!reduce) {
      const heroIn = () => {
        const g = window.gsap;
        if (!g) { setTimeout(heroIn, 100); return; }
        const active = document.querySelector('.hero__slide.is-active') || document.querySelector('.hero__slide');
        if (!active) return;
        const lg = active.querySelector('.hero__img--large'), sm = active.querySelector('.hero__img--small');
        const tt = active.querySelector('.hero__title'), st = active.querySelector('.hero__subtitle'), ct = active.querySelector('.hero__cta');
        const tl = g.timeline();
        if (lg) tl.from(lg, { clipPath: 'inset(0 100% 0 0)', skewX: 3, duration: 1.1, ease: 'power3.out' }, 0.2);
        if (sm) tl.from(sm, { clipPath: 'inset(0 100% 0 0)', duration: 1.2, ease: 'power3.out' }, 0.5);
        if (tt) tl.from(tt, { opacity: 0, y: 16, duration: .8, ease: 'power2.out' }, 0.65);
        if (st) tl.from(st, { opacity: 0, y: 16, duration: .8, ease: 'power2.out' }, 0.78);
        if (ct) tl.from(ct, { opacity: 0, y: 16, duration: .8, ease: 'power2.out' }, 0.9);
      };
      heroIn();
    }

    /* ---- Cinematic scroll choreography (GSAP) ---- */
    if (!reduce) {
      const gInit = () => {
        const g = window.gsap;
        if (!g) { setTimeout(gInit, 100); return; }
        document.querySelectorAll('.reveal-up').forEach(e => e.classList.remove('reveal-up'));
        const q = (s, r) => (r || document).querySelector(s);
        const qa = (s, r) => Array.from((r || document).querySelectorAll(s));
        const secs = qa('#content > section');

        const scenes = [];
        const scene = (trigger, hide, play) => { if (!trigger) return; if (hide) g.set(hide.els, hide.vars); scenes.push({ trigger, play, done: false }); };

        /* section titles — "hang the workshop sign": tilt + drop + settle */
        qa('.section-title').forEach(t => {
          const left = t.classList.contains('is-left');
          scene(t, { els: t, vars: { opacity: 0 } }, () =>
            g.fromTo(t, { opacity: 0, y: -26, rotateZ: left ? -5 : 4, scale: .9, transformOrigin: left ? 'left top' : 'center top' },
              { opacity: 1, y: 0, rotateZ: 0, scale: 1, duration: .9, ease: 'elastic.out(1, .65)' }));
        });

        /* NEW RELEASE — three individual beats */
        const relSec = secs[0];
        if (relSec) {
          const fv = q('.free-video', relSec), rel = q('.rel-carousel', relSec), about = q('.about-col', relSec);
          const arch = q('.arch-duo', about), cap = q('.arch-cap', about);
          g.set([fv, rel, about].filter(Boolean), { opacity: 0 });
          scene(q('.release-trio', relSec), null, () => {
            const tl = g.timeline();
            if (fv) tl.fromTo(fv, { opacity: 0, x: -70, rotateY: 12, transformPerspective: 700 }, { opacity: 1, x: 0, rotateY: 0, duration: .8, ease: 'power3.out' });
            if (rel) tl.fromTo(rel, { opacity: 0, y: 80, scale: .85 }, { opacity: 1, y: 0, scale: 1, duration: .9, ease: 'power4.out' }, '-=0.5');
            if (about) tl.fromTo(about, { opacity: 0, y: 110, scale: .8, rotateZ: 4 }, { opacity: 1, y: 0, scale: 1, rotateZ: 0, duration: 1, ease: 'back.out(1.5)' }, '-=0.3');
            if (arch) tl.fromTo(arch, { scale: 1.18 }, { scale: 1, duration: 1.1, ease: 'power2.out' }, '<');
            if (cap) tl.fromTo(qa(':scope > *', cap), { opacity: 0, y: 14 }, { opacity: 1, y: 0, duration: .5, stagger: .08, ease: 'power2.out' }, '-=0.5');
            return tl;
          });
        }

        /* SHOP — deal the course cards, slide the gallery */
        const shopSec = secs[1];
        if (shopSec) {
          const cards = qa('.shop-rail .course-card', shopSec);
          const gal = q('.shop-gallery', shopSec);
          g.set(cards.concat(gal ? [gal] : []), { opacity: 0 });
          scene(q('.shop-layout', shopSec), null, () => {
            const tl = g.timeline();
            if (cards.length) tl.fromTo(cards, { opacity: 0, x: 70, rotateZ: 5, transformOrigin: 'right center' }, { opacity: 1, x: 0, rotateZ: 0, duration: .7, ease: 'power3.out', stagger: .12 });
            if (gal) tl.fromTo(gal, { opacity: 0, x: 50, scale: .92 }, { opacity: 1, x: 0, scale: 1, duration: .8, ease: 'power3.out' }, '-=0.4');
            return tl;
          });
        }

        /* SHARE — posts glide in, TOOLS disc spins on */
        const shareSec = secs[2];
        if (shareSec) {
          const posts = qa('.share-rail .share-card', shareSec);
          const tools = q('.tools-btn', shareSec);
          g.set(posts.concat(tools ? [tools] : []), { opacity: 0 });
          scene(q('.share-layout', shareSec), null, () => {
            const tl = g.timeline();
            if (posts.length) tl.fromTo(posts, { opacity: 0, y: 60, scale: .94 }, { opacity: 1, y: 0, scale: 1, duration: .7, ease: 'power3.out', stagger: .14 });
            if (tools) tl.fromTo(tools, { opacity: 0, scale: .3, rotate: -120 }, { opacity: 1, scale: 1, rotate: 0, duration: .9, ease: 'back.out(1.7)', onComplete: () => g.to(tools, { y: -8, duration: 2.4, ease: 'sine.inOut', repeat: -1, yoyo: true }) }, '-=0.4');
            return tl;
          });
        }

        /* COMMUNITY band — photo & text split in */
        secs.forEach(sec => {
          const band = q('.band-forest', sec);
          if (!band) return;
          const photo = band.children[0], copy = band.children[1], cta = band.children[2];
          g.set(band, { opacity: 0 });
          scene(band, null, () => {
            const tl = g.timeline();
            tl.fromTo(band, { opacity: 0 }, { opacity: 1, duration: .3 });
            if (photo) tl.fromTo(photo, { opacity: 0, x: -50, rotate: -4 }, { opacity: 1, x: 0, rotate: 0, duration: .8, ease: 'power3.out' }, '<');
            if (copy) tl.fromTo(copy, { opacity: 0, x: 30 }, { opacity: 1, x: 0, duration: .7, ease: 'power2.out' }, '-=0.5');
            if (cta) tl.fromTo(cta, { opacity: 0, scale: .8 }, { opacity: 1, scale: 1, duration: .6, ease: 'back.out(1.6)' }, '-=0.3');
            return tl;
          });
        });

        /* TESTIMONIALS — pop up staggered */
        const cards = qa('.testi-card');
        if (cards.length) {
          g.set(cards, { opacity: 0 });
          scene(q('.testi-grid'), null, () => g.fromTo(cards, { opacity: 0, y: 56, scale: .92 }, { opacity: 1, y: 0, scale: 1, duration: .7, ease: 'back.out(1.4)', stagger: .12 }));
        }

        /* continuous: hero depth parallax */
        const heroLarge = q('.hero__img--large'), heroSmall = q('.hero__img--small'), heroPanel = q('.hero__panel');
        let firstPass = true;
        const drive = () => {
          const vh = window.innerHeight || document.documentElement.clientHeight;
          let staged = 0;
          scenes.forEach(s => {
            if (s.done) return;
            const r = s.trigger.getBoundingClientRect();
            if (r.top < vh * 0.86 && r.bottom > 30) {
              s.done = true;
              if (firstPass) { g.delayedCall(staged * 0.45, s.play); staged++; }
              else s.play();
            }
          });
          firstPass = false;
          const sc = window.pageYOffset || document.documentElement.scrollTop || 0;
          if (heroLarge && sc < vh * 1.2) { g.set(heroLarge, { y: sc * 0.12 }); if (heroSmall) g.set(heroSmall, { y: sc * 0.22 }); if (heroPanel) g.set(heroPanel, { y: sc * 0.05 }); }
        };
        setTimeout(drive, 260);
        window.addEventListener('scroll', drive, { passive: true });
        document.addEventListener('scroll', drive, true);
        window.addEventListener('resize', drive);
      };
      gInit();
    }

    /* ---- non-GSAP fallback: ensure section ornaments reveal ---- */
    {
      const titleEls = Array.from(document.querySelectorAll('.section-title, .arch-duo'));
      const check = () => {
        const vh = window.innerHeight || document.documentElement.clientHeight;
        for (const el of titleEls) {
          if (el.classList.contains('in-view')) continue;
          const r = el.getBoundingClientRect();
          if (r.top < vh * 0.92 && r.bottom > 0) el.classList.add('in-view');
        }
      };
      check();
      window.addEventListener('scroll', check, { passive: true });
      document.addEventListener('scroll', check, true);
      setTimeout(check, 160);
    }
    const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;');
    const cartSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1.6"></circle><circle cx="19" cy="21" r="1.6"></circle><path d="M2 3h3l2.6 12.6a1.8 1.8 0 0 0 1.8 1.4h8.9a1.8 1.8 0 0 0 1.8-1.4L22 7H6"></path></svg>';

    /* ---- theme switch (persists across pages) ---- */
    const THEME_KEY = 'cas-theme';
    const valid = ['A','B','C','D'];
    const applyTheme = (t) => {
      root.setAttribute('data-theme', t);
      document.querySelectorAll('.theme-switch button').forEach(b => b.classList.toggle('active', b.dataset.theme === t));
    };
    let saved = (valid.includes('A') ? 'A' : 'A');
    try { const s = localStorage.getItem(THEME_KEY); if (s && valid.includes(s)) saved = s; } catch (e) {}
    applyTheme(saved);
    document.querySelectorAll('.theme-switch button').forEach(b => {
      b.addEventListener('click', () => { const t = b.dataset.theme; applyTheme(t); try { localStorage.setItem(THEME_KEY, t); } catch (e) {} });
    });

    /* ---- mobile nav sheet ---- */
    const burger = document.querySelector('.nav-burger');
    const sheet = document.querySelector('.nav-sheet');
    if (burger && sheet) {
      burger.addEventListener('click', () => { const open = sheet.classList.toggle('open'); burger.setAttribute('aria-expanded', open ? 'true' : 'false'); });
      sheet.addEventListener('click', (e) => { if (e.target.tagName === 'A') sheet.classList.remove('open'); });
    }

    /* ---- cart badge (reads shared cas-cart array; tier add handled by site-extras) ---- */
    const cartCount = () => { try { return (JSON.parse(localStorage.getItem('cas-cart') || '[]')).reduce((a, i) => a + (i.qty || 1), 0); } catch (er) { return 0; } };
    const renderCart = () => document.querySelectorAll('.cart-badge').forEach(b => { const n = cartCount(); b.textContent = n; b.style.display = n > 0 ? 'grid' : 'none'; });
    renderCart();

    /* ---- New Release: 3 most recent in a mini carousel ---- */
    const pool = this.coursePool();
    const recent = pool.slice().sort((a, b) => b.date.localeCompare(a.date)).slice(0, 3);
    const relCar = document.getElementById('rel-carousel');
    const relDots = document.getElementById('rel-dots');
    if (relCar) {
      recent.forEach((c, i) => {
        const slide = document.createElement('div');
        slide.className = 'rel-slide' + (i === 0 ? ' is-active' : '');
        slide.innerHTML = '<img src="img/' + c.img + '" alt="' + esc(c.title) + '">' +
          '<div class="rel-title">' + esc(c.title) + '</div>' +
          '<div class="rel-actions">' +
            '<button class="cc-btn primary" type="button" data-add-to-cart data-tiered data-price="' + c.price + '">' + cartSvg + ' Add to cart</button>' +
            '<a class="cc-btn secondary" href="lesson.html">View <span class="arr" aria-hidden="true">→</span></a>' +
          '</div>';
        relCar.appendChild(slide);
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.setAttribute('aria-label', 'Show release ' + (i + 1));
        if (i === 0) dot.className = 'active';
        relDots.appendChild(dot);
      });
      const relSlides = Array.from(relCar.querySelectorAll('.rel-slide'));
      const relDotEls = Array.from(relDots.querySelectorAll('button'));
      let ri = 0, rTimer = null;
      const showRel = (i) => {
        ri = (i + relSlides.length) % relSlides.length;
        relSlides.forEach((s, k) => s.classList.toggle('is-active', k === ri));
        relDotEls.forEach((d, k) => d.classList.toggle('active', k === ri));
      };
      relDotEls.forEach((d, k) => d.addEventListener('click', () => { showRel(k); restartRel(); }));
      const restartRel = () => { if (rTimer) clearInterval(rTimer); if (!reduce) rTimer = setInterval(() => showRel(ri + 1), 6000); };
      restartRel();
      this._relTimer = () => { if (rTimer) clearInterval(rTimer); };
    }

    /* ---- Shop all courses: 10 random course cards (3 visible) + 3 photo previews ---- */
    const shuffle = (arr) => { const a = arr.slice(); for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); const t = a[i]; a[i] = a[j]; a[j] = t; } return a; };
    const shopRail = document.getElementById('shop-rail');
    if (shopRail) {
      const courses = shuffle(pool).slice(0, 10);
      courses.forEach((c, idx) => {
        const ph = courses[(idx + 1) % courses.length];
        const card = document.createElement('div');
        card.className = 'course-card';
        card.innerHTML = '<div class="cc-cover"><img src="img/' + c.img + '" alt="' + esc(c.title) + '"></div>' +
          '<div class="cc-pad"><div class="cc-meta">' + esc(c.tag) + '</div><h3>' + esc(c.title) + '</h3>' +
          '<div class="cc-row"><span class="cc-price">£' + c.price + '</span>' +
          '<button class="cc-btn primary" type="button" data-add-to-cart data-tiered data-title="' + esc(c.title) + '" data-price="' + c.price + '" data-img="' + c.img + '">' + cartSvg + ' Add</button></div></div>' +
          '<div class="cc-photo"><img src="img/' + ph.img + '" alt=""></div>';
        shopRail.appendChild(card);
      });
      const galTile = document.querySelector('.shop-gallery');
      const firstCard = shopRail.querySelector('.course-card');
      if (galTile && firstCard) {
        const sizeGallery = () => { galTile.style.height = Math.round(firstCard.getBoundingClientRect().height * 0.66) + 'px'; };
        sizeGallery();
        window.addEventListener('resize', sizeGallery);
        if (window.requestAnimationFrame) requestAnimationFrame(sizeGallery);
        setTimeout(sizeGallery, 300);
      }
    }

    /* ---- paged rail arrows (shop + share) ---- */
    const initPager = (rail, sel, prevSel, nextSel, perPage) => {
      if (!rail) return;
      const car = rail.closest(sel);
      if (!car) return;
      let page = 0;
      const apply = () => {
        const kids = Array.from(rail.children);
        const pages = Math.max(1, Math.ceil(kids.length / perPage));
        page = Math.max(0, Math.min(page, pages - 1));
        const first = kids[0];
        const cardW = first ? first.getBoundingClientRect().width : 0;
        const gap = parseFloat(getComputedStyle(rail).gap) || 18;
        rail.style.transform = 'translateX(' + (-(page * perPage) * (cardW + gap)) + 'px)';
        const prev = car.querySelector(prevSel), next = car.querySelector(nextSel);
        if (prev) prev.disabled = page <= 0;
        if (next) next.disabled = page >= pages - 1;
      };
      const p = car.querySelector(prevSel), n = car.querySelector(nextSel);
      if (p) p.addEventListener('click', () => { page--; apply(); });
      if (n) n.addEventListener('click', () => { page++; apply(); });
      window.addEventListener('resize', apply);
      setTimeout(apply, 60);
    };
    initPager(shopRail, '.shop-carousel', '[data-shop="prev"]', '[data-shop="next"]', 3);
    initPager(document.getElementById('share-rail'), '.share-carousel', '[data-share="prev"]', '[data-share="next"]', 2);

    /* ---- carousel pagers already wired above ---- */
  },

  componentWillUnmount() { if (this._relTimer) this._relTimer(); }
    };
    if (comp.componentDidMount) comp.componentDidMount();
    window.__casComponentWillUnmount = comp.componentWillUnmount || function () {};

  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
  // cleanup if the page is hidden
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden' && window.__casComponentWillUnmount) {
      window.__casComponentWillUnmount();
    }
  });
})();
