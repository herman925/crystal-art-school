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
    document.querySelectorAll('.cart-badge').forEach(b => { try { const n = JSON.parse(localStorage.getItem('cas-cart') || '[]').reduce((a, i) => a + i.qty, 0); b.textContent = n; b.style.display = n > 0 ? 'grid' : 'none'; } catch (e) {} });

    /* ---- signup pipeline ---- */
    const stages = Array.from(document.querySelectorAll('.auth-stage'));
    const bars = document.querySelectorAll('.auth-steps .as');
    const heading = document.getElementById('su-heading');
    const sub = document.getElementById('su-sub');
    const titles = { 1: ['Create your account', 'Step 1 of 3 — the basics'], 2: ['What brings you here?', 'Step 2 of 3 — your focus'], 3: ['Choose a plan', 'Step 3 of 3 — pick a tier'] };
    const show = (n) => {
      stages.forEach(s => s.classList.toggle('active', +s.dataset.su === n));
      bars.forEach(b => b.classList.toggle('on', +b.dataset.as <= n));
      if (titles[n]) { heading.textContent = titles[n][0]; sub.textContent = titles[n][1]; }
      if (n === 4) { document.getElementById('su-foot').style.display = 'none'; sub.style.display = 'none'; heading.style.display = 'none'; document.querySelector('.auth-steps').style.display = 'none'; document.querySelector('.auth-head .eyebrow').style.display = 'none'; }
    };

    // radio-group behaviour
    const groupSel = (attr) => document.querySelectorAll('[' + attr + ']').forEach(el => el.addEventListener('click', () => {
      document.querySelectorAll('[' + attr + ']').forEach(x => x.classList.remove('sel')); el.classList.add('sel');
    }));
    groupSel('data-focus'); groupSel('data-plan');

    const form = document.getElementById('su-form');
    const gbtn = document.getElementById('su-google');
    if (gbtn) gbtn.addEventListener('click', () => {
      gbtn.disabled = true; gbtn.style.opacity = '.7';
      gbtn.innerHTML = gbtn.innerHTML.replace('Sign up with Google', 'Connecting to Google…');
      setTimeout(() => {
        document.getElementById('su-name').value = 'Jane Doe';
        document.getElementById('su-email').value = 'jane@gmail.com';
        document.getElementById('su-pass').value = 'google-oauth';
        gbtn.disabled = false; gbtn.style.opacity = '';
        gbtn.innerHTML = gbtn.innerHTML.replace('Connecting to Google…', 'Sign up with Google');
        show(2); /* skip basics — Google provided them — go to focus */
      }, 850);
    });
    document.querySelectorAll('[data-next]').forEach(b => b.addEventListener('click', () => {
      const n = +b.dataset.next;
      if (n === 1) { const a = ['su-name', 'su-email', 'su-pass'].map(id => document.getElementById(id)); if (!a.every(i => i.reportValidity())) return; }
      show(n + 1);
    }));
    document.querySelectorAll('[data-back]').forEach(b => b.addEventListener('click', () => show(+b.dataset.back - 1)));

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const name = (document.getElementById('su-name').value || 'friend').split(' ')[0];
      const focus = (document.querySelector('[data-focus].sel') || {}).getAttribute ? document.querySelector('[data-focus].sel').dataset.focus : 'Watercolour';
      const plan = (document.querySelector('[data-plan].sel') || {}).getAttribute ? document.querySelector('[data-plan].sel').dataset.plan : 'Wanderer';
      document.getElementById('su-welcome').textContent = name;
      document.getElementById('su-focus-name').textContent = focus;
      document.getElementById('su-plan-name').textContent = plan;
      try { localStorage.setItem('cas-user', JSON.stringify({ name, plan, focus })); } catch (er) {}
      show(4);
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
