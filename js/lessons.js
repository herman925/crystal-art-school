/* Auto-generated from Claude Design handoff bundle.
   The original Component class wrapped its init in componentDidMount and
   only ran setInterval timers in componentWillUnmount. We replicate that
   pair without a runtime, and hoist the timer handles onto `window` so the
   unload hook can find them. */
(function () {
  function start() {
    var comp = {

  componentDidMount() {
    const root = document.querySelector('.cas-root');
    if (!root) return;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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

    /* cart */
    const CART = 'cas-cart';
    const readCart = () => { try { return JSON.parse(localStorage.getItem(CART) || '[]'); } catch (e) { return []; } };
    const writeCart = (c) => { try { localStorage.setItem(CART, JSON.stringify(c)); } catch (e) {} };
    const renderCart = () => { const n = readCart().reduce((a, i) => a + i.qty, 0); document.querySelectorAll('.cart-badge').forEach(b => { b.textContent = n; b.style.display = n > 0 ? 'grid' : 'none'; }); };
    renderCart();
    document.addEventListener('click', (e) => {
      const add = e.target.closest('[data-add-to-cart]');
      if (!add) return;
      e.preventDefault();
      const cell = add.closest('.lesson-cell');
      const title = cell ? (cell.querySelector('.scrim .t') || {}).textContent || 'Course' : 'Course';
      const priceTxt = cell ? ((cell.querySelector('.price') || {}).textContent || '') : '';
      const price = parseInt((priceTxt.match(/\d+/) || [0])[0], 10);
      const img = cell ? ((cell.querySelector('.cover-card img') || {}).getAttribute('src') || '').replace('img/', '') : '';
      const c = readCart();
      const ex = c.find(x => x.title === title);
      if (ex) ex.qty++; else c.push({ title, price, img, kind: 'Course', qty: 1 });
      writeCart(c); renderCart();
      const o = add.innerHTML; add.innerHTML = 'Added ✓'; add.disabled = true; setTimeout(() => { add.innerHTML = o; add.disabled = false; }, 1100);
      document.querySelectorAll('.cart-btn').forEach(btn => { btn.style.transition = 'transform .18s'; btn.style.transform = 'translateY(-3px) rotate(-8deg)'; setTimeout(() => { btn.style.transform = ''; }, 220); });
    });

    /* multi-group filter panel */
    const cells = Array.from(document.querySelectorAll('.lesson-cell'));
    const groupEls = Array.from(document.querySelectorAll('.fgroup'));
    const sel = {};
    groupEls.forEach(g => { sel[g.dataset.group] = 'all'; });
    let maxPrice = 999;
    const countEl = document.getElementById('lesson-count');
    const noRes = document.getElementById('no-results');

    const matches = (cell) => {
      if (sel.medium !== 'all' && cell.dataset.cat !== sel.medium) return false;
      if (sel.difficulty !== 'all' && cell.dataset.difficulty !== sel.difficulty) return false;
      if (sel.duration !== 'all' && cell.dataset.duration !== sel.duration) return false;
      if (sel.offer === 'sale' && cell.dataset.sale !== '1') return false;
      if (cell.dataset.price && (+cell.dataset.price) > maxPrice) return false;
      return true;
    };
    const applyFilters = () => {
      let n = 0;
      cells.forEach(c => { const ok = matches(c); c.classList.toggle('hidden', !ok); if (ok) n++; });
      if (countEl) countEl.textContent = n + (n === 1 ? ' lesson' : ' lessons');
      if (noRes) noRes.style.display = n ? 'none' : 'block';
    };
    groupEls.forEach(g => {
      const opts = g.querySelectorAll('.fopt');
      opts.forEach(o => o.addEventListener('click', () => {
        opts.forEach(x => x.classList.remove('active'));
        o.classList.add('active');
        sel[g.dataset.group] = o.dataset.val;
        applyFilters();
      }));
    });
    const clearBtn = document.getElementById('fp-clear');
    if (clearBtn) clearBtn.addEventListener('click', () => {
      groupEls.forEach(g => { sel[g.dataset.group] = 'all'; g.querySelectorAll('.fopt').forEach((o, i) => o.classList.toggle('active', i === 0)); });
      maxPrice = 999; priceSlider.value = priceSlider.max; priceVal.textContent = 'Any price'; setPct();
      applyFilters();
    });
    /* deep-link: ?cat=oils etc. */
    try { const qp = new URLSearchParams(location.search).get('cat'); if (qp) { const btn = document.querySelector('.fgroup[data-group="medium"] .fopt[data-val="' + qp.toLowerCase() + '"]'); if (btn) btn.click(); } } catch (e) {}
    applyFilters();

    /* price slider (dynamic range) */
    const priceSlider = document.getElementById('lesson-price-slider');
    const priceVal = document.getElementById('lesson-price-val');
    const niceRange = (lo, hi) => {
      const span = Math.max(hi - lo, 1);
      const steps = [1, 2, 5, 10, 20, 25, 50, 100, 200];
      let step = steps.find(s => span / s <= 20) || 200;
      while (step > 1 && span / step < 5) step = steps[steps.indexOf(step) - 1];
      return { min: Math.floor(lo / step) * step, max: Math.ceil(hi / step) * step, step };
    };
    const lessonPrices = cells.map(c => +c.dataset.price).filter(n => !isNaN(n));
    const pr = niceRange(Math.min(...lessonPrices), Math.max(...lessonPrices));
    priceSlider.min = pr.min; priceSlider.max = pr.max; priceSlider.step = pr.step; priceSlider.value = pr.max;
    const lScale = priceSlider.closest('.slider-wrap').querySelector('.price-scale');
    if (lScale) lScale.innerHTML = '<span>' + (pr.min === 0 ? 'Free' : '£' + pr.min) + '</span><span>£' + pr.max + '+</span>';
    const setPct = () => {
      const pct = ((+priceSlider.value - +priceSlider.min) / (+priceSlider.max - +priceSlider.min)) * 100;
      priceSlider.closest('.slider-ui').style.setProperty('--pct', pct + '%');
    };
    const onSlide = () => {
      const v = +priceSlider.value;
      priceVal.textContent = (v >= +priceSlider.max) ? 'Any price' : (v === 0 ? 'Free only' : ('Up to £' + v));
      setPct();
      maxPrice = (v >= +priceSlider.max) ? 999 : v;
      applyFilters();
    };
    priceSlider.addEventListener('input', onSlide);
    priceSlider.addEventListener('change', onSlide);
    setPct();

    /* sort (own card) */
    const lessonGrid = document.getElementById('lesson-grid');
    const lessonSort = document.getElementById('lesson-sort');
    const origOrder = cells.slice();
    if (lessonSort) lessonSort.querySelectorAll('.fopt').forEach(chip => chip.addEventListener('click', () => {
      lessonSort.querySelectorAll('.fopt').forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      const v = chip.dataset.val;
      let arr = origOrder.slice();
      if (v === 'price-asc') arr.sort((a, b) => (+a.dataset.price) - (+b.dataset.price));
      else if (v === 'price-desc') arr.sort((a, b) => (+b.dataset.price) - (+a.dataset.price));
      arr.forEach(c => lessonGrid.appendChild(c));
    }));

    /* scroll reveals (rect-based; IO is unreliable in the preview) */
    document.querySelectorAll('.reveal > *').forEach((el, i) => { el.style.setProperty('--i', i % 8); el.classList.add('reveal-up'); });
    const revealEls = Array.from(document.querySelectorAll('.reveal-up, .reveal-one, .reveal-brush, .section-title'));
    if (reduce) {
      revealEls.forEach(el => el.classList.add('in-view'));
    } else {
      const checkReveal = () => {
        const vh = window.innerHeight || document.documentElement.clientHeight;
        for (const el of revealEls) {
          if (el.classList.contains('in-view')) continue;
          const r = el.getBoundingClientRect();
          if (r.top < vh * 0.92 && r.bottom > 0) {
            el.classList.add('in-view');
            const t = el;
            setTimeout(() => { if (getComputedStyle(t).opacity !== '1') { t.style.transition = 'none'; t.style.opacity = '1'; t.style.transform = 'none'; } }, 1200);
          }
        }
      };
      checkReveal();
      window.addEventListener('scroll', checkReveal, { passive: true });
      window.addEventListener('resize', checkReveal);
      setTimeout(checkReveal, 120);
    }
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
