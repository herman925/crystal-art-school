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

    const THEME_KEY = 'cas-theme';
    const valid = ['A','B','C','D'];
    const applyTheme = (t) => { root.setAttribute('data-theme', t); document.querySelectorAll('.theme-switch button').forEach(b => b.classList.toggle('active', b.dataset.theme === t)); };
    let saved = (valid.includes('A') ? 'A' : 'A');
    try { const s = localStorage.getItem(THEME_KEY); if (s && valid.includes(s)) saved = s; } catch (e) {}
    applyTheme(saved);
    document.querySelectorAll('.theme-switch button').forEach(b => b.addEventListener('click', () => { const t = b.dataset.theme; applyTheme(t); try { localStorage.setItem(THEME_KEY, t); } catch (e) {} }));

    const burger = document.querySelector('.nav-burger'), sheet = document.querySelector('.nav-sheet');
    if (burger && sheet) { burger.addEventListener('click', () => { const o = sheet.classList.toggle('open'); burger.setAttribute('aria-expanded', o ? 'true' : 'false'); }); sheet.addEventListener('click', (e) => { if (e.target.tagName === 'A') sheet.classList.remove('open'); }); }
    document.querySelectorAll('.cart-badge').forEach(b => { try { const n = JSON.parse(localStorage.getItem('cas-cart') || '[]').reduce((a, i) => a + i.qty, 0); b.textContent = n; b.style.display = n > 0 ? 'grid' : 'none'; } catch (e) {} });

    const form = document.getElementById('li-form');
    const fp = document.getElementById('fp-form');
    const stages = { login: form, fp: fp, fpSent: document.getElementById('fp-sent'), done: document.getElementById('li-done') };
    const show = (key) => { Object.keys(stages).forEach(k => stages[k].classList.toggle('active', k === key)); };
    const head = document.getElementById('li-head');
    const foot = document.getElementById('li-foot');
    const setHead = (ey, h, p) => { head.innerHTML = '<div class="eyebrow"><span class="star">✦</span> ' + ey + '</div><h1>' + h + '</h1><p>' + p + '</p>'; };

    const done = (msg) => {
      head.style.display = 'none'; foot.style.display = 'none';
      show('done');
      if (msg) document.getElementById('li-name').textContent = msg;
    };

    const userName = () => { try { const u = JSON.parse(localStorage.getItem('cas-user') || 'null'); return (u && u.name) ? ', ' + u.name : ''; } catch (er) { return ''; } };

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const btn = document.getElementById('li-submit');
      btn.textContent = 'Signing in…';
      setTimeout(() => done(userName()), 700);
    });
    document.getElementById('li-google').addEventListener('click', () => {
      const b = document.getElementById('li-google');
      b.disabled = true; b.style.opacity = '.7';
      const orig = b.innerHTML; b.innerHTML = b.innerHTML.replace('Continue with Google', 'Connecting to Google…');
      try { const u = JSON.parse(localStorage.getItem('cas-user') || '{}') || {}; u.name = u.name || 'Jane'; u.via = 'google'; localStorage.setItem('cas-user', JSON.stringify(u)); } catch (er) {}
      setTimeout(() => done(userName()), 850);
    });
    document.getElementById('li-guest').addEventListener('click', () => { window.location.href = 'Gallery.html'; });

    /* forgot-password pipeline */
    document.getElementById('li-forgot').addEventListener('click', (e) => {
      e.preventDefault();
      setHead('Reset access', 'Forgot password?', 'No worries — we\'ll email you a reset link.');
      foot.style.display = 'none';
      const liEmail = document.getElementById('li-email').value;
      if (liEmail) document.getElementById('fp-email').value = liEmail;
      show('fp');
    });
    const backToLogin = () => { setHead('Welcome back', 'Log in', 'Pick up exactly where you left off.'); foot.style.display = ''; show('login'); };
    document.getElementById('fp-back').addEventListener('click', backToLogin);
    document.getElementById('fp-return').addEventListener('click', backToLogin);
    fp.addEventListener('submit', (e) => {
      e.preventDefault();
      const em = document.getElementById('fp-email').value.trim();
      if (!em) { document.getElementById('fp-email').focus(); return; }
      const btn = document.getElementById('fp-submit'); btn.textContent = 'Sending…'; btn.disabled = true;
      setTimeout(() => { document.getElementById('fp-sent-email').textContent = em; head.style.display = 'none'; show('fpSent'); btn.textContent = 'Send reset link →'; btn.disabled = false; }, 750);
    });
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
