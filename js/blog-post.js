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
    document.querySelectorAll('.cart-badge').forEach(b => { try { const n = JSON.parse(localStorage.getItem('cas-cart') || '[]').reduce((a, i) => a + i.qty, 0); b.textContent = n; b.style.display = n > 0 ? 'grid' : 'none'; } catch (e) {} });

    /* smooth-scroll TOC */
    document.querySelectorAll('.toc a[href^="#"]').forEach(a => a.addEventListener('click', (e) => {
      const id = a.getAttribute('href').slice(1);
      const target = document.getElementById(id);
      if (target) { e.preventDefault(); const y = target.getBoundingClientRect().top + (window.pageYOffset || 0) - 80; window.scrollTo({ top: y, behavior: 'smooth' }); }
    }));

    /* comment form */
    const form = document.getElementById('comment-form');
    if (form) form.addEventListener('submit', (e) => { e.preventDefault(); const s = form.querySelector('.sent'); if (s) s.style.display = 'block'; form.reset(); });

    /* reveals */
    document.querySelectorAll('.reveal-one, .section-title').forEach(el => el.classList.add('in-view'));
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
