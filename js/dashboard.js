/* Auto-generated from Claude Design handoff bundle.
   The original Component class wrapped its init in componentDidMount and
   only ran setInterval timers in componentWillUnmount. We replicate that
   pair without a runtime, and hoist the timer handles onto `window` so the
   unload hook can find them. */
(function () {
  function start() {
    var comp = {

  pool() {
    return [
      { img: 'cover1.jpg', title: 'Light & shadow on the head', tag: 'Oils', price: 42 },
      { img: 'cover2.jpg', title: 'Blocking in large shapes', tag: 'Oils', price: 36 },
      { img: 'cover3.jpg', title: 'Mixing greens from scratch', tag: 'Watercolour', price: 29 },
      { img: 'Liquidwatercolor.jpg', title: 'Wet-on-wet washes', tag: 'Watercolour', price: 34 },
      { img: 'Postercolor.jpg', title: 'Flat & opaque in poster colour', tag: 'Poster', price: 38 },
      { img: 'Sketchbook.jpg', title: 'Fill a sketchbook in 30 days', tag: 'Drawing', price: 24 },
      { img: 'BrushA.jpg', title: 'Brush & ink studies', tag: 'Ink', price: 44 }
    ];
  },

  componentDidMount() {
    const root = document.querySelector('.cas-root');
    if (!root) return;
    const esc = (s) => String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const $ = (id) => document.getElementById(id);

    /* ---- theme ---- */
    const THEME_KEY = 'cas-theme';
    const valid = ['A', 'B', 'C', 'D'];
    const applyTheme = (t) => { root.setAttribute('data-theme', t); document.querySelectorAll('.theme-switch button').forEach(b => b.classList.toggle('active', b.dataset.theme === t)); };
    let saved = 'A' || 'A';
    try { const s = localStorage.getItem(THEME_KEY); if (s && valid.includes(s)) saved = s; } catch (e) {}
    applyTheme(saved);
    document.querySelectorAll('.theme-switch button').forEach(b => b.addEventListener('click', () => { const t = b.dataset.theme; applyTheme(t); try { localStorage.setItem(THEME_KEY, t); } catch (e) {} }));

    /* ---- burger ---- */
    const burger = document.querySelector('.nav-burger'), sheet = document.querySelector('.nav-sheet');
    if (burger && sheet) burger.addEventListener('click', () => { const open = sheet.classList.toggle('open'); burger.setAttribute('aria-expanded', open ? 'true' : 'false'); });

    /* ---- user ---- */
    let user = {};
    try { user = JSON.parse(localStorage.getItem('cas-user') || '{}') || {}; } catch (e) { user = {}; }
    const name = user.name || 'Friend';
    const first = String(name).split(' ')[0];
    const email = user.email || (first.toLowerCase() + '@example.com');
    $('nav-user-name').textContent = first;
    $('dash-greeting').textContent = 'Welcome back, ' + first + '.';
    $('dash-plan').textContent = (user.plan || 'Wanderer') + ' member';

    /* ---- enrolled courses (seed if empty) ---- */
    let enrolled;
    try { enrolled = JSON.parse(localStorage.getItem('cas-enrolled') || 'null'); } catch (e) { enrolled = null; }
    if (!enrolled || !enrolled.length) {
      enrolled = [
        { img: 'cover3.jpg', title: 'Materials & setting up', tier: 'Apprentice', units: 6, free: true },
        { img: 'cover1.jpg', title: 'Light & shadow on the head', tier: 'Fellow', units: 8, done: 3 },
        { img: 'Sketchbook.jpg', title: 'Fill a sketchbook in 30 days', tier: 'Apprentice', units: 10, done: 10 }
      ];
      try { localStorage.setItem('cas-enrolled', JSON.stringify(enrolled)); } catch (e) {}
    }
    // free-lesson real progress
    let freeDone = 0;
    try { const f = JSON.parse(localStorage.getItem('cas-free-lesson-progress') || 'null'); if (f && f.done) freeDone = f.done.filter(Boolean).length; } catch (e) {}
    const courseDone = (c) => c.free ? freeDone : (c.done || 0);
    const coursePct = (c) => Math.round((courseDone(c) / c.units) * 100);

    /* ---- orders (seed if empty) ---- */
    let orders;
    try { orders = JSON.parse(localStorage.getItem('cas-orders') || 'null'); } catch (e) { orders = null; }
    if (!orders || !orders.length) {
      orders = [
        { id: 'CAS-2041', date: '12 Jun 2026', title: 'Light & shadow on the head', tier: 'Fellow', img: 'cover1.jpg', amount: 76 },
        { id: 'CAS-1990', date: '28 May 2026', title: 'Fill a sketchbook in 30 days', tier: 'Apprentice', img: 'Sketchbook.jpg', amount: 24 }
      ];
      try { localStorage.setItem('cas-orders', JSON.stringify(orders)); } catch (e) {}
    }

    /* ---- stats ---- */
    const lessonsDone = enrolled.reduce((a, c) => a + courseDone(c), 0);
    $('stat-courses').textContent = enrolled.length;
    $('stat-lessons').textContent = lessonsDone;
    $('stat-since').textContent = user.since || 'Apr 2026';
    $('badge-courses').textContent = enrolled.length;

    /* ---- overview: resume card = lowest-progress in-progress course ---- */
    const inProg = enrolled.filter(c => coursePct(c) < 100).sort((a, b) => coursePct(b) - coursePct(a));
    const resume = inProg[0] || enrolled[0];
    const rp = coursePct(resume);
    $('resume-card').innerHTML =
      '<div class="resume-thumb"><img src="img/' + esc(resume.img) + '" alt=""></div>' +
      '<div class="resume-body"><div class="rk">' + esc(resume.tier) + ' · in progress</div>' +
      '<h3>' + esc(resume.title) + '</h3>' +
      '<div class="prog-row"><span>' + courseDone(resume) + ' of ' + resume.units + ' units</span><b>' + rp + '%</b></div>' +
      '<div class="prog"><i style="width:' + rp + '%"></i></div></div>' +
      '<a class="btn primary" href="lesson.html" style="flex:none;">Resume →</a>';

    /* ---- overview: recommended (focus-matched, not enrolled) ---- */
    const enrolledTitles = enrolled.map(c => c.title);
    const focus = (user.focus || 'Watercolour').toLowerCase();
    const recs = pool().filter(c => enrolledTitles.indexOf(c.title) < 0)
      .sort((a, b) => (b.tag.toLowerCase() === focus ? 1 : 0) - (a.tag.toLowerCase() === focus ? 1 : 0)).slice(0, 3);
    $('recommend-grid').innerHTML = recs.map(c =>
      '<div class="mc-course"><div class="cov"><img src="img/' + esc(c.img) + '" alt=""></div>' +
      '<div class="body"><div class="meta">' + esc(c.tag) + ' · £' + c.price + '</div><h3>' + esc(c.title) + '</h3>' +
      '<a class="btn ghost" href="Lessons.html" style="margin-top:auto;">View course →</a></div></div>').join('');

    /* ---- my courses ---- */
    $('courses-note').textContent = enrolled.length + ' enrolled';
    $('mycourse-grid').innerHTML = enrolled.map(c => {
      const p = coursePct(c), tierCls = c.tier === 'Fellow' ? ' fellow' : '';
      const cta = p >= 100 ? 'Review →' : (courseDone(c) > 0 ? 'Continue →' : 'Start →');
      return '<div class="mc-course"><div class="cov"><img src="img/' + esc(c.img) + '" alt=""><span class="mc-tier' + tierCls + '">' + esc(c.tier) + '</span></div>' +
        '<div class="body"><h3>' + esc(c.title) + '</h3>' +
        '<div class="prog-row" style="margin-bottom:0;"><span>' + (p >= 100 ? 'Completed' : courseDone(c) + '/' + c.units + ' units') + '</span><b>' + p + '%</b></div>' +
        '<div class="prog"><i style="width:' + p + '%"></i></div>' +
        '<a class="btn ' + (p >= 100 ? 'ghost' : 'primary') + '" href="lesson.html">' + cta + '</a></div></div>';
    }).join('');

    /* ---- progress ---- */
    const overall = Math.round(enrolled.reduce((a, c) => a + coursePct(c), 0) / enrolled.length);
    $('overall-pct').textContent = overall + '%';
    $('overall-bar').style.width = overall + '%';
    $('progress-list').innerHTML = enrolled.map(c => {
      const p = coursePct(c);
      return '<div class="dash-card" style="margin-top:0;margin-bottom:12px;"><div class="prog-row"><span><b>' + esc(c.title) + '</b> · ' + esc(c.tier) + '</span><b>' + p + '%</b></div><div class="prog"><i style="width:' + p + '%"></i></div></div>';
    }).join('');

    /* ---- purchases ---- */
    const money = (n) => '£' + n.toLocaleString();
    $('orders-body').innerHTML = orders.map(o =>
      '<tr><td style="font-family:var(--font-cond);color:var(--muted);">' + esc(o.id) + '</td>' +
      '<td style="color:var(--muted);">' + esc(o.date) + '</td>' +
      '<td><div class="ord-item"><img src="img/' + esc(o.img) + '" alt=""><div><div class="t">' + esc(o.title) + '</div><div class="ord-tier">' + esc(o.tier) + ' tier</div></div></div></td>' +
      '<td><span class="amt">' + money(o.amount) + '</span></td>' +
      '<td><span class="inv-link" data-inv="' + esc(o.id) + '">Invoice ↓</span></td></tr>').join('');

    /* ---- discord (Fellow-gated) ---- */
    const isFellow = enrolled.some(c => c.tier === 'Fellow') || (user.plan === 'Fellow');
    const dico = '<div class="dc-ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.5 5.3A17 17 0 0 0 15.3 4l-.2.4a12.7 12.7 0 0 1 3.7 1.2 12.6 12.6 0 0 0-11.6 0A12.6 12.6 0 0 1 10.9 4.4L10.7 4A17 17 0 0 0 6.5 5.3 17.7 17.7 0 0 0 3.4 17a17 17 0 0 0 5.2 2.6l.4-.6a11 11 0 0 1-1.8-.9l.4-.3a12.1 12.1 0 0 0 10.8 0l.4.3a11 11 0 0 1-1.8.9l.4.6A17 17 0 0 0 22.6 17a17.6 17.6 0 0 0-3.1-11.7zM9.3 14.4c-.8 0-1.5-.8-1.5-1.7s.7-1.7 1.5-1.7 1.5.8 1.5 1.7-.7 1.7-1.5 1.7zm5.4 0c-.8 0-1.5-.8-1.5-1.7s.7-1.7 1.5-1.7 1.5.8 1.5 1.7-.6 1.7-1.5 1.7z"></path></svg></div>';
    if (isFellow) {
      $('discord-slot').innerHTML = '<div class="dash-card discord-card">' + dico +
        '<div class="dc-body"><h3>You\'re a Fellow — welcome in.</h3><p>Your Fellow-tier courses include access to Crystal\'s private Discord for critique and questions. Click below; Crystal approves each new member manually, usually within a day.</p></div>' +
        '<a class="btn" href="#" id="discord-join">Join the Discord →</a></div>';
      const j = $('discord-join'); if (j) j.addEventListener('click', (e) => { e.preventDefault(); toast('Invite sent — Crystal will approve you shortly.'); });
    } else {
      $('discord-slot').innerHTML = '<div class="dash-card discord-card dc-locked">' + dico +
        '<div class="dc-body"><h3>Unlock with a Fellow course</h3><p>The private Discord is part of the Fellow tier. Buy any course at the Fellow tier to join the studio conversation.</p></div>' +
        '<a class="btn" href="Lessons.html">Browse courses →</a></div>';
    }

    /* ---- settings ---- */
    $('set-name').value = name;
    $('set-email').value = email;
    if (user.focus) { const sel = $('set-focus'); Array.from(sel.options).forEach(o => { if (o.value === user.focus) sel.value = user.focus; }); }
    $('save-account').addEventListener('click', () => {
      const u = Object.assign({}, user, { name: $('set-name').value.trim() || name, email: $('set-email').value.trim(), focus: $('set-focus').value });
      try { localStorage.setItem('cas-user', JSON.stringify(u)); } catch (e) {}
      $('nav-user-name').textContent = (u.name || 'Friend').split(' ')[0];
      $('dash-greeting').textContent = 'Welcome back, ' + (u.name || 'Friend').split(' ')[0] + '.';
      toast('Account saved.');
    });
    document.querySelectorAll('.set-tog').forEach(t => t.addEventListener('click', () => t.classList.toggle('on')));
    const op = $('open-privacy'); if (op) op.addEventListener('click', () => { if (window.casOpenPrivacy) window.casOpenPrivacy(); });
    $('delete-account').addEventListener('click', () => { if (window.casOpenPrivacy) window.casOpenPrivacy(); else toast('Erasure request received.'); });

    /* ---- invoices ---- */
    $('orders-body').addEventListener('click', (e) => { const inv = e.target.closest('[data-inv]'); if (inv) toast('Invoice ' + inv.getAttribute('data-inv') + ' downloaded.'); });

    /* ---- logout ---- */
    const logout = (e) => { e.preventDefault(); try { localStorage.removeItem('cas-user'); } catch (er) {} window.location.href = 'index.html'; };
    $('logout-btn').addEventListener('click', logout);
    const ls = $('logout-sheet'); if (ls) ls.addEventListener('click', logout);

    /* ---- tabs ---- */
    const tabs = Array.from(document.querySelectorAll('.dash-tab'));
    const panels = Array.from(document.querySelectorAll('.dash-panel'));
    const go = (name) => {
      tabs.forEach(t => t.classList.toggle('active', t.dataset.panel === name));
      panels.forEach(p => p.classList.toggle('active', p.dataset.panel === name));
      try { localStorage.setItem('cas-dash-tab', name); } catch (e) {}
    };
    tabs.forEach(t => t.addEventListener('click', () => go(t.dataset.panel)));
    let startTab = 'overview';
    try { const h = (location.hash || '').replace('#', ''); if (h && tabs.some(t => t.dataset.panel === h)) startTab = h; else { const s = localStorage.getItem('cas-dash-tab'); if (s && tabs.some(t => t.dataset.panel === s)) startTab = s; } } catch (e) {}
    go(startTab);

    /* ---- toast ---- */
    let toastTimer = null;
    function toast(msg) { const el = $('dash-toast'); el.textContent = msg; el.classList.add('show'); if (toastTimer) clearTimeout(toastTimer); toastTimer = setTimeout(() => el.classList.remove('show'), 2600); }
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
