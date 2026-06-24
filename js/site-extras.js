/* Crystal's Art School — shared site extras
   1) Mini-cart slide-down drawer (intercepts every .cart-btn)
   2) Chalk cursor for theme D only
   Self-injecting, vanilla, themable. Load once per page via <script src="js/site-extras.js">. */
(function () {
  if (window.__casExtras) return; window.__casExtras = true;

  var CART = 'cas-cart', THEME = 'cas-theme';
  function read() { try { return JSON.parse(localStorage.getItem(CART) || '[]'); } catch (e) { return []; } }
  function write(c) { try { localStorage.setItem(CART, JSON.stringify(c)); } catch (e) {} }
  function count() { return read().reduce(function (a, i) { return a + (i.qty || 1); }, 0); }
  function money(n) { return '£' + (Math.round(n * 100) / 100).toLocaleString(); }
  function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
  function imgsrc(p) { return 'img/' + String(p || '').replace(/^img\//, ''); }
  function curTheme() { var r = document.querySelector('.cas-root'); var t = r && r.getAttribute('data-theme'); if (t) return t; try { return localStorage.getItem(THEME) || 'A'; } catch (e) { return 'A'; } }

  /* ---------- styles ---------- */
  var css = ''
    + '.mc-backdrop{position:fixed;inset:0;z-index:1400;background:rgba(20,17,13,.46);opacity:0;pointer-events:none;transition:opacity .35s ease;backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);}'
    + '.mc-backdrop.open{opacity:1;pointer-events:auto;}'
    + '.mc-drawer{position:fixed;top:0;left:0;right:0;z-index:1401;transform:translateY(-104%);transition:transform .42s cubic-bezier(.22,.61,.36,1);background-color:var(--surface);background-image:var(--concrete-grain);background-size:200px 200px;color:var(--ink);box-shadow:0 18px 50px rgba(20,17,13,.4);border-bottom:2px solid color-mix(in srgb,var(--banner-bracket,#2b2823) 45%,transparent);}'
    + '.mc-drawer.open{transform:translateY(0);}'
    + '.mc-inner{max-width:960px;margin:0 auto;padding:22px 26px 26px;}'
    + '.mc-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:14px;}'
    + '.mc-title{font-family:var(--font-display-serif,serif);font-weight:600;font-size:22px;letter-spacing:.04em;text-transform:uppercase;color:var(--ink);display:flex;align-items:center;gap:.5em;}'
    + '.mc-title .ast{color:#7e9b46;font-size:.7em;}'
    + '.mc-close{width:38px;height:38px;border-radius:50%;border:0;background:color-mix(in srgb,var(--ink) 10%,transparent);color:var(--ink);font-size:18px;cursor:pointer;display:grid;place-items:center;transition:background .15s,transform .15s;}'
    + '.mc-close:hover{background:var(--ink);color:var(--paper);transform:rotate(90deg);}'
    + '.mc-list{display:flex;flex-direction:column;gap:12px;max-height:min(48vh,420px);overflow:auto;margin:0 -6px;padding:2px 6px;}'
    + '.mc-line{display:grid;grid-template-columns:62px 1fr auto;gap:14px;align-items:center;padding:10px;border-radius:12px;background:color-mix(in srgb,var(--ink) 5%,transparent);}'
    + '.mc-thumb{width:62px;height:62px;border-radius:8px;overflow:hidden;background:var(--paper-2);}'
    + '.mc-thumb img{width:100%;height:100%;object-fit:cover;display:block;}'
    + '.mc-name{font-family:var(--font-marker,serif);font-weight:600;font-size:15px;line-height:1.2;color:var(--ink);}'
    + '.mc-kind{font-family:var(--font-cond,sans-serif);font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:var(--muted);margin-top:3px;}'
    + '.mc-qty{display:inline-flex;align-items:center;gap:0;margin-top:8px;border:1px solid var(--line);border-radius:999px;overflow:hidden;}'
    + '.mc-qty button{width:26px;height:26px;border:0;background:transparent;color:var(--ink);font-size:15px;cursor:pointer;line-height:1;}'
    + '.mc-qty button:hover{background:color-mix(in srgb,var(--ink) 12%,transparent);}'
    + '.mc-qty span{min-width:26px;text-align:center;font-family:var(--font-cond,sans-serif);font-weight:600;font-size:13px;}'
    + '.mc-right{display:flex;flex-direction:column;align-items:flex-end;gap:8px;}'
    + '.mc-price{font-family:var(--font-cond,sans-serif);font-weight:600;font-size:15px;color:var(--coral);}'
    + '.mc-rm{border:0;background:none;color:var(--muted);font-family:var(--font-cond,sans-serif);font-size:11px;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;}'
    + '.mc-rm:hover{color:var(--coral);text-decoration:underline;}'
    + '.mc-foot{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-top:18px;padding-top:16px;border-top:1px solid var(--line);flex-wrap:wrap;}'
    + '.mc-sub{font-family:var(--font-cond,sans-serif);font-size:13px;letter-spacing:.04em;color:var(--muted);}'
    + '.mc-sub b{display:block;font-family:var(--font-display-serif,serif);font-size:26px;color:var(--ink);letter-spacing:0;}'
    + '.mc-acts{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}'
    + '.mc-btn{font-family:var(--font-cond,sans-serif);font-weight:500;font-size:13px;letter-spacing:.12em;text-transform:uppercase;padding:13px 24px;border-radius:2px;border:0;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:transform .15s,background .15s,filter .15s;}'
    + '.mc-btn.primary{background:var(--coral);color:var(--paper);-webkit-text-fill-color:var(--paper);}'
    + '.mc-btn.primary:hover{transform:translateY(-2px);filter:brightness(1.06);}'
    + '.mc-btn.ghost{background:transparent;color:var(--ink);-webkit-text-fill-color:var(--ink);box-shadow:inset 0 0 0 1px var(--line);}'
    + '.mc-btn.ghost:hover{background:var(--ink);color:var(--paper);-webkit-text-fill-color:var(--paper);}'
    + '.mc-empty{text-align:center;padding:30px 10px 24px;}'
    + '.mc-empty .ast{font-size:30px;color:#7e9b46;display:block;margin-bottom:10px;}'
    + '.mc-empty p{color:var(--muted);margin:0 0 18px;font-size:15px;}'
    + '@media (max-width:640px){.mc-foot{flex-direction:column;align-items:stretch;}.mc-acts{justify-content:stretch;}.mc-btn{flex:1;justify-content:center;}}'
    /* chalk cursor (theme D only) */
    + '[data-theme="D"] .cas-root, [data-theme="D"].cas-root{cursor:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'26\' height=\'26\'%3E%3Ccircle cx=\'13\' cy=\'13\' r=\'5\' fill=\'%23f3ede1\' fill-opacity=\'.92\'/%3E%3Ccircle cx=\'13\' cy=\'13\' r=\'9\' fill=\'none\' stroke=\'%23f3ede1\' stroke-opacity=\'.35\'/%3E%3C/svg%3E") 13 13, auto;}'
    + '[data-theme="D"] .cas-root a,[data-theme="D"] .cas-root button,[data-theme="D"] .cas-root [role="button"],[data-theme="D"] .cas-root .cover-card,[data-theme="D"] .cas-root summary{cursor:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'30\' height=\'30\'%3E%3Ccircle cx=\'15\' cy=\'15\' r=\'7\' fill=\'%23d98a5c\' fill-opacity=\'.95\'/%3E%3Ccircle cx=\'15\' cy=\'15\' r=\'12\' fill=\'none\' stroke=\'%23d98a5c\' stroke-opacity=\'.5\'/%3E%3C/svg%3E") 15 15, pointer;}'
    + '.mc-chalk-dust{position:fixed;width:7px;height:7px;border-radius:50%;background:#f3ede1;pointer-events:none;z-index:2000;opacity:.0;}'
    /* ---------- GDPR cookie consent ---------- */
    + '.ck-bar{position:fixed;left:0;right:0;bottom:0;z-index:1500;transform:translateY(115%);transition:transform .5s cubic-bezier(.22,.61,.36,1);background-color:var(--surface);background-image:var(--concrete-grain);background-size:200px 200px;color:var(--ink);box-shadow:0 -14px 44px rgba(20,17,13,.34);border-top:2px solid color-mix(in srgb,var(--banner-bracket,#2b2823) 45%,transparent);}'
    + '.ck-bar.show{transform:translateY(0);}'
    + '.ck-in{max-width:1180px;margin:0 auto;padding:20px 26px;display:grid;grid-template-columns:1fr auto;gap:20px 30px;align-items:center;}'
    + '.ck-txt{font-family:var(--font-cond,sans-serif);font-size:13.5px;line-height:1.5;color:var(--ink);max-width:74ch;}'
    + '.ck-txt b{font-family:var(--font-display-serif,serif);font-weight:600;font-size:14px;letter-spacing:.12em;text-transform:uppercase;display:flex;align-items:center;gap:.5em;margin-bottom:5px;}'
    + '.ck-txt b .ast{color:#7e9b46;font-size:.8em;}'
    + '.ck-txt a{color:var(--coral);-webkit-text-fill-color:var(--coral);text-decoration:underline;text-underline-offset:2px;cursor:pointer;}'
    + '.ck-acts{display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:flex-end;}'
    + '.ck-btn{font-family:var(--font-cond,sans-serif);font-weight:500;font-size:12px;letter-spacing:.13em;text-transform:uppercase;padding:13px 22px;border-radius:2px;border:0;cursor:pointer;white-space:nowrap;transition:transform .15s,background .15s,filter .15s;}'
    + '.ck-btn.primary{background:var(--coral);color:var(--paper);-webkit-text-fill-color:var(--paper);}'
    + '.ck-btn.primary:hover{transform:translateY(-2px);filter:brightness(1.06);}'
    + '.ck-btn.ghost{background:transparent;color:var(--ink);-webkit-text-fill-color:var(--ink);box-shadow:inset 0 0 0 1px var(--line);}'
    + '.ck-btn.ghost:hover{background:var(--ink);color:var(--paper);-webkit-text-fill-color:var(--paper);}'
    + '@media (max-width:760px){.ck-in{grid-template-columns:1fr;}.ck-acts{justify-content:stretch;}.ck-btn{flex:1;text-align:center;}}'
    /* data-rights / preferences modal */
    + '.ck-modal{position:fixed;inset:0;z-index:1600;display:grid;place-items:center;padding:24px;opacity:0;pointer-events:none;transition:opacity .3s ease;}'
    + '.ck-modal.show{opacity:1;pointer-events:auto;}'
    + '.ck-modal-bg{position:absolute;inset:0;background:rgba(20,17,13,.5);backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);}'
    + '.ck-card{position:relative;width:min(560px,100%);max-height:88vh;overflow:auto;background-color:var(--surface);background-image:var(--concrete-grain);background-size:200px 200px;color:var(--ink);border-radius:6px;box-shadow:0 26px 70px rgba(20,17,13,.5);border:1px solid var(--line);transform:translateY(14px) scale(.98);transition:transform .3s ease;}'
    + '.ck-modal.show .ck-card{transform:none;}'
    + '.ck-card-in{padding:30px 32px 32px;}'
    + '.ck-h{font-family:var(--font-display-serif,serif);font-weight:600;font-size:24px;letter-spacing:.04em;text-transform:uppercase;color:var(--ink);display:flex;align-items:center;gap:.5em;margin:0 0 6px;}'
    + '.ck-h .ast{color:#7e9b46;font-size:.66em;}'
    + '.ck-sub{font-family:var(--font-cond,sans-serif);font-size:13px;color:var(--muted);margin:0 0 22px;line-height:1.5;}'
    + '.ck-sect{font-family:var(--font-cond,sans-serif);font-weight:600;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin:22px 0 12px;padding-bottom:7px;border-bottom:1px solid var(--line);}'
    + '.ck-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:12px 0;}'
    + '.ck-row .l{font-family:var(--font-marker,serif);font-weight:600;font-size:15px;color:var(--ink);}'
    + '.ck-row .d{font-family:var(--font-cond,sans-serif);font-size:12.5px;color:var(--muted);margin-top:3px;line-height:1.45;}'
    + '.ck-tog{flex:none;width:46px;height:26px;border-radius:999px;border:0;background:color-mix(in srgb,var(--ink) 20%,transparent);position:relative;cursor:pointer;transition:background .2s;margin-top:3px;}'
    + '.ck-tog::after{content:"";position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:var(--paper);transition:transform .2s;}'
    + '.ck-tog.on{background:var(--coral);}'
    + '.ck-tog.on::after{transform:translateX(20px);}'
    + '.ck-tog.locked{opacity:.55;cursor:not-allowed;}'
    + '.ck-dr{display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;}'
    + '.ck-dr .ck-btn{flex:1;min-width:150px;}'
    + '.ck-note{font-family:var(--font-cond,sans-serif);font-size:12px;color:#7e9b46;margin-top:14px;min-height:1.2em;}'
    + '.ck-foot{display:flex;gap:10px;justify-content:flex-end;margin-top:26px;padding-top:18px;border-top:1px solid var(--line);flex-wrap:wrap;}'
    + '.ck-x{position:absolute;top:16px;right:16px;width:34px;height:34px;border-radius:50%;border:0;background:color-mix(in srgb,var(--ink) 10%,transparent);color:var(--ink);font-size:16px;cursor:pointer;display:grid;place-items:center;transition:background .15s,transform .15s;}'
    + '.ck-x:hover{background:var(--ink);color:var(--paper);transform:rotate(90deg);}'
    /* ---------- per-course tier picker ---------- */
    + '.tp-pop{position:absolute;z-index:1450;width:300px;background-color:var(--surface);background-image:var(--concrete-grain);background-size:200px 200px;color:var(--ink);border:1px solid var(--line);border-radius:8px;box-shadow:0 22px 60px rgba(20,17,13,.42);opacity:0;transform:translateY(-8px) scale(.98);transform-origin:top center;transition:opacity .2s ease,transform .2s ease;pointer-events:none;}'
    + '.tp-pop.show{opacity:1;transform:none;pointer-events:auto;}'
    + '.tp-in{padding:14px 14px 15px;}'
    + '.tp-h{font-family:var(--font-cond,sans-serif);font-weight:600;font-size:10.5px;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:10px;display:flex;align-items:center;gap:.5em;}'
    + '.tp-h .ast{color:#7e9b46;}'
    + '.tp-opt{display:flex;align-items:center;gap:12px;width:100%;text-align:left;border:1px solid var(--line);background:color-mix(in srgb,var(--ink) 4%,transparent);border-radius:7px;padding:11px 12px;cursor:pointer;margin-bottom:8px;transition:border-color .15s,background .15s,transform .12s;}'
    + '.tp-opt:hover{border-color:var(--coral);background:color-mix(in srgb,var(--coral) 9%,transparent);transform:translateY(-1px);}'
    + '.tp-opt:last-child{margin-bottom:0;}'
    + '.tp-opt .nm{font-family:var(--font-marker,serif);font-weight:600;font-size:15px;color:var(--ink);line-height:1.1;}'
    + '.tp-opt .ds{font-family:var(--font-cond,sans-serif);font-size:11px;color:var(--muted);margin-top:3px;line-height:1.3;}'
    + '.tp-opt .pr{margin-left:auto;font-family:var(--font-display-serif,serif);font-weight:600;font-size:18px;color:var(--coral);white-space:nowrap;}'
    + '.tp-arrow{position:absolute;top:-7px;left:var(--ax,40px);width:13px;height:13px;background:var(--surface);border-left:1px solid var(--line);border-top:1px solid var(--line);transform:rotate(45deg);}';

  function injectStyles() { var s = document.createElement('style'); s.id = 'mc-styles'; s.textContent = css; document.head.appendChild(s); }

  /* ---------- drawer DOM ---------- */
  var backdrop, drawer, listEl, footEl;
  function build() {
    backdrop = document.createElement('div'); backdrop.className = 'mc-backdrop';
    drawer = document.createElement('div'); drawer.className = 'mc-drawer'; drawer.setAttribute('role', 'dialog'); drawer.setAttribute('aria-label', 'Shopping cart');
    drawer.innerHTML = '<div class="mc-inner">'
      + '<div class="mc-head"><div class="mc-title"><span class="ast">✦</span> Your basket</div>'
      + '<button class="mc-close" type="button" aria-label="Close cart">✕</button></div>'
      + '<div class="mc-list"></div><div class="mc-footwrap"></div></div>';
    document.body.appendChild(backdrop); document.body.appendChild(drawer);
    listEl = drawer.querySelector('.mc-list'); footEl = drawer.querySelector('.mc-footwrap');
    backdrop.addEventListener('click', close);
    drawer.querySelector('.mc-close').addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    listEl.addEventListener('click', onListClick);
  }

  function syncTheme() { var t = curTheme(); backdrop.setAttribute('data-theme', t); drawer.setAttribute('data-theme', t); }

  function render() {
    var c = read();
    if (!c.length) {
      listEl.innerHTML = '<div class="mc-empty"><span class="ast">✦</span><p>Your basket is empty.</p>'
        + '<a class="mc-btn primary" href="Gallery.html">Browse the gallery →</a></div>';
      footEl.innerHTML = ''; updateBadges(); return;
    }
    listEl.innerHTML = c.map(function (it, i) {
      var line = (it.price || 0) * (it.qty || 1);
      return '<div class="mc-line">'
        + '<div class="mc-thumb">' + (it.img ? '<img src="' + esc(imgsrc(it.img)) + '" alt="">' : '') + '</div>'
        + '<div><div class="mc-name">' + esc(it.title) + '</div><div class="mc-kind">' + esc(it.kind || 'Artwork') + '</div>'
        + '<div class="mc-qty"><button type="button" data-dec="' + i + '" aria-label="Decrease">−</button><span>' + (it.qty || 1) + '</span><button type="button" data-inc="' + i + '" aria-label="Increase">+</button></div></div>'
        + '<div class="mc-right"><div class="mc-price">' + money(line) + '</div><button class="mc-rm" type="button" data-rm="' + i + '">Remove</button></div>'
        + '</div>';
    }).join('');
    var sub = c.reduce(function (a, i) { return a + (i.price || 0) * (i.qty || 1); }, 0);
    footEl.innerHTML = '<div class="mc-foot"><div class="mc-sub">Subtotal<b>' + money(sub) + '</b></div>'
      + '<div class="mc-acts"><button class="mc-btn ghost" type="button" data-keep>Keep shopping</button>'
      + '<a class="mc-btn primary" href="Checkout.html">Go to checkout →</a></div></div>';
    footEl.querySelector('[data-keep]').addEventListener('click', close);
    updateBadges();
  }

  function onListClick(e) {
    var t = e.target.closest('[data-inc],[data-dec],[data-rm]'); if (!t) return;
    var c = read(); var i;
    if (t.hasAttribute('data-inc')) { i = +t.getAttribute('data-inc'); c[i].qty = (c[i].qty || 1) + 1; }
    else if (t.hasAttribute('data-dec')) { i = +t.getAttribute('data-dec'); c[i].qty = (c[i].qty || 1) - 1; if (c[i].qty < 1) c.splice(i, 1); }
    else if (t.hasAttribute('data-rm')) { i = +t.getAttribute('data-rm'); c.splice(i, 1); }
    write(c); render();
  }

  function updateBadges() { var n = count(); document.querySelectorAll('.cart-badge').forEach(function (b) { b.textContent = n; b.style.display = n > 0 ? 'grid' : 'none'; }); }

  var lastFocus = null;
  function open() { syncTheme(); render(); backdrop.classList.add('open'); drawer.classList.add('open'); document.body.style.overflow = 'hidden'; lastFocus = document.activeElement; var cb = drawer.querySelector('.mc-close'); if (cb) cb.focus(); }
  function close() { backdrop.classList.remove('open'); drawer.classList.remove('open'); document.body.style.overflow = ''; if (lastFocus && lastFocus.focus) try { lastFocus.focus(); } catch (e) {} }

  /* ---------- intercept cart buttons (delegation: works regardless of stream timing) ---------- */
  document.addEventListener('click', function (e) {
    // per-course tier picker (intercept before page handlers)
    var tBtn = e.target.closest('[data-add-to-cart][data-tiered]');
    if (tBtn) { e.preventDefault(); e.stopPropagation(); openTierPop(tBtn); return; }
    // outside-click closes the tier popover
    if (tpPop && tpPop.classList.contains('show') && !e.target.closest('.tp-pop')) closeTierPop();
    var soc = e.target.closest('.social-btn');
    if (soc && (soc.getAttribute('href') === '#' || !soc.getAttribute('href'))) {
      e.preventDefault();
      var lbl = (soc.getAttribute('aria-label') || '').toLowerCase();
      var url = lbl.indexOf('face') >= 0 ? 'https://facebook.com/' : lbl.indexOf('insta') >= 0 ? 'https://instagram.com/' : null;
      if (url) window.open(url, '_blank', 'noopener');
      return;
    }
    var btn = e.target.closest('.cart-btn'); if (!btn) return;
    e.preventDefault(); e.stopPropagation();
    if (!drawer) return;
    if (drawer.classList.contains('open')) close(); else open();
  }, true);

  /* ---------- chalk dust trail (theme D only) ---------- */
  function chalkTrail() {
    var pool = [], N = 6, idx = 0;
    for (var k = 0; k < N; k++) { var d = document.createElement('div'); d.className = 'mc-chalk-dust'; document.body.appendChild(d); pool.push(d); }
    var last = 0;
    window.addEventListener('pointermove', function (e) {
      if (curTheme() !== 'D') return;
      var now = Date.now(); if (now - last < 38) return; last = now;
      var d = pool[idx % N]; idx++;
      var jx = (Math.random() - .5) * 8, jy = (Math.random() - .5) * 8;
      d.style.left = (e.clientX + jx - 3) + 'px'; d.style.top = (e.clientY + jy - 3) + 'px';
      d.style.transition = 'none'; d.style.opacity = (0.35 + Math.random() * 0.4);
      d.style.transform = 'scale(' + (0.6 + Math.random() * 0.7) + ')';
      requestAnimationFrame(function () { d.style.transition = 'opacity .6s ease, transform .6s ease'; d.style.opacity = '0'; d.style.transform = 'scale(.2) translateY(6px)'; });
    }, { passive: true });
  }

  /* ---------- GDPR cookie consent + data-rights tool ---------- */
  var CKKEY = 'cas-cookie-consent';
  var ckBar, ckModal, ckPrefs;
  function ckRead() { try { return JSON.parse(localStorage.getItem(CKKEY) || 'null'); } catch (e) { return null; } }
  function ckWrite(o) { try { localStorage.setItem(CKKEY, JSON.stringify(o)); } catch (e) {} }
  function ckSyncTheme() { var t = curTheme(); if (ckBar) ckBar.setAttribute('data-theme', t); if (ckModal) ckModal.setAttribute('data-theme', t); }

  function buildCookie() {
    var saved = ckRead() || {};
    ckPrefs = { analytics: !!saved.analytics, marketing: !!saved.marketing };

    ckBar = document.createElement('div'); ckBar.className = 'ck-bar'; ckBar.setAttribute('role', 'dialog'); ckBar.setAttribute('aria-label', 'Cookie consent');
    ckBar.innerHTML = '<div class="ck-in"><div class="ck-txt"><b><span class="ast">✦</span> Cookies &amp; your privacy</b>'
      + 'We use essential cookies to run Crystal\'s Art School, and optional ones for analytics. You\'re in the UK, so you\'re fully in control — choose what you allow. '
      + 'See your <a data-ck-open>privacy choices &amp; data rights</a>.</div>'
      + '<div class="ck-acts">'
      + '<button class="ck-btn ghost" type="button" data-ck="essential">Essential only</button>'
      + '<button class="ck-btn ghost" type="button" data-ck="manage">Manage</button>'
      + '<button class="ck-btn primary" type="button" data-ck="all">Accept all</button>'
      + '</div></div>';
    document.body.appendChild(ckBar);

    ckModal = document.createElement('div'); ckModal.className = 'ck-modal'; ckModal.setAttribute('role', 'dialog'); ckModal.setAttribute('aria-modal', 'true'); ckModal.setAttribute('aria-label', 'Privacy choices and data rights');
    ckModal.innerHTML = '<div class="ck-modal-bg" data-ck-close></div>'
      + '<div class="ck-card"><button class="ck-x" type="button" data-ck-close aria-label="Close">✕</button><div class="ck-card-in">'
      + '<h2 class="ck-h"><span class="ast">✦</span> Privacy choices</h2>'
      + '<p class="ck-sub">Manage how we use cookies, and exercise your data rights under UK GDPR. Essential cookies keep the basket, login and theme working and can\'t be switched off.</p>'
      + '<div class="ck-sect">Cookie preferences</div>'
      + '<div class="ck-row"><div><div class="l">Essential</div><div class="d">Basket, account session, theme, security. Always on.</div></div><button class="ck-tog on locked" type="button" disabled aria-label="Essential cookies, always on"></button></div>'
      + '<div class="ck-row"><div><div class="l">Analytics</div><div class="d">Anonymous usage stats so we can improve the lessons and pages.</div></div><button class="ck-tog' + (ckPrefs.analytics ? ' on' : '') + '" type="button" data-ck-tog="analytics" aria-label="Toggle analytics cookies"></button></div>'
      + '<div class="ck-row"><div><div class="l">Marketing</div><div class="d">Helps us measure promotions. Off by default.</div></div><button class="ck-tog' + (ckPrefs.marketing ? ' on' : '') + '" type="button" data-ck-tog="marketing" aria-label="Toggle marketing cookies"></button></div>'
      + '<div class="ck-sect">Your data rights (UK GDPR)</div>'
      + '<p class="ck-sub" style="margin-bottom:14px;">You have the right to access, correct, or erase the personal data we hold. Requests are handled by the site administrator.</p>'
      + '<div class="ck-dr">'
      + '<button class="ck-btn ghost" type="button" data-ck-dr="export">Request my data</button>'
      + '<button class="ck-btn ghost" type="button" data-ck-dr="rectify">Correct my data</button>'
      + '<button class="ck-btn ghost" type="button" data-ck-dr="erase">Delete my data</button>'
      + '</div>'
      + '<div class="ck-note" id="ck-note" aria-live="polite"></div>'
      + '<div class="ck-foot"><button class="ck-btn ghost" type="button" data-ck-close>Cancel</button><button class="ck-btn primary" type="button" data-ck="save">Save choices</button></div>'
      + '</div></div>';
    document.body.appendChild(ckModal);

    ckBar.addEventListener('click', function (e) {
      var open = e.target.closest('[data-ck-open]'); if (open) { e.preventDefault(); openCkModal(); return; }
      var b = e.target.closest('[data-ck]'); if (!b) return;
      var a = b.getAttribute('data-ck');
      if (a === 'all') saveConsent({ analytics: true, marketing: true });
      else if (a === 'essential') saveConsent({ analytics: false, marketing: false });
      else if (a === 'manage') openCkModal();
    });
    ckModal.addEventListener('click', function (e) {
      if (e.target.closest('[data-ck-close]')) { closeCkModal(); return; }
      var tog = e.target.closest('[data-ck-tog]');
      if (tog) { var k = tog.getAttribute('data-ck-tog'); ckPrefs[k] = !ckPrefs[k]; tog.classList.toggle('on', ckPrefs[k]); return; }
      var dr = e.target.closest('[data-ck-dr]');
      if (dr) {
        var kind = dr.getAttribute('data-ck-dr');
        var msg = kind === 'export' ? 'Request received — we\'ll email a copy of your data within 30 days.'
          : kind === 'rectify' ? 'Request received — the administrator will be in touch to correct your details.'
          : 'Erasure request received — your data will be deleted within 30 days. A confirmation will follow.';
        var note = ckModal.querySelector('#ck-note'); note.textContent = '✓ ' + msg;
        return;
      }
      var save = e.target.closest('[data-ck="save"]');
      if (save) saveConsent({ analytics: ckPrefs.analytics, marketing: ckPrefs.marketing });
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && ckModal.classList.contains('show')) closeCkModal(); });

    ckSyncTheme();
    if (!ckRead()) setTimeout(function () { ckBar.classList.add('show'); }, 700);
  }
  function saveConsent(p) { ckWrite({ analytics: !!p.analytics, marketing: !!p.marketing, ts: Date.now() }); ckBar.classList.remove('show'); closeCkModal(); }
  function openCkModal() { ckSyncTheme(); ckModal.classList.add('show'); document.body.style.overflow = 'hidden'; }
  function closeCkModal() { ckModal.classList.remove('show'); if (!ckBar.classList.contains('show')) document.body.style.overflow = ''; }
  window.casOpenPrivacy = function () { if (ckBar) ckBar.classList.add('show'); openCkModal(); };

  /* ---------- global logged-in nav state ---------- */
  function applyAuthNav() {
    var u = null; try { u = JSON.parse(localStorage.getItem('cas-user') || 'null'); } catch (e) { u = null; }
    if (!u || !u.name) return;
    var first = String(u.name).split(' ')[0];
    document.querySelectorAll('.nav-auth').forEach(function (na) {
      if (na.querySelector('.nav-user')) return; // dashboard already has it
      var login = na.querySelector('a.btn.ghost[href="Login.html"]');
      var signup = na.querySelector('a.btn.primary[href="Signup.html"]');
      if (!login && !signup) return;
      var chip = document.createElement('a');
      chip.className = 'nav-user'; chip.href = 'Dashboard.html'; chip.style.textDecoration = 'none';
      chip.innerHTML = '<img src="img/ProfilePic.jpg" alt=""><span class="nm">' + first.replace(/[<>&]/g, '') + '</span>';
      var out = document.createElement('a');
      out.className = 'btn ghost'; out.href = 'index.html'; out.textContent = 'Log out';
      out.addEventListener('click', function (e) { e.preventDefault(); try { localStorage.removeItem('cas-user'); } catch (er) {} window.location.href = 'index.html'; });
      if (signup) { signup.parentNode.insertBefore(chip, signup); signup.remove(); }
      if (login) { login.parentNode.insertBefore(out, login); login.remove(); }
    });
    // mobile sheet
    document.querySelectorAll('.nav-sheet a[href="Login.html"]').forEach(function (a) {
      a.textContent = 'My dashboard'; a.href = 'Dashboard.html';
    });
  }

  /* ---------- per-course tier picker ---------- */
  var FELLOW_MULT = 1.8; // Fellow = 80% more than Apprentice (course + Discord)
  var tpPop, tpBtn = null;
  function tpResolve(btn) {
    var d = btn.dataset;
    if (d.title != null && d.price != null) return { title: d.title, price: +d.price || 0, img: (d.img || '').replace(/^img\//, '') };
    var cell = btn.closest('.lesson-cell');
    if (cell) return {
      title: ((cell.querySelector('.scrim .t') || {}).textContent || 'Course').trim(),
      price: +cell.dataset.price || 0,
      img: ((cell.querySelector('img') || {}).getAttribute('src') || '').replace(/^img\//, '')
    };
    var rel = btn.closest('.rel-slide');
    if (rel) return { title: ((rel.querySelector('.rel-title') || {}).textContent || 'Course').trim(), price: +d.price || 0, img: ((rel.querySelector('img') || {}).getAttribute('src') || '').replace(/^img\//, '') };
    return { title: 'Course', price: +d.price || 0, img: (d.img || '').replace(/^img\//, '') };
  }
  function buildTierPicker() {
    tpPop = document.createElement('div'); tpPop.className = 'tp-pop'; tpPop.setAttribute('role', 'menu');
    document.body.appendChild(tpPop);
  }
  function openTierPop(btn) {
    if (tpBtn === btn && tpPop.classList.contains('show')) { closeTierPop(); return; }
    tpBtn = btn;
    var item = tpResolve(btn);
    var appr = item.price, fell = Math.round(item.price * FELLOW_MULT);
    tpPop.setAttribute('data-theme', curTheme());
    tpPop.innerHTML = '<div class="tp-arrow"></div><div class="tp-in">'
      + '<div class="tp-h"><span class="ast">✦</span> Choose your tier</div>'
      + '<button class="tp-opt" type="button" data-tier="Apprentice"><div><div class="nm">Apprentice</div><div class="ds">Course videos &amp; downloadable materials</div></div><div class="pr">£' + appr + '</div></button>'
      + '<button class="tp-opt" type="button" data-tier="Fellow"><div><div class="nm">Fellow</div><div class="ds">Course + the teacher\'s private Discord</div></div><div class="pr">£' + fell + '</div></button>'
      + '</div>';
    tpPop.querySelectorAll('[data-tier]').forEach(function (o) {
      o.addEventListener('click', function () {
        var tier = o.getAttribute('data-tier');
        addCourse(item, tier, tier === 'Fellow' ? fell : appr, btn);
        closeTierPop();
      });
    });
    // position under the button
    var r = btn.getBoundingClientRect();
    var top = r.bottom + window.scrollY + 10;
    var left = r.left + window.scrollX + r.width / 2 - 150;
    left = Math.max(12 + window.scrollX, Math.min(left, window.scrollX + document.documentElement.clientWidth - 312));
    tpPop.style.top = top + 'px'; tpPop.style.left = left + 'px';
    var ax = (r.left + window.scrollX + r.width / 2) - left - 6;
    tpPop.style.setProperty('--ax', Math.max(14, Math.min(ax, 272)) + 'px');
    requestAnimationFrame(function () { tpPop.classList.add('show'); });
  }
  function closeTierPop() { if (tpPop) tpPop.classList.remove('show'); tpBtn = null; }
  function addCourse(item, tier, price, btn) {
    var c = read();
    var ex = c.find(function (x) { return x.title === item.title && x.tier === tier; });
    if (ex) ex.qty = (ex.qty || 1) + 1;
    else c.push({ title: item.title, price: price, tier: tier, kind: 'Course · ' + tier, img: item.img, qty: 1 });
    write(c); updateBadges();
    if (btn) {
      var o = btn.innerHTML; btn.innerHTML = 'Added ✓'; btn.disabled = true;
      setTimeout(function () { btn.innerHTML = o; btn.disabled = false; }, 1100);
    }
    document.querySelectorAll('.cart-btn').forEach(function (b) { b.style.transition = 'transform .18s'; b.style.transform = 'translateY(-3px) rotate(-8deg)'; setTimeout(function () { b.style.transform = ''; }, 220); });
  }

  function init() { injectStyles(); build(); buildCookie(); buildTierPicker(); applyAuthNav(); syncTheme(); updateBadges();
    window.addEventListener('scroll', function () { if (tpPop && tpPop.classList.contains('show')) closeTierPop(); }, { passive: true });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && tpPop && tpPop.classList.contains('show')) closeTierPop(); });
    // keep badge fresh when theme/cart changes elsewhere
    window.addEventListener('storage', function (e) { if (e.key === CART) { updateBadges(); if (drawer.classList.contains('open')) render(); } });
    chalkTrail();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
