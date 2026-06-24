/* Auto-generated from Claude Design handoff bundle.
   The original Component class wrapped its init in componentDidMount and
   only ran setInterval timers in componentWillUnmount. We replicate that
   pair without a runtime, and hoist the timer handles onto `window` so the
   unload hook can find them. */
(function () {
  function start() {
    var comp = {

  pieces() {
    return [
      { title: 'Harbour at Dusk', img: 'cover1.jpg', tags: ['Oil', 'Landscape'], price: 320, date: '2026-06-18', orig: true, size: '40 × 50 cm', desc: 'Oil on canvas — a working harbour as the light drops, painted on location over three evenings. Warm coppers held against a cooling sky.' },
      { title: 'Liquid Sky', img: 'Liquidwatercolor.jpg', tags: ['Watercolour', 'Abstract'], price: 180, date: '2026-06-09', orig: false, size: 'A3 print', desc: 'Giclée print of a loose wet-on-wet study. Limited run of 40, signed and numbered on archival cotton rag.' },
      { title: 'Studio Brushes', img: 'BrushA.jpg', tags: ['Ink', 'Still life'], price: 95, date: '2026-05-30', orig: false, size: 'A4 print', desc: 'Pen and wash study of well-worn studio brushes. A quiet object portrait — the tools that do the work.' },
      { title: 'Quiet Shore', img: 'cover3.jpg', tags: ['Watercolour', 'Landscape'], price: 240, date: '2026-05-21', orig: true, size: '30 × 40 cm', desc: 'Original watercolour on 300gsm cold-press. A single graded wash sky over a low tide, painted in one sitting.' },
      { title: 'Poster Study No.4', img: 'Postercolor.jpg', tags: ['Poster', 'Abstract'], price: 120, date: '2026-05-08', orig: false, size: 'A3 print', desc: 'Flat, opaque poster-colour shapes — a teaching piece on simplifying a subject to its honest blocks.' },
      { title: 'Open Sketchbook', img: 'Sketchbook.jpg', tags: ['Drawing', 'Still life'], price: 80, date: '2026-04-26', orig: false, size: 'A4 print', desc: 'A page from a daily sketchbook, reproduced full-size. Graphite and a touch of wash.' },
      { title: 'Block-in Head', img: 'cover4.jpg', tags: ['Oil', 'Portrait'], price: 360, date: '2026-04-12', orig: true, size: '35 × 45 cm', desc: 'Original alla-prima portrait block-in. Big shapes, honest values — left deliberately unfinished where the light lands.' },
      { title: 'Field Greens', img: 'cover2.jpg', tags: ['Oil', 'Landscape'], price: 280, date: '2026-03-29', orig: true, size: '40 × 40 cm', desc: 'Oil on board. An exercise in mixing greens from a limited palette — no tube green anywhere in it.' },
      { title: 'Worn Eraser', img: 'Rubber.jpg', tags: ['Drawing', 'Still life'], price: 70, date: '2026-03-15', orig: false, size: 'A5 print', desc: 'A small, fond study of a kneaded eraser at the end of its life. Subtractive drawing about a subtractive tool.' },
      { title: 'Two Brushes', img: 'BrushB.jpg', tags: ['Ink', 'Still life'], price: 110, date: '2026-02-28', orig: false, size: 'A4 print', desc: 'Ink line and wash. Two rounds, crossed — the workhorse and the detail brush.' },
      { title: 'Morning Wash', img: 'cover3.jpg', tags: ['Watercolour', 'Landscape'], price: 200, date: '2026-02-14', orig: false, size: 'A3 print', desc: 'Print of a calm morning estuary. The kind of soft gradient the free lesson teaches you to lay down.' },
      { title: 'Portrait in Umber', img: 'ProfilePic.jpg', tags: ['Oil', 'Portrait'], price: 340, date: '2026-01-30', orig: true, size: '35 × 45 cm', desc: 'Original monochrome oil portrait in burnt umber — a study in pure value before any colour enters.' }
    ];
  },

  componentDidMount() {
    const root = document.querySelector('.cas-root');
    if (!root) return;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');

    /* theme switch */
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

    /* mobile nav sheet */
    const burger = document.querySelector('.nav-burger');
    const sheet = document.querySelector('.nav-sheet');
    if (burger && sheet) {
      burger.addEventListener('click', () => { const open = sheet.classList.toggle('open'); burger.setAttribute('aria-expanded', open ? 'true' : 'false'); });
      sheet.addEventListener('click', (e) => { if (e.target.tagName === 'A') sheet.classList.remove('open'); });
    }

    /* cart (array of items in localStorage) */
    const CART = 'cas-cart';
    const readCart = () => { try { return JSON.parse(localStorage.getItem(CART) || '[]'); } catch (e) { return []; } };
    const writeCart = (c) => { try { localStorage.setItem(CART, JSON.stringify(c)); } catch (e) {} };
    const renderCart = () => { const n = readCart().reduce((a, i) => a + i.qty, 0); document.querySelectorAll('.cart-badge').forEach(b => { b.textContent = n; b.style.display = n > 0 ? 'grid' : 'none'; }); };
    renderCart();
    document.addEventListener('click', (e) => {
      const add = e.target.closest('[data-add-to-cart]');
      if (!add) return;
      e.preventDefault();
      const c = readCart();
      const title = add.dataset.cartTitle || 'Item';
      const ex = c.find(x => x.title === title);
      if (ex) ex.qty++; else c.push({ title, price: +(add.dataset.cartPrice || 0), img: add.dataset.cartImg || '', kind: add.dataset.cartKind || 'Artwork', qty: 1 });
      writeCart(c); renderCart();
      if (add.tagName === 'BUTTON') { const o = add.textContent; add.textContent = 'Added ✓'; add.disabled = true; setTimeout(() => { add.textContent = o; add.disabled = false; }, 1100); }
      document.querySelectorAll('.cart-btn').forEach(btn => { btn.style.transition = 'transform .18s'; btn.style.transform = 'translateY(-3px) rotate(-8deg)'; setTimeout(() => { btn.style.transform = ''; }, 220); });
    });

    /* ---- STORE ---- */
    const data = pieces();
    const MEDIUMS = ['Oil', 'Watercolour', 'Ink', 'Poster', 'Drawing'];
    const SUBJECTS = ['Landscape', 'Portrait', 'Still life', 'Abstract'];
    const state = { media: [], subjects: [], maxPrice: 400, sort: 'new' };

    // read ?tag= from URL (deep-link a medium or subject)
    try {
      const qt = new URLSearchParams(location.search).get('tag');
      if (qt) {
        const m = MEDIUMS.find(t => t.toLowerCase() === qt.toLowerCase());
        const s = SUBJECTS.find(t => t.toLowerCase() === qt.toLowerCase());
        if (m) state.media.push(m); else if (s) state.subjects.push(s);
      }
    } catch (e) {}

    const mediumList = document.getElementById('medium-list');
    const subjectList = document.getElementById('subject-list');
    const chipsHTML = (arr, sel) => arr.map(t => '<button class="fopt' + (sel.includes(t) ? ' active' : '') + '" type="button" data-val="' + esc(t) + '">' + esc(t) + '</button>').join('');
    mediumList.innerHTML = chipsHTML(MEDIUMS, state.media);
    subjectList.innerHTML = chipsHTML(SUBJECTS, state.subjects);

    const grid = document.getElementById('store-grid');
    const empty = document.getElementById('store-empty');
    const countEl = document.getElementById('store-count');
    const activeWrap = document.getElementById('active-tags');
    const priceSlider = document.getElementById('price-slider');
    const priceVal = document.getElementById('price-val');
    const niceRange = (lo, hi) => {
      const span = Math.max(hi - lo, 1);
      const steps = [1, 2, 5, 10, 20, 25, 50, 100, 200];
      let step = steps.find(s => span / s <= 20) || 200;
      while (step > 1 && span / step < 5) step = steps[steps.indexOf(step) - 1];
      return { min: Math.floor(lo / step) * step, max: Math.ceil(hi / step) * step, step };
    };
    const galPrices = data.map(p => +p.price);
    const pr = niceRange(Math.min(...galPrices), Math.max(...galPrices));
    const sliderMax = pr.max;
    priceSlider.min = pr.min; priceSlider.max = pr.max; priceSlider.step = pr.step; priceSlider.value = pr.max;
    state.maxPrice = pr.max;
    const galScale = priceSlider.closest('.slider-wrap').querySelector('.price-scale');
    if (galScale) galScale.innerHTML = '<span>' + (pr.min === 0 ? 'Free' : '£' + pr.min) + '</span><span>£' + pr.max + '+</span>';
    priceVal.textContent = 'Up to £' + pr.max + '+';

    const cartSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1.6"></circle><circle cx="19" cy="21" r="1.6"></circle><path d="M2 3h3l2.6 12.6a1.8 1.8 0 0 0 1.8 1.4h8.9a1.8 1.8 0 0 0 1.8-1.4L22 7H6"></path></svg>';

    const etsyUrl = (p) => 'https://www.etsy.com/search?q=' + encodeURIComponent("Crystal's Art School " + p.title);
    const RATIO = { 'cover1.jpg': 0.8, 'Liquidwatercolor.jpg': 1, 'BrushA.jpg': 0.75, 'cover3.jpg': 1.5, 'Postercolor.jpg': 0.8, 'Sketchbook.jpg': 1.33, 'cover4.jpg': 0.8, 'cover2.jpg': 1, 'Rubber.jpg': 1, 'BrushB.jpg': 0.72, 'ProfilePic.jpg': 0.82 };
    const rOf = (p) => RATIO[p.img] || 0.8;
    const cardHTML = (p, idx) =>
      '<article class="art-card" data-idx="' + idx + '">' +
        '<div class="art-frame-wrap"><figure class="art-frame"><div class="art-img" style="--r:' + rOf(p) + '"><img src="img/' + p.img + '" alt="' + esc(p.title) + '"></div></figure></div>' +
        '<div class="art-meta">' +
          '<h3 class="art-title">' + esc(p.title) + '</h3>' +
          '<div class="art-sub">' + (p.orig ? 'Original' : 'Limited print') + '</div>' +
          '<div class="art-tagrow">' + p.tags.map(t => '<span class="art-tagchip">' + esc(t) + '</span>').join('') + '</div>' +
          '<div class="art-row"><span class="art-price">£' + p.price + '</span>' +
          '<div class="art-actions">' +
            '<a class="art-btn etsy" href="' + etsyUrl(p) + '" target="_blank" rel="noopener">Buy on Etsy</a>' +
            '<button class="art-btn detail" type="button" data-detail="' + idx + '">View details</button>' +
          '</div></div>' +
        '</div>' +
      '</article>';

    const setPct = () => {
      const pct = ((+priceSlider.value - +priceSlider.min) / (+priceSlider.max - +priceSlider.min)) * 100;
      priceSlider.closest('.slider-ui').style.setProperty('--pct', pct + '%');
    };

    const render = () => {
      let list = data.filter(p =>
        p.price <= state.maxPrice &&
        (state.media.length === 0 || state.media.some(t => p.tags.includes(t))) &&
        (state.subjects.length === 0 || state.subjects.some(t => p.tags.includes(t)))
      );
      if (state.sort === 'new') list.sort((a, b) => b.date.localeCompare(a.date));
      else if (state.sort === 'old') list.sort((a, b) => a.date.localeCompare(b.date));
      else if (state.sort === 'price-asc') list.sort((a, b) => a.price - b.price);
      else if (state.sort === 'price-desc') list.sort((a, b) => b.price - a.price);

      grid.innerHTML = list.map(p => cardHTML(p, data.indexOf(p))).join('');
      empty.style.display = list.length ? 'none' : 'block';
      countEl.textContent = list.length + (list.length === 1 ? ' piece' : ' pieces');
      grid.querySelectorAll('[data-detail]').forEach(b => b.addEventListener('click', () => openModal(data[+b.dataset.detail])));

      const active = state.media.concat(state.subjects);
      activeWrap.innerHTML = active.map(t => '<span class="active-tag" data-tag="' + esc(t) + '">' + esc(t) + '</span>').join('');
      activeWrap.querySelectorAll('.active-tag').forEach(el => el.addEventListener('click', () => {
        const t = el.dataset.tag;
        state.media = state.media.filter(x => x !== t);
        state.subjects = state.subjects.filter(x => x !== t);
        [mediumList, subjectList].forEach(l => l.querySelectorAll('.fopt').forEach(c => { if (c.dataset.val === t) c.classList.remove('active'); }));
        render();
      }));

      if (!reduce) {
        grid.querySelectorAll('.art-card').forEach((c, i) => { c.style.animation = 'rise-in .55s var(--ease-out) ' + (i * 0.04) + 's'; });
      }
    };

    /* medium + subject chip toggles (multi-select) */
    const wireChips = (listEl, bucket) => listEl.querySelectorAll('.fopt').forEach(chip => chip.addEventListener('click', () => {
      const v = chip.dataset.val;
      const i = state[bucket].indexOf(v);
      if (i >= 0) { state[bucket].splice(i, 1); chip.classList.remove('active'); }
      else { state[bucket].push(v); chip.classList.add('active'); }
      render();
    }));
    wireChips(mediumList, 'media');
    wireChips(subjectList, 'subjects');

    /* price slider — live label + fill while dragging; filter only on release */
    const onSlide = () => {
      const v = +priceSlider.value;
      priceVal.textContent = (v >= sliderMax) ? ('Up to £' + sliderMax + '+') : ('Up to £' + v);
      setPct();
      state.maxPrice = v;
      render();
    };
    priceSlider.addEventListener('input', onSlide);
    priceSlider.addEventListener('change', onSlide);
    setPct();

    /* sort chips (single-select) */
    const sortList = document.getElementById('sort-list');
    sortList.querySelectorAll('.fopt').forEach(chip => chip.addEventListener('click', () => {
      sortList.querySelectorAll('.fopt').forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      state.sort = chip.dataset.val;
      render();
    }));

    /* clear all */
    document.getElementById('filter-clear').addEventListener('click', () => {
      state.media = []; state.subjects = []; state.maxPrice = sliderMax; state.sort = 'new';
      [mediumList, subjectList].forEach(l => l.querySelectorAll('.fopt').forEach(c => c.classList.remove('active')));
      sortList.querySelectorAll('.fopt').forEach((c, i) => c.classList.toggle('active', i === 0));
      priceSlider.value = sliderMax; priceVal.textContent = 'Up to £' + sliderMax + '+'; setPct();
      render();
    });

    /* ---- quick-view modal ---- */
    const modal = document.getElementById('art-modal');
    const mImg = document.getElementById('am-img');
    const openModal = (p) => {
      mImg.src = 'img/' + p.img; mImg.alt = p.title;
      document.querySelector('.am-frame .am-img').style.aspectRatio = (RATIO[p.img] || 0.8);
      document.getElementById('am-sub').textContent = p.orig ? 'Original artwork' : 'Limited-edition print';
      document.getElementById('am-title').textContent = p.title;
      document.getElementById('am-tags').innerHTML = p.tags.map(t => '<span class="art-tagchip">' + esc(t) + '</span>').join('');
      document.getElementById('am-desc').textContent = p.desc;
      document.getElementById('am-spec').innerHTML =
        '<dt>Type</dt><dd>' + (p.orig ? 'Original, one of a kind' : 'Signed limited print') + '</dd>' +
        '<dt>Size</dt><dd>' + esc(p.size) + '</dd>' +
        '<dt>Ships</dt><dd>Framed, 5–7 days</dd>';
      document.getElementById('am-price').textContent = '£' + p.price;
      document.getElementById('am-buy').href = etsyUrl(p);
      const amCart = document.getElementById('am-cart');
      amCart.dataset.cartTitle = p.title; amCart.dataset.cartPrice = p.price; amCart.dataset.cartImg = p.img; amCart.dataset.cartKind = p.orig ? 'Original' : 'Limited print';
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
    };
    const closeModal = () => { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); };
    modal.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', closeModal));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    render();

    /* reveals for header */
    document.querySelectorAll('.reveal-one').forEach(el => el.classList.add('in-view'));
  }
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
