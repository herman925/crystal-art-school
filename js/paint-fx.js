/* ============================================================
   paint-fx.js — Crystal's Art School, v2.7.1
   Background brush layer (full redesign after client feedback):
   · ONE colour per stroke — colours never interleave mid-gesture
     (interleaved pastels multiplied into mud)
   · a stroke is a continuous ribbon of soft stamps laid along the
     cursor path — bristle jitter gives thick→light randomised
     texture, and the brush "runs dry" the longer the stroke goes
   · deliberately quiet: low alpha, multiply blend
   · paints BEHIND the page (z-index:-1) — visible on the paper
     background only, never over cards, text or images
   Guardrails (design system §4.1):
   · fine pointers only · prefers-reduced-motion off
   · inert over the 3D gallery canvas · stamp cap, then oldest dry
   ============================================================ */
(function () {
  'use strict';
  if (!window.matchMedia('(pointer: fine)').matches) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var canvas = document.createElement('canvas');
  canvas.id = 'paint-fx';
  canvas.setAttribute('aria-hidden', 'true');
  canvas.style.cssText =
    'position:fixed;inset:0;width:100vw;height:100vh;' +
    'pointer-events:none;z-index:-1;mix-blend-mode:multiply;';
  document.body.appendChild(canvas);
  var ctx = canvas.getContext('2d');

  var dpr = Math.min(window.devicePixelRatio || 1, 2);
  function resize() {
    canvas.width = window.innerWidth * dpr;
    canvas.height = window.innerHeight * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }
  resize();
  window.addEventListener('resize', resize);

  /* brand pastels — one per stroke, rotated between strokes */
  var PALETTE = [
    [245, 149, 149],
    [150, 211, 217],
    [229, 193, 179],
    [214, 156, 133],
    [197, 250, 238]
  ];

  /* soft brush-tip sprites, one per colour, rendered once */
  var SPRITES = PALETTE.map(function (c) {
    var s = document.createElement('canvas');
    s.width = s.height = 64;
    var sc = s.getContext('2d');
    var g = sc.createRadialGradient(32, 32, 0, 32, 32, 32);
    var rgb = c[0] + ',' + c[1] + ',' + c[2];
    g.addColorStop(0, 'rgba(' + rgb + ',1)');
    g.addColorStop(0.55, 'rgba(' + rgb + ',0.7)');
    g.addColorStop(1, 'rgba(' + rgb + ',0)');
    sc.fillStyle = g;
    sc.fillRect(0, 0, 64, 64);
    return s;
  });

  /* stamp lifecycle (ms): settle in fast → stay wet → dry away */
  var BLOOM = 160, HOLD = 2600, DRY = 3600;
  var LIFE = BLOOM + HOLD + DRY;
  var MAX_STAMPS = 900;
  var STEP = 5;                 /* px between stamps along the path */
  var STROKE_GAP = 280;         /* ms idle → next movement = new stroke */
  var DEPLETE = 0.0011;         /* paint used per px — long stroke runs dry */

  var stamps = [], drops = [];
  var raf = null;
  var ci = -1;

  /* stroke state */
  var sx = -1, sy = -1;         /* last stamped point */
  var lastMove = 0;
  var load = 0;                 /* paint left on the brush, 0..1 */
  var baseW = 12;

  function wake() { if (raf === null) raf = requestAnimationFrame(tick); }

  function overOtherCanvas(t) {
    return t && t.closest && t.closest('canvas') && t.id !== 'paint-fx';
  }

  function newStroke(x, y) {
    ci = (ci + 1) % PALETTE.length;       /* one colour for the whole stroke */
    load = 0.75 + Math.random() * 0.25;   /* freshly dipped brush */
    baseW = 11 + Math.random() * 9;
    sx = x; sy = y;
  }

  function addStamp(x, y, scale, alpha, now) {
    if (alpha <= 0.004) return;
    if (stamps.length >= MAX_STAMPS) stamps.shift();
    stamps.push({ x: x, y: y, s: scale, a: alpha, c: ci, born: now });
  }

  /* lay bristle stamps from (sx,sy) toward (x,y) */
  function layRibbon(x, y, now) {
    var dx = x - sx, dy = y - sy;
    var dist = Math.sqrt(dx * dx + dy * dy);
    if (dist < STEP) return;
    var nx = dx / dist, ny = dy / dist;         /* along the motion */
    var px = -ny, py = nx;                      /* perpendicular */
    var speedThin = 1 / (1 + dist * 0.012);     /* fast gesture = lighter touch */
    var steps = Math.min(60, Math.floor(dist / STEP));
    for (var i = 1; i <= steps; i++) {
      var t = i / steps;
      var cx = sx + dx * t, cy = sy + dy * t;
      load = Math.max(0, load - DEPLETE * STEP);
      if (load <= 0) break;
      /* body of the stroke — width and tone wander like real bristles */
      var w = baseW * (0.75 + Math.random() * 0.5) * (0.45 + load * 0.55) * speedThin;
      var off = (Math.random() - 0.5) * baseW * 0.5;
      addStamp(cx + px * off, cy + py * off, w, load * (0.09 + Math.random() * 0.09), now);
      /* occasional dry-brush flick at the edge */
      if (Math.random() < 0.35) {
        var off2 = (Math.random() < 0.5 ? -1 : 1) * baseW * (0.5 + Math.random() * 0.45);
        addStamp(cx + px * off2, cy + py * off2, w * 0.45, load * (0.04 + Math.random() * 0.05), now);
      }
    }
    sx = x; sy = y;
  }

  window.addEventListener('mousemove', function (e) {
    if (overOtherCanvas(e.target)) { lastMove = 0; return; }
    var now = performance.now();
    if (!lastMove || now - lastMove > STROKE_GAP || sx < 0) {
      newStroke(e.clientX, e.clientY);
    } else {
      layRibbon(e.clientX, e.clientY, now);
    }
    lastMove = now;
    wake();
  }, { passive: true });

  /* —— click: small splatter in the CURRENT stroke colour only —— */
  window.addEventListener('pointerdown', function (e) {
    if (e.pointerType !== 'mouse' || e.button !== 0) return;
    if (overOtherCanvas(e.target)) return;
    burst(e.clientX, e.clientY, 0.4);
  }, { passive: true });

  function burst(x, y, strength) {
    strength = strength || 1;
    if (ci < 0) ci = (Math.random() * PALETTE.length) | 0;
    var now = performance.now();
    addStamp(x, y, 16 * strength + 8, 0.16 * strength, now);
    var n = 6 + ((Math.random() * 5) | 0);
    for (var i = 0; i < n; i++) {
      var ang = Math.random() * Math.PI * 2;
      var sp = (1.2 + Math.random() * 3.6) * strength;
      drops.push({
        x: x, y: y,
        vx: Math.cos(ang) * sp,
        vy: Math.sin(ang) * sp - 1,
        r: (1.5 + Math.random() * 3) * strength,
        born: now,
        flight: 240 + Math.random() * 300,
        c: ci
      });
    }
    wake();
  }
  /* exposed for page scripts (lesson completion celebration) */
  window.casPaintBurst = function (x, y) { burst(x, y, 1); };

  function tick() {
    ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
    var now = performance.now();
    var i, d, t, a, sz;

    for (i = stamps.length - 1; i >= 0; i--) {
      d = stamps[i];
      t = now - d.born;
      if (t >= LIFE) { stamps.splice(i, 1); continue; }
      if (t < BLOOM) {
        a = d.a * (t / BLOOM);
      } else if (t < BLOOM + HOLD) {
        a = d.a;
      } else {
        var k = (t - BLOOM - HOLD) / DRY;
        a = d.a * (1 - k * k * (3 - 2 * k));   /* smoothstep dry-out */
      }
      ctx.globalAlpha = a;
      sz = d.s * 2;
      ctx.drawImage(SPRITES[d.c], d.x - d.s, d.y - d.s, sz, sz);
    }
    ctx.globalAlpha = 1;

    for (i = drops.length - 1; i >= 0; i--) {
      d = drops[i];
      d.x += d.vx; d.y += d.vy; d.vy += 0.12; d.vx *= 0.985;
      if (now - d.born >= d.flight) {
        if (stamps.length >= MAX_STAMPS) stamps.shift();
        stamps.push({ x: d.x, y: d.y, s: d.r * 2.4, a: 0.12 + Math.random() * 0.08, c: d.c, born: now });
        drops.splice(i, 1);
        continue;
      }
      ctx.globalAlpha = 0.5;
      sz = d.r * 2;
      ctx.drawImage(SPRITES[d.c], d.x - d.r, d.y - d.r, sz, sz);
    }
    ctx.globalAlpha = 1;

    if (stamps.length || drops.length) {
      raf = requestAnimationFrame(tick);
    } else {
      raf = null;
      ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
    }
  }
})();
