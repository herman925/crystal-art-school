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
    const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');

    /* theme — Light (A) / Dark (D) only. The toggle itself lives in js/site-nav.js.
       A stored or ?theme= value outside A|D is ignored and falls back to the default. */
    const THEME_KEY = 'cas-theme';
    const valid = ['A','D'];
    let __theme = 'D';
    try { const __s = localStorage.getItem(THEME_KEY); if (valid.indexOf(__s) >= 0) __theme = __s; } catch (e) {}
    try { const __q = (new URLSearchParams(location.search).get('theme') || '').toUpperCase(); if (valid.indexOf(__q) >= 0) { __theme = __q; localStorage.setItem(THEME_KEY, __q); } } catch (e) {}
    root.setAttribute('data-theme', __theme);

    const burger = document.querySelector('.nav-burger'), sheet = document.querySelector('.nav-sheet');
    if (burger && sheet) { burger.addEventListener('click', () => { const o = sheet.classList.toggle('open'); burger.setAttribute('aria-expanded', o ? 'true' : 'false'); }); sheet.addEventListener('click', (e) => { if (e.target.tagName === 'A') sheet.classList.remove('open'); }); }

    /* ---- cart state ---- */
    const CART = 'cas-cart';
    const SHIP = 18;
    const read = () => { try { return JSON.parse(localStorage.getItem(CART) || '[]'); } catch (e) { return []; } };
    const write = (c) => { try { localStorage.setItem(CART, JSON.stringify(c)); } catch (e) {} };
    const totalQty = (c) => c.reduce((n, i) => n + i.qty, 0);
    const subtotal = (c) => c.reduce((n, i) => n + i.price * i.qty, 0);
    const money = (n) => '£' + n.toLocaleString();
    const badge = () => { const n = totalQty(read()); document.querySelectorAll('.cart-badge').forEach(b => { b.textContent = n; b.style.display = n > 0 ? 'grid' : 'none'; }); };

    const lines = document.getElementById('cart-lines');
    const empty = document.getElementById('cart-empty');
    const toDetails = document.getElementById('to-details');

    const renderBasket = () => {
      const c = read();
      badge();
      if (!c.length) {
        lines.innerHTML = ''; empty.style.display = 'block';
        document.getElementById('summary-1').style.opacity = '.5';
        toDetails.disabled = true; toDetails.style.opacity = '.5'; toDetails.style.pointerEvents = 'none';
      } else {
        empty.style.display = 'none';
        document.getElementById('summary-1').style.opacity = '1';
        toDetails.disabled = false; toDetails.style.opacity = '1'; toDetails.style.pointerEvents = 'auto';
        lines.innerHTML = c.map((it, i) =>
          '<div class="line-item">' +
            '<div class="li-thumb">' + (it.img ? '<img src="img/' + esc(String(it.img).replace(/^img\//,'')) + '" alt="">' : '') + '</div>' +
            '<div><div class="li-title">' + esc(it.title) + '</div><div class="li-sub">' + esc(it.kind || 'Artwork') + '</div>' +
              '<div class="qty" style="margin-top:8px;"><button type="button" data-dec="' + i + '">−</button><span class="qv">' + it.qty + '</span><button type="button" data-inc="' + i + '">+</button></div></div>' +
            '<div class="li-right"><div class="li-price">' + money(it.price * it.qty) + '</div><button class="li-remove" data-rm="' + i + '">Remove</button></div>' +
          '</div>'
        ).join('');
        lines.querySelectorAll('[data-inc]').forEach(b => b.addEventListener('click', () => { const c2 = read(); c2[+b.dataset.inc].qty++; write(c2); renderBasket(); }));
        lines.querySelectorAll('[data-dec]').forEach(b => b.addEventListener('click', () => { const c2 = read(); const it = c2[+b.dataset.dec]; it.qty--; if (it.qty < 1) c2.splice(+b.dataset.dec, 1); write(c2); renderBasket(); }));
        lines.querySelectorAll('[data-rm]').forEach(b => b.addEventListener('click', () => { const c2 = read(); c2.splice(+b.dataset.rm, 1); write(c2); renderBasket(); }));
      }
      const sub = subtotal(c);
      document.getElementById('sub-1').textContent = money(sub);
      document.getElementById('tot-1').textContent = money(c.length ? sub + SHIP : 0);
      document.getElementById('ship-1').textContent = money(SHIP);
    };

    /* ---- step flow ---- */
    const stages = { 1: document.querySelector('[data-stage="1"]'), 2: document.querySelector('[data-stage="2"]'), 3: document.querySelector('[data-stage="3"]') };
    const stepEls = document.querySelectorAll('.step');
    const goStep = (n) => {
      Object.keys(stages).forEach(k => stages[k].style.display = (+k === n) ? 'block' : 'none');
      stepEls.forEach(s => { const sn = +s.dataset.step; s.classList.toggle('active', sn === n); s.classList.toggle('done', sn < n); });
      window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const fillStep2 = () => {
      const c = read();
      const mini = document.getElementById('mini-lines');
      mini.innerHTML = c.map(it => '<div class="sum-row"><span>' + esc(it.title) + ' ×' + it.qty + '</span><span>' + money(it.price * it.qty) + '</span></div>').join('');
      const sub = subtotal(c);
      document.getElementById('sub-2').textContent = money(sub);
      document.getElementById('ship-2').textContent = money(SHIP);
      document.getElementById('tot-2').textContent = money(sub + SHIP);
    };

    toDetails.addEventListener('click', () => { if (!read().length) return; fillStep2(); goStep(2); });
    document.getElementById('back-basket').addEventListener('click', () => goStep(1));

    document.getElementById('details-form').addEventListener('submit', (e) => {
      e.preventDefault();
      const c = read();
      const sub = subtotal(c);
      document.getElementById('order-no').textContent = '#CAS-' + String(Math.floor(1000 + Math.random() * 9000));
      document.getElementById('order-count').textContent = totalQty(c);
      document.getElementById('order-total').textContent = money(sub + SHIP);
      write([]); badge();
      goStep(3);
    });

    renderBasket();
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
