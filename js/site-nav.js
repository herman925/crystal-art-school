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

  function buildDrawer() {
    var h = '<div class="nav-mega" role="menu"><span class="mega-eyebrow">By medium <em>&middot; most popular first</em></span><div class="mega-cats">';
    for (var i = 0; i < CATS.length; i++) {
      h += '<a class="mega-cat' + (CATS[i][2] ? ' is-all' : '') + '" href="' + CATS[i][1] + '" role="menuitem"><span>' + CATS[i][0] + '</span></a>';
    }
    return h + '</div></div>';
  }

  function buildNav() {
    var links = '';
    for (var i = 0; i < LINKS.length; i++) {
      var l = LINKS[i], act = isActive(l) ? ' class="active"' : '';
      if (l[3] === 'drop') {
        links += '<li class="nav-drop" data-nav-drop><a href="' + l[1] + '"' + (isActive(l) ? ' class="nav-trigger active"' : ' class="nav-trigger"') + '>' + l[0] +
          ' <svg class="nav-caret" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 4.5 6 8.5 10 4.5"></path></svg></a>' + buildDrawer() + '</li>';
      } else {
        links += '<li><a href="' + l[1] + '"' + act + '>' + l[0] + '</a></li>';
      }
    }
    var sheet = '<a href="index.html">Home</a><a href="Lessons.dc.html">Courses — all</a>';
    for (var c = 1; c < CATS.length; c++) sheet += '<a href="' + CATS[c][1] + '">&middot; ' + CATS[c][0] + '</a>';
    for (var j = 2; j < LINKS.length; j++) sheet += '<a href="' + LINKS[j][1] + '">' + LINKS[j][0] + '</a>';
    sheet += '<a href="Login.dc.html">Log in / Sign up</a>';

    return '<div class="nav-top"><div class="inner">' +
      '<a class="nav-brand" href="index.html"><img src="img/lau-logo.png" alt="Lau. — Crystal\'s Art School"><span class="name">Crystal\'s Art School</span></a>' +
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
    '.nav-drop { position: relative; }' +
    '.nav-drop > .nav-trigger { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }' +
    '.nav-caret { width: 8px; height: 8px; opacity: .6; transition: transform .25s ease; }' +
    '.nav-drop:hover .nav-caret, .nav-drop.open .nav-caret { transform: rotate(180deg); }' +
    '.nav-mega { position: absolute; top: calc(100% + 12px); left: -8px; min-width: 248px;' +
    '  background: #26221e; border: 1px solid rgba(237,225,208,.14); border-radius: 14px;' +
    '  box-shadow: 0 26px 64px rgba(0,0,0,.55); padding: 8px;' +
    '  opacity: 0; visibility: hidden; transform: translateY(-8px);' +
    '  transition: opacity .2s ease, transform .2s ease, visibility .2s; z-index: 300; }' +
    '.nav-drop:hover .nav-mega, .nav-drop.open .nav-mega { opacity: 1; visibility: visible; transform: translateY(0); }' +
    '.nav-mega::before { content:""; position:absolute; top:-14px; left:0; right:0; height:14px; }' +
    '.mega-eyebrow { display:block; font-family: var(--font-cond); font-size: 10px; letter-spacing: .24em; text-transform: uppercase; color: #cf9457; padding: 8px 12px 8px; }' +
    '.mega-eyebrow em { font-style: normal; opacity: .8; }' +
    '.mega-cats { display:flex; flex-direction:column; gap:7px; padding: 2px 8px 12px; min-width: 216px; }' +
    '.nav-links a.mega-cat, .mega-cat { display:flex; align-items:center; justify-content:space-between; gap:12px;' +
    '  font-family: var(--font-cond); font-weight:600; font-size:13px; letter-spacing:.14em; text-transform:uppercase;' +
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
    '@media (max-width: 1100px) { .site-nav .nav-brand .name { display:none; } .site-nav .nav-links { gap: 4px; } }' +
    '@media (max-width: 860px) { .nav-mega { display:none; } }';
  var st = document.createElement('style');
  st.id = 'cas-nav-style';
  st.textContent = css;
  (document.head || document.documentElement).appendChild(st);

  function renderCart() {
    var n = 0;
    try { n = JSON.parse(localStorage.getItem('cas-cart') || '[]').reduce(function (a, i) { return a + (i.qty || 1); }, 0); } catch (e) {}
    var badges = document.querySelectorAll('.cart-badge');
    for (var i = 0; i < badges.length; i++) { badges[i].textContent = n; badges[i].style.display = n > 0 ? 'grid' : 'none'; }
  }

  /* ---- replace each page's nav with the canonical one ---- */
  var tries = 0;
  var iv = setInterval(function () {
    tries++;
    var nav = document.querySelector('nav.site-nav');
    if (nav && !nav.getAttribute('data-cas-synced')) {
      nav.innerHTML = buildNav();
      nav.setAttribute('data-cas-synced', '1');
      renderCart();
      clearInterval(iv);
    }
    if (tries > 300) clearInterval(iv);
  }, 50);

  /* ---- behaviour (delegated, so it survives the innerHTML swap) ---- */
  document.addEventListener('click', function (e) {
    var t = e.target;

    var burger = t.closest && t.closest('.nav-burger');
    if (burger) {
      var sheet = burger.closest('nav').querySelector('.nav-sheet');
      if (sheet) { var open = sheet.classList.toggle('open'); burger.setAttribute('aria-expanded', open ? 'true' : 'false'); }
      return;
    }
    var sheetLink = t.closest && t.closest('.nav-sheet a');
    if (sheetLink) { sheetLink.closest('.nav-sheet').classList.remove('open'); return; }

    var mode = t.closest && t.closest('.mode-toggle');
    if (mode) {
      var root = document.querySelector('.cas-root');
      if (root) {
        var next = root.getAttribute('data-theme') === 'D' ? 'A' : 'D';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('cas-theme', next); } catch (er) {}
      }
      return;
    }

    var trig = t.closest && t.closest('[data-nav-drop] .nav-trigger');
    if (trig && window.matchMedia('(hover: hover)').matches) {
      e.preventDefault();
      trig.closest('[data-nav-drop]').classList.toggle('open');
      return;
    }
    var drop = document.querySelector('[data-nav-drop].open');
    if (drop && !(t.closest && t.closest('[data-nav-drop]'))) drop.classList.remove('open');
  });

  window.addEventListener('storage', renderCart);
  window.casRenderCart = renderCart;
})();
