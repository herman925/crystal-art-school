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

    /* cart */
    const renderCart = () => { let n = 0; try { n = JSON.parse(localStorage.getItem('cas-cart') || '[]').reduce((a, i) => a + i.qty, 0); } catch (e) {} document.querySelectorAll('.cart-badge').forEach(b => { b.textContent = n; b.style.display = n > 0 ? 'grid' : 'none'; }); };
    renderCart();

    /* contact form */
    const form = document.getElementById('contact-form');
    if (form) form.addEventListener('submit', (e) => { e.preventDefault(); const s = form.querySelector('.sent'); if (s) s.style.display = 'block'; form.reset(); });

    /* reveals */
    document.querySelectorAll('.reveal-one, .section-title, .reveal').forEach(el => el.classList.add('in-view'));
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
