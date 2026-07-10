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
    let savedTheme = (valid.includes('A') ? 'A' : 'A');
    try { const s = localStorage.getItem(THEME_KEY); if (s && valid.includes(s)) savedTheme = s; } catch (e) {}
    applyTheme(savedTheme);
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

    /* ---- simulated lesson player ---- */
    const UNITS = [
      { title: "Welcome & what you'll need", dur: 252, img: 'img/cover3.jpg' },
      { title: 'Choosing paper', dur: 468, img: 'img/Sketchbook.jpg' },
      { title: 'Brushes that matter', dur: 485, img: 'img/BrushA.jpg' },
      { title: 'A starter palette of 8 colours', dur: 572, img: 'img/Postercolor.jpg' },
      { title: 'Workspace & water', dur: 381, img: 'img/BrushB.jpg' },
      { title: 'Your first wash', dur: 724, img: 'img/Liquidwatercolor.jpg' }
    ];
    const SPEED = 12;
    const KEY = 'cas-free-lesson-progress';

    let state = { unit: 0, t: 0, done: [false, false, false, false, false, false] };
    try { const sv = JSON.parse(localStorage.getItem(KEY) || 'null'); if (sv && sv.done && sv.done.length === 6) state = sv; } catch (e) {}
    const save = () => { try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {} };

    const $ = (id) => document.getElementById(id);
    const stage = $('stage'), poster = $('stage-poster'), flag = $('unit-flag'), veil = $('stage-veil'), playBtn = $('play-btn');
    const tCur = $('t-cur'), tDur = $('t-dur'), seek = $('seek'), seekFill = $('seek-fill'), seekKnob = $('seek-knob');
    const toast = $('done-toast'), completeBtn = $('complete-btn'), doneTag = $('done-tag'), finishCard = $('finish-card');
    const pfill = $('pfill'), doneCount = $('done-count');
    const rows = document.querySelectorAll('.unit-row'), notes = document.querySelectorAll('.unit-notes');

    let playing = false, raf = null, lastTick = 0;
    const fmt = (s) => { s = Math.max(0, Math.round(s)); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); };
    const doneTotal = () => state.done.reduce((n, d) => n + (d ? 1 : 0), 0);

    const renderProgress = () => {
      const n = doneTotal();
      doneCount.textContent = n;
      pfill.style.width = (n / 6 * 100) + '%';
      rows.forEach((row, i) => {
        row.classList.toggle('done', state.done[i]);
        row.classList.toggle('current', i === state.unit);
        row.querySelector('.st').textContent = state.done[i] ? '✓' : (i + 1);
        row.querySelector('.now').hidden = i !== state.unit;
      });
      doneTag.hidden = !state.done[state.unit];
      completeBtn.innerHTML = state.unit < 5 ? 'Mark complete &amp; continue <span class="arr" aria-hidden="true">→</span>' : 'Mark complete &amp; finish <span class="arr" aria-hidden="true">→</span>';
      if (n === 6) finishCard.classList.add('show'); else finishCard.classList.remove('show');
    };

    const renderTime = () => {
      const u = UNITS[state.unit];
      const pct = Math.min(100, state.t / u.dur * 100);
      tCur.textContent = fmt(state.t);
      seekFill.style.width = pct + '%';
      seekKnob.style.left = pct + '%';
      seek.setAttribute('aria-valuenow', Math.round(pct));
    };

    const setPlaying = (on) => {
      playing = on;
      stage.classList.toggle('is-playing', on);
      playBtn.textContent = on ? '❚❚' : '▶';
      playBtn.setAttribute('aria-label', on ? 'Pause' : 'Play');
      if (on) { lastTick = performance.now(); if (!raf) raf = requestAnimationFrame(tick); }
    };

    const loadUnit = (i, autoplay) => {
      state.unit = Math.max(0, Math.min(5, i));
      state.t = 0;
      const u = UNITS[state.unit];
      poster.src = u.img;
      poster.alt = 'Unit ' + (state.unit + 1) + ' — ' + u.title;
      flag.textContent = 'Unit ' + (state.unit + 1) + ' — ' + u.title;
      tDur.textContent = fmt(u.dur);
      notes.forEach(a => a.classList.toggle('active', parseInt(a.dataset.unit, 10) === state.unit));
      setPlaying(!!autoplay);
      renderTime();
      renderProgress();
      save();
    };

    const markDone = (viaVideo) => {
      if (!state.done[state.unit]) {
        state.done[state.unit] = true;
        save();
        if (viaVideo) { toast.classList.add('show'); setTimeout(() => toast.classList.remove('show'), 1300); }
      }
      renderProgress();
      if (state.unit < 5) {
        setTimeout(() => loadUnit(state.unit + 1, viaVideo), viaVideo ? 1450 : 250);
      } else {
        setPlaying(false); save(); renderProgress();
        if (finishCard.scrollIntoView) try { finishCard.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) {}
      }
    };

    const tick = (now) => {
      raf = null;
      if (!playing) return;
      const dt = (now - lastTick) / 1000 * SPEED;
      lastTick = now;
      state.t += dt;
      const u = UNITS[state.unit];
      if (state.t >= u.dur) { state.t = u.dur; renderTime(); setPlaying(false); markDone(true); return; }
      renderTime();
      raf = requestAnimationFrame(tick);
    };

    veil.addEventListener('click', () => setPlaying(true));
    playBtn.addEventListener('click', () => setPlaying(!playing));
    const seekTo = (e) => { const r = seek.getBoundingClientRect(); const k = Math.max(0, Math.min(1, (e.clientX - r.left) / r.width)); state.t = UNITS[state.unit].dur * k; renderTime(); save(); };
    seek.addEventListener('click', seekTo);
    rows.forEach(row => row.addEventListener('click', () => loadUnit(parseInt(row.dataset.unit, 10), true)));
    completeBtn.addEventListener('click', () => markDone(false));
    $('reset-btn').addEventListener('click', () => { state = { unit: 0, t: 0, done: [false, false, false, false, false, false] }; save(); loadUnit(0, false); });
    $('watch-again').addEventListener('click', () => { state = { unit: 0, t: 0, done: [false, false, false, false, false, false] }; save(); loadUnit(0, true); try { stage.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) {} });

    loadUnit(state.unit, false);
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
