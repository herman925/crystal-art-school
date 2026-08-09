/* Crystal's Art School — SINGLE SOURCE OF TRUTH for the top nav bar.
   Every page loads this script; it replaces the page's <nav class="site-nav">
   content with the canonical markup below, so the nav can never drift between
   pages again. Edit the nav HERE, nowhere else.
   Also owns: Courses drawer (dark panel), light/dark mode switch, burger sheet,
   cart badge. Pages must NOT bind their own listeners to these elements.     */
(function () {
  if (window.__casNav) return;
  window.__casNav = true;

  var CATS = [
    ['All courses', 'Lessons.dc.html', 1],
    ['Watercolour', 'Lessons.dc.html?cat=Watercolour'],
    ['Oils', 'Lessons.dc.html?cat=Oil'],
    ['Drawing', 'Lessons.dc.html?cat=Drawing'],
    ['Ink', 'Lessons.dc.html?cat=Ink'],
    ['Beginners', 'Lessons.dc.html?cat=Beginners']
  ];

  var LINKS = [
    ['Home', 'index.html', ['index.html', '']],
    ['Courses', 'Lessons.dc.html', ['lessons.dc.html', 'course.dc.html', 'lesson.dc.html'], 'drop'],
    ['Gallery', 'Gallery.dc.html', ['gallery.dc.html']],
    ['Showcase', 'Showcase.dc.html', ['showcase.dc.html']],
    ['Blog', 'Blog.dc.html', ['blog.dc.html', 'blog-post.dc.html']],
    ['About', 'About.dc.html', ['about.dc.html']],
    ['Free videos', 'FreeVideos.dc.html', ['freevideos.dc.html']],
    ['FAQ', 'FAQ.dc.html', ['faq.dc.html']]
  ];

  var CART_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"></circle><circle cx="18" cy="20" r="1.4"></circle><path d="M2 3h3.2l2.5 12.2a1.5 1.5 0 0 0 1.5 1.2h9a1.5 1.5 0 0 0 1.5-1.2L21.5 7H6.5"></path></svg>';
  var SUN_SVG = '<svg class="ico-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><circle cx="12" cy="12" r="4.2"></circle><path d="M12 2.5v2.2M12 19.3v2.2M2.5 12h2.2M19.3 12h2.2M4.9 4.9l1.6 1.6M17.5 17.5l1.6 1.6M19.1 4.9l-1.6 1.6M6.5 17.5l-1.6 1.6"></path></svg>';
  var MOON_SVG = '<svg class="ico-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z"></path></svg>';

  var page = (location.pathname.split('/').pop() || 'index.html').toLowerCase();

  function isActive(l) { return l[2].indexOf(page) >= 0; }

  var MEGA_ID = 'cas-nav-courses';

  /* A plain disclosure, not a menu. It used to carry role="menu" plus six
     role="menuitem" links with no aria-haspopup, no aria-expanded and no
     arrow-key handling — a menu contract the widget did not implement — and it
     opened on mouseover or touch only, so above 860px the six category links
     could not be reached from the keyboard at all. Roles dropped, and the caret
     is now a real button that owns the open state. */
  function buildDrawer() {
    var h = '<div class="nav-mega" id="' + MEGA_ID + '"><span class="mega-eyebrow">By medium <em>&middot; most popular first</em></span><div class="mega-cats">';
    for (var i = 0; i < CATS.length; i++) {
      h += '<a class="mega-cat' + (CATS[i][2] ? ' is-all' : '') + '" href="' + CATS[i][1] + '"><span>' + CATS[i][0] + '</span></a>';
    }
    return h + '</div></div>';
  }

  function buildNav() {
    var links = '';
    for (var i = 0; i < LINKS.length; i++) {
      var l = LINKS[i], act = isActive(l) ? ' class="active"' : '';
      if (l[3] === 'drop') {
        links += '<li class="nav-drop" data-nav-drop><a href="' + l[1] + '"' + (isActive(l) ? ' class="nav-trigger active"' : ' class="nav-trigger"') + '>' + l[0] + '</a>' +
          '<button class="nav-caret-btn" type="button" aria-expanded="false" aria-controls="' + MEGA_ID + '" aria-label="Show course categories">' +
          '<svg class="nav-caret" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 4.5 6 8.5 10 4.5"></path></svg>' +
          '</button>' + buildDrawer() + '</li>';
      } else {
        links += '<li><a href="' + l[1] + '"' + act + '>' + l[0] + '</a></li>';
      }
    }
    var sheet = '<a href="index.html">Home</a><a href="Lessons.dc.html">Courses — all</a>';
    for (var c = 1; c < CATS.length; c++) sheet += '<a href="' + CATS[c][1] + '">&middot; ' + CATS[c][0] + '</a>';
    for (var j = 2; j < LINKS.length; j++) sheet += '<a href="' + LINKS[j][1] + '">' + LINKS[j][0] + '</a>';
    sheet += '<a href="Login.dc.html">Log in / Sign up</a>';

    return '<div class="nav-top"><div class="inner">' +
      '<a class="nav-brand" href="index.html"><img src="img/lau-logo.png" alt="Lau. — Crystal\'s Art School"></a>' +
      '<ul class="nav-links">' + links + '</ul>' +
      '<div class="nav-auth">' +
        '<a class="cart-btn" href="Checkout.dc.html" aria-label="Shopping cart">' + CART_SVG + '<span class="cart-badge">0</span></a>' +
        '<a class="btn ghost" href="Login.dc.html">Log in</a>' +
        '<a class="btn primary" href="Signup.dc.html">Sign up</a>' +
        '<button class="mode-toggle" type="button" aria-label="Switch between light and dark mode" title="Light / dark mode">' + SUN_SVG + MOON_SVG + '</button>' +
        '<button class="nav-burger" type="button" aria-label="Menu" aria-expanded="false">☰</button>' +
      '</div></div></div>' +
      '<div class="nav-sheet">' + sheet + '</div>';
  }

  /* ---- styles owned by the shared nav ---- */
  var css =
    '.nav-drop { position: relative; display: inline-flex; align-items: center; gap: 6px; }' +
    '.nav-drop > .nav-trigger { display: inline-flex; align-items: center; cursor: pointer; }' +
    /* the caret is a real control now; the ::after gives it a 44px hit area
       without changing the bar's metrics */
    '.nav-caret-btn { position: relative; display: inline-grid; place-items: center; width: 14px; height: 14px;' +
    '  padding: 0; border: 0; background: none; color: inherit; cursor: pointer; flex: none; }' +
    '.nav-caret-btn::after { content: ""; position: absolute; left: 50%; top: 50%; width: 44px; height: 44px; transform: translate(-50%, -50%); }' +
    '.nav-caret { width: 8px; height: 8px; opacity: .6; transition: transform .25s ease; }' +
    '.nav-caret-btn:hover .nav-caret { opacity: 1; }' +
    '.nav-drop.open .nav-caret { transform: rotate(180deg); }' +
    '.nav-mega { position: absolute; top: calc(100% + 10px); left: -8px; width: max-content; min-width: 248px;' +
    '  background: #26221e; border: 1px solid rgba(237,225,208,.14); border-radius: 14px;' +
    '  box-shadow: 0 26px 64px rgba(0,0,0,.55); padding: 8px;' +
    '  opacity: 0; visibility: hidden; transform: translateY(-8px);' +
    '  transition: opacity .2s ease, transform .2s ease, visibility .2s; z-index: 300; }' +
    '.nav-drop.open .nav-mega { opacity: 1; visibility: visible; transform: translateY(0); }' +
    '.nav-mega::before { content:""; position:absolute; top:-12px; left:0; right:0; height:12px; }' +
    '.mega-eyebrow { display:block; white-space: nowrap; font-family: var(--font-cond); font-size: 12px; letter-spacing: var(--tracking-label); text-transform: uppercase; color: #cf9457; padding: 8px 12px 8px; }' +
    '.mega-eyebrow em { font-style: normal; opacity: .8; }' +
    '.mega-cats { display:flex; flex-direction:column; gap:7px; padding: 2px 8px 12px; }' +
    '.nav-links a.mega-cat, .mega-cat { display:flex; align-items:center; justify-content:space-between; gap:12px;' +
    '  font-family: var(--font-cond); font-weight:600; font-size:14px; letter-spacing:var(--tracking-label); text-transform:uppercase;' +
    '  color:#ede1d0; padding: 9px 16px; border-radius: 999px; border:1px solid rgba(237,225,208,.28);' +
    '  transition: background .16s ease, border-color .16s ease; }' +
    '.mega-cat:hover { background: rgba(237,225,208,.10); border-color: rgba(237,225,208,.5); }' +
    '.mega-cat.is-all { background: rgba(237,225,208,.08); border-color: rgba(237,225,208,.44); }' +
    '.mode-toggle { display:inline-grid; place-items:center; width:38px; height:38px; border-radius:999px;' +
    '  border:1.5px solid color-mix(in srgb, var(--banner-ink, #ede1d0) 42%, transparent); background:transparent;' +
    '  color: var(--banner-ink, #ede1d0); cursor:pointer; flex:none; transition: background .16s ease; }' +
    '.mode-toggle:hover { background: color-mix(in srgb, var(--banner-ink, #ede1d0) 12%, transparent); }' +
    '.mode-toggle svg { width:18px; height:18px; }' +
    '.mode-toggle .ico-sun { display:none; } .mode-toggle .ico-moon { display:block; }' +
    '[data-theme="D"] .mode-toggle .ico-sun { display:block; } [data-theme="D"] .mode-toggle .ico-moon { display:none; }' +
    '.site-nav .nav-links a { white-space: nowrap; }' +
    '@media (max-width: 1100px) { .site-nav .nav-links { gap: 4px; } }' +
    '@media (max-width: 860px) { .nav-mega { display:none; } }';
  var st = document.createElement('style');
  st.id = 'cas-nav-style';
  st.textContent = css;
  (document.head || document.documentElement).appendChild(st);

  function renderCart() {
    var n = 0;
    try { n = JSON.parse(localStorage.getItem('cas-cart') || '[]').reduce(function (a, i) { return a + (i.qty || 1); }, 0); } catch (e) {}
    var badges = document.querySelectorAll('.cart-badge');
    for (var i = 0; i < badges.length; i++) {
      badges[i].textContent = n;
      badges[i].style.display = n > 0 ? 'grid' : 'none';
      /* the button's aria-label made the badge presentational, so the count was
         reported ignored while its textContent read "3" — it belongs in the name */
      badges[i].setAttribute('aria-hidden', 'true');
    }
    var btns = document.querySelectorAll('.cart-btn');
    for (var j = 0; j < btns.length; j++) {
      btns[j].setAttribute('aria-label', n === 0 ? 'Shopping cart, empty'
        : 'Shopping cart, ' + n + (n === 1 ? ' item' : ' items'));
    }
  }

  /* ---- replace each page's nav with the canonical one ----
     This used to stop at the first success. It cannot: the page runtime re-renders
     and replaces the nav's children afterwards, which throws away the injected
     markup along with the light/dark toggle and leaves the stale pre-render nav in
     place. It is intermittent — the same page passed at 1.8s and failed at 4s — so
     a longer one-shot delay does not fix it either.
     Instead the guard runs fast while the page is settling, then slowly — and
     then STOPS. It used to keep a 400ms interval alive for the life of the page
     after 300 querySelector ticks in the first 15 seconds; the footer poller
     already stands down after a couple of seconds of quiet and this now matches
     it. The runtime's re-render happens within the first second or two; if a
     later one ever appears, the MutationObserver below catches it for free. */
  var QUIET_TICKS = 40;   // 2s of no new work, same as js/site-footer.js
  var MAX_TRIES = 300;    // 15s hard ceiling
  var tries = 0, quiet = 0, syncedAny = false, iv = null;

  function ensureNav() {
    var nav = document.querySelector('nav.site-nav');
    if (!nav || nav.getAttribute('data-cas-synced') === '1') return false;
    nav.innerHTML = buildNav();
    nav.setAttribute('data-cas-synced', '1');
    renderCart();
    /* auth state has to be re-applied after every render or the logged-in chip
       and the Log out button are wiped by the line above */
    if (window.casApplyAuthNav) { try { window.casApplyAuthNav(); } catch (e) {} }
    return true;
  }

  if (ensureNav()) syncedAny = true;
  iv = setInterval(function () {
    tries++;
    var did = ensureNav();
    if (did) syncedAny = true;
    quiet = (syncedAny && !did) ? quiet + 1 : 0;
    if (quiet >= QUIET_TICKS || tries > MAX_TRIES) { clearInterval(iv); iv = null; }
  }, 50);

  /* ---- open/close state, kept on the elements that advertise it ---- */
  function setDrop(li, open) {
    if (!li) return;
    li.classList.toggle('open', open);
    var btn = li.querySelector('.nav-caret-btn');
    if (btn) {
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.setAttribute('aria-label', (open ? 'Hide' : 'Show') + ' course categories');
    }
  }
  function closeAllDrops(except) {
    var ds = document.querySelectorAll('[data-nav-drop].open');
    for (var i = 0; i < ds.length; i++) if (ds[i] !== except) setDrop(ds[i], false);
  }
  function setSheet(sheet, burger, open) {
    if (!sheet) return;
    sheet.classList.toggle('open', open);
    if (!burger) burger = sheet.closest('nav') && sheet.closest('nav').querySelector('.nav-burger');
    if (burger) burger.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  /* ---- behaviour (delegated, so it survives the innerHTML swap) ---- */
  document.addEventListener('click', function (e) {
    var t = e.target;

    var burger = t.closest && t.closest('.nav-burger');
    if (burger) {
      var sheet = burger.closest('nav').querySelector('.nav-sheet');
      if (sheet) setSheet(sheet, burger, !sheet.classList.contains('open'));
      return;
    }
    var sheetLink = t.closest && t.closest('.nav-sheet a');
    if (sheetLink) { setSheet(sheetLink.closest('.nav-sheet'), null, false); return; }

    var mode = t.closest && t.closest('.mode-toggle');
    if (mode) {
      var root = document.querySelector('.cas-root');
      if (root) {
        var next = root.getAttribute('data-theme') === 'D' ? 'A' : 'D';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('cas-theme', next); } catch (er) {}
        if (window.CasAnnounce) window.CasAnnounce(next === 'D' ? 'Dark mode on.' : 'Light mode on.');
      }
      return;
    }

    /* the caret button is the disclosure; the link beside it still navigates */
    var caret = t.closest && t.closest('.nav-caret-btn');
    if (caret) {
      var cd = caret.closest('[data-nav-drop]');
      var willOpen = !cd.classList.contains('open');
      closeAllDrops(cd);
      setDrop(cd, willOpen);
      return;
    }

    /* outside click closes both the drawer and the burger sheet. The sheet used
       to stay open after any click on the page — only a second burger tap shut
       it — and aria-expanded stayed "true" with it. */
    if (!(t.closest && t.closest('[data-nav-drop]'))) closeAllDrops(null);
    var openSheet = document.querySelector('.nav-sheet.open');
    if (openSheet && !(t.closest && (t.closest('.nav-sheet') || t.closest('.nav-burger')))) {
      setSheet(openSheet, null, false);
    }
  });

  /* Escape closes whatever is open and puts focus back where it came from. */
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' && e.key !== 'Esc') return;
    var od = document.querySelector('[data-nav-drop].open');
    if (od) {
      setDrop(od, false);
      var b = od.querySelector('.nav-caret-btn');
      if (b) b.focus();
      return;
    }
    var os = document.querySelector('.nav-sheet.open');
    if (os) {
      var bur = os.closest('nav') && os.closest('nav').querySelector('.nav-burger');
      setSheet(os, bur, false);
      if (bur) bur.focus();
    }
  });

  /* Keyboard: ArrowDown from the caret opens and lands on the first category;
     Arrow keys walk the list; Tab out or click away closes it. */
  document.addEventListener('keydown', function (e) {
    var inDrop = e.target.closest && e.target.closest('[data-nav-drop]');
    if (!inDrop) return;
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      var items = Array.prototype.slice.call(inDrop.querySelectorAll('.mega-cat'));
      if (!items.length) return;
      if (!inDrop.classList.contains('open')) { setDrop(inDrop, true); }
      e.preventDefault();
      var i = items.indexOf(e.target);
      if (i < 0) { items[e.key === 'ArrowDown' ? 0 : items.length - 1].focus(); return; }
      items[(i + (e.key === 'ArrowDown' ? 1 : items.length - 1)) % items.length].focus();
    }
  });

  /* Focus leaving the whole <li> closes it — otherwise tabbing past the last
     category leaves an open panel floating over the page. */
  document.addEventListener('focusout', function (e) {
    var d = e.target.closest && e.target.closest('[data-nav-drop]');
    if (!d || !d.classList.contains('open')) return;
    setTimeout(function () {
      if (!d.contains(document.activeElement)) setDrop(d, false);
    }, 0);
  });

  /* Hover-intent for the Courses drawer: open on enter, close only after a
     grace delay so the cursor can cross the gap (or slip out briefly)
     without the panel vanishing mid-travel. */
  var hoverCloseTimer = null;
  document.addEventListener('mouseover', function (e) {
    var d = e.target.closest && e.target.closest('[data-nav-drop]');
    if (!d || !window.matchMedia('(hover: hover)').matches) return;
    clearTimeout(hoverCloseTimer);
    setDrop(d, true);
  });
  document.addEventListener('mouseout', function (e) {
    var d = e.target.closest && e.target.closest('[data-nav-drop]');
    if (!d || (e.relatedTarget && d.contains(e.relatedTarget))) return;
    clearTimeout(hoverCloseTimer);
    hoverCloseTimer = setTimeout(function () {
      if (!d.contains(document.activeElement)) setDrop(d, false);
    }, 320);
  });

  window.addEventListener('storage', renderCart);
  window.casRenderCart = renderCart;
})();
