/* Auto-generated from Claude Design handoff bundle.
   The original Component class wrapped its init in componentDidMount and
   only ran setInterval timers in componentWillUnmount. We replicate that
   pair without a runtime, and hoist the timer handles onto `window` so the
   unload hook can find them. */
(function () {
  function start() {
    var comp = {

  posts() {
    return [
      { title: 'Five brushes worth owning', excerpt: 'The short list of tools that actually change how you paint — and which ones to skip.', img: 'BrushA.jpg', tag: 'Tools', date: '2026-06-19', d: 'Jun 19' },
      { title: 'Slow looking: a warm-up', excerpt: 'Ten quiet minutes before you draw anything at all. A small ritual that pays off.', img: 'Sketchbook.jpg', tag: 'Practice', date: '2026-06-12', d: 'Jun 12' },
      { title: 'Mixing earthy neutrals', excerpt: 'Walnut, oak, iron — building the warm-neutral palette of a workshop from three tubes.', img: 'Liquidwatercolor.jpg', tag: 'Colour', date: '2026-06-04', d: 'Jun 04' },
      { title: 'Caring for your paper', excerpt: 'Storage, tooth, and why cold-press matters more than the brand on the block.', img: 'Postercolor.jpg', tag: 'Tools', date: '2026-05-27', d: 'May 27' },
      { title: 'The unhurried sketchbook', excerpt: 'Building a daily habit without the pressure of making anything good.', img: 'cover3.jpg', tag: 'Practice', date: '2026-05-18', d: 'May 18' },
      { title: 'Framing student work', excerpt: 'How we hang the studio wall — mats, bevels and the small mistakes to avoid.', img: 'cover1.jpg', tag: 'Studio', date: '2026-05-09', d: 'May 09' },
      { title: 'Reading light in a room', excerpt: 'Notes from late evenings by the window, and what shadows tell you about form.', img: 'studio.jpg', tag: 'Practice', date: '2026-04-30', d: 'Apr 30' },
      { title: 'Sharpening, the right way', excerpt: 'A knife, a block, and two minutes — the only pencil-point method you need.', img: 'cover4.jpg', tag: 'Tools', date: '2026-04-21', d: 'Apr 21' },
      { title: 'Oil vs poster colour', excerpt: 'Which to start with, and why it barely matters as much as you think.', img: 'cover2.jpg', tag: 'Colour', date: '2026-04-12', d: 'Apr 12' },
      { title: 'A studio worth returning to', excerpt: 'Concrete, plants, lamplight — designing a calm room you actually want to work in.', img: 'ProfilePic.jpg', tag: 'Studio', date: '2026-04-02', d: 'Apr 02' },
      { title: 'Three greys, endless range', excerpt: 'Warm and cool greys, and the quiet power of a limited value palette.', img: 'BrushB.jpg', tag: 'Colour', date: '2026-03-24', d: 'Mar 24' },
      { title: 'The eraser as a brush', excerpt: 'Subtractive drawing: lifting light back out of a toned ground.', img: 'Rubber.jpg', tag: 'Tools', date: '2026-03-11', d: 'Mar 11' }
    ];
  },

  componentDidMount() {
    const root = document.querySelector('.cas-root');
    if (!root) return;
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

    /* cart */
    const renderCart = () => { const n = (()=>{try{return JSON.parse(localStorage.getItem('cas-cart')||'[]').reduce((a,i)=>a+i.qty,0)}catch(e){return 0}})(); document.querySelectorAll('.cart-badge').forEach(b => { b.textContent = n; b.style.display = n > 0 ? 'grid' : 'none'; }); };
    renderCart();

    /* ---- BLOG ---- */
    const all = posts().slice().sort((a, b) => b.date.localeCompare(a.date)).slice(0, 10);
    const tags = ['All']; all.forEach(p => { if (!tags.includes(p.tag)) tags.push(p.tag); });
    let active = 'All';
    try { const u = new URLSearchParams(location.search); const qt = u.get('tag'); if (qt) { const m = tags.find(t => t.toLowerCase() === qt.toLowerCase()); if (m) active = m; } } catch (e) {}

    const filters = document.getElementById('blog-filters');
    const feature = document.getElementById('blog-feature');
    const grid = document.getElementById('blog-grid');
    const empty = document.getElementById('blog-empty');

    filters.innerHTML = tags.map(t => '<button class="chip' + (t === active ? ' active' : '') + '" type="button" data-tag="' + esc(t) + '">' + esc(t) + '</button>').join('');

    const render = () => {
      const list = active === 'All' ? all : all.filter(p => p.tag === active);
      empty.style.display = list.length ? 'none' : 'block';

      if (list.length) {
        const f = list[0];
        feature.style.display = 'grid';
        feature.innerHTML =
          '<a class="bf-img" href="blog-post.html" aria-label="' + esc(f.title) + '"><img src="img/' + f.img + '" alt="' + esc(f.title) + '"></a>' +
          '<div class="bf-body"><span class="bf-kicker">Latest · ' + esc(f.tag) + ' · ' + esc(f.d) + '</span>' +
          '<h2>' + esc(f.title) + '</h2><p>' + esc(f.excerpt) + '</p>' +
          '<a class="link-squiggle" href="blog-post.html" style="align-self:flex-start;">Read the post →</a></div>';
      } else {
        feature.style.display = 'none';
      }

      const rest = list.slice(1);
      grid.innerHTML = rest.map(p =>
        '<a class="blog-card" href="blog-post.html">' +
          '<div class="bc-img"><img src="img/' + p.img + '" alt="' + esc(p.title) + '"></div>' +
          '<div class="bc-body"><span class="bc-tag">' + esc(p.tag) + '</span>' +
          '<h3>' + esc(p.title) + '</h3><p>' + esc(p.excerpt) + '</p>' +
          '<span class="bc-date">' + esc(p.d) + '</span></div>' +
        '</a>'
      ).join('');
    };

    filters.querySelectorAll('.chip').forEach(c => c.addEventListener('click', () => {
      filters.querySelectorAll('.chip').forEach(x => x.classList.remove('active'));
      c.classList.add('active');
      active = c.dataset.tag;
      render();
    }));

    render();
    document.querySelectorAll('.reveal-one').forEach(el => el.classList.add('in-view'));

    /* ---- GSAP: roll posts in one-by-one as they scroll into view ---- */
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const rollIn = () => {
      const g = window.gsap;
      const cards = Array.from(grid.querySelectorAll('.blog-card'));
      const feat = feature.style.display !== 'none' ? [feature] : [];
      const items = feat.concat(cards);
      if (!items.length) return;
      if (!g || reduce) { items.forEach(el => { el.style.opacity = '1'; el.style.transform = 'none'; }); return; }
      g.set(items, { opacity: 0 });
      const watch = items.map(el => ({ el, done: false }));
      const drive = () => {
        const vh = window.innerHeight || document.documentElement.clientHeight;
        watch.forEach((w, i) => {
          if (w.done) return;
          const r = w.el.getBoundingClientRect();
          if (r.top < vh * 0.9 && r.bottom > 20) {
            w.done = true;
            const fromLeft = i % 2 === 0;
            g.fromTo(w.el,
              { opacity: 0, x: fromLeft ? -60 : 60, y: 28, rotateZ: fromLeft ? -3 : 3, transformOrigin: 'center top' },
              { opacity: 1, x: 0, y: 0, rotateZ: 0, duration: .7, ease: 'power3.out' });
          }
        });
      };
      drive();
      grid._blogDrive && window.removeEventListener('scroll', grid._blogDrive);
      document._blogDrive && document.removeEventListener('scroll', document._blogDrive, true);
      grid._blogDrive = drive;
      window.addEventListener('scroll', drive, { passive: true });
      document.addEventListener('scroll', drive, true);
      window.addEventListener('resize', drive);
      setTimeout(drive, 180);
    };
    rollIn();

    /* re-arm the roll-in after any filter change */
    const origRender = render;
    filters.querySelectorAll('.chip').forEach(c => c.addEventListener('click', () => { setTimeout(rollIn, 20); }));
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
