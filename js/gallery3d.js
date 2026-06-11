/* Crystal's Art School — 3D exhibition hall (three.js)
   One museum, two wings off a lobby:
     west (−x) "Fresh Eyes"  — student showcase
     east (+x) "Art Showcase" — Crystal's work (Buy → Etsy)
   Works are data-driven (dynamic count); frames size themselves from each
   texture's natural aspect ratio and hang on a shared eye-line rail. */

import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js';

/* ---------------- data (dynamic — capacity 20 per wing) ---------------- */
const IMGS = [
  'img/cover1.jpg', 'img/cover2.jpg', 'img/cover3.jpg', 'img/cover4.jpg',
  'img/BrushA.jpg', 'img/BrushB.jpg', 'img/Liquidwatercolor.jpg',
  'img/Postercolor.jpg', 'img/Rubber.jpg', 'img/Sketchbook.jpg',
  'img/Bannerphoto.jpg', 'img/ProfilePic.jpg'
];
const ETSY = 'https://www.etsy.com/shop/crystalsartschool';

const TEACHER_TITLES = [
  'Morning at the harbour', 'Peonies in a jam jar', 'Study in warm greys',
  'The blue hour', 'Portrait of a stranger', 'Wet street, Kowloon',
  'Three pears', 'Quiet corner of the studio', 'First snow on the hill',
  'Reading by the window', 'Tide pools', 'The yellow raincoat',
  'Garlic and copper pot', 'Evening ferry', 'Sunday flowers',
  'The half-finished cup', 'Rain on the skylight', 'Old brushes, old friends',
  'Lantern festival', 'Towards the sea'
];
const STUDENTS = [
  'Mia L.', 'Ethan C.', 'Chloe W.', 'Hugo T.', 'Yuki S.',
  'Anya P.', 'Marcus F.', 'Lily K.', 'Theo R.', 'Sara N.',
  'Ivy H.', 'Noah B.', 'Grace Y.', 'Owen D.', 'Hana M.',
  'Felix J.', 'Ruby Z.', 'Leo A.', 'Wren O.', 'Kai V.'
];
const STUDENT_TITLES = [
  'My grandmother\'s hands', 'The school gate', 'Goldfish dream',
  'Rainy Tuesday', 'My brother sleeping', 'The mango tree',
  'Bus ride home', 'Self portrait, age 9', 'The lighthouse',
  'Mum\'s kitchen', 'Typhoon day', 'My dog Mochi',
  'The swimming lesson', 'Night market', 'Paper planes',
  'The big wave', 'Grandpa\'s radio', 'Star ceiling',
  'The red door', 'Summer holiday'
];

const eastWorks = TEACHER_TITLES.map((t, i) => ({
  title: t, img: IMGS[i % IMGS.length], author: 'Crystal Lau', kind: 'teacher'
}));
const westWorks = STUDENT_TITLES.map((t, i) => ({
  title: t, img: IMGS[(i + 5) % IMGS.length], author: STUDENTS[i], kind: 'student'
}));

/* ---------------- museum dimensions ---------------- */
const HALL_HALF_W = 4;        // corridor z half-width
const WALL_H = 4.4;
const EYE = 1.62;             // shared hanging eye-line
const LOBBY_HALF = 8;
const SLOT = 4.4;             // distance between works on one wall
const slots = (n) => Math.ceil(n / 2);
const wingLen = (n) => slots(n) * SLOT + 4;
const WEST_LEN = wingLen(westWorks.length);
const EAST_LEN = wingLen(eastWorks.length);
const X_MIN = -LOBBY_HALF - WEST_LEN;
const X_MAX = LOBBY_HALF + EAST_LEN;

/* ---------------- palette (pastel architecture) ---------------- */
const COL = {
  /* lobby — marble neutral */
  wall:      0xf3f1ec,   // cool near-white
  wallTint:  0xeae8e0,
  ceil:      0xfaf8f3,
  base:      0xd6cfba,
  /* east wing — warm gallery (Art Showcase) */
  eastWall:  0xf4e8d0,
  eastFloor: 0xd8c4a0,
  eastCeil:  0xfaf4e8,
  /* west wing — cool studio (Fresh Eyes) */
  westWall:  0xe4f0ec,
  westFloor: 0xbfd8cc,
  westCeil:  0xf0faf7,
  ink: 0x15130a,
  teal: 0x96d3d9,
  blush: 0xe5c1b3
};

/* marble texture for lobby floor (canvas-generated) */
function marbleTexture() {
  const cv = document.createElement('canvas');
  cv.width = 512; cv.height = 512;
  const cx = cv.getContext('2d');
  cx.fillStyle = '#F4F2EE';
  cx.fillRect(0, 0, 512, 512);
  /* subtle checkerboard tiles */
  const ts = 64;
  for (let r = 0; r < 8; r++) for (let c = 0; c < 8; c++) {
    if ((r + c) % 2 === 0) { cx.fillStyle = 'rgba(192,188,178,0.22)'; cx.fillRect(c * ts, r * ts, ts, ts); }
  }
  /* grey veins — deterministic paths so texture is consistent */
  const veins = [
    [[0.12,0],[0.34,0.42],[0.58,0.88],[0.82,1.0]],
    [[0.44,0],[0.22,0.35],[0.68,0.65],[0.56,1.0]],
    [[0.75,0],[0.88,0.28],[0.42,0.72],[0.18,1.0]],
    [[0,0.24],[0.38,0.44],[0.72,0.60],[1.0,0.88]],
    [[0,0.66],[0.28,0.52],[0.64,0.78],[1.0,0.42]],
  ];
  cx.strokeStyle = 'rgba(168,160,150,0.45)'; cx.lineWidth = 0.9;
  veins.forEach(pts => {
    cx.beginPath();
    cx.moveTo(pts[0][0]*512, pts[0][1]*512);
    cx.bezierCurveTo(pts[1][0]*512,pts[1][1]*512, pts[2][0]*512,pts[2][1]*512, pts[3][0]*512,pts[3][1]*512);
    cx.stroke();
  });
  cx.strokeStyle = 'rgba(200,196,186,0.3)'; cx.lineWidth = 1.8;
  veins.slice(0,3).forEach(pts => {
    cx.beginPath();
    cx.moveTo(pts[0][0]*512+18, pts[0][1]*512+12);
    cx.bezierCurveTo(pts[1][0]*512+10,pts[1][1]*512-8, pts[2][0]*512-12,pts[2][1]*512+14, pts[3][0]*512-6,pts[3][1]*512+8);
    cx.stroke();
  });
  const tx = new THREE.CanvasTexture(cv);
  tx.colorSpace = THREE.SRGBColorSpace;
  tx.wrapS = tx.wrapT = THREE.RepeatWrapping;
  tx.repeat.set(3, 3);
  return tx;
}

const stage = document.getElementById('stage');
let renderer;
try {
  renderer = new THREE.WebGLRenderer({ antialias: true });
} catch (e) {
  showFallback();
  throw e;
}
window.__g3dOK = true;
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
renderer.setSize(stage.clientWidth, stage.clientHeight);
renderer.outputColorSpace = THREE.SRGBColorSpace;
stage.appendChild(renderer.domElement);

const scene = new THREE.Scene();
scene.background = new THREE.Color(COL.wall);
scene.fog = new THREE.Fog(COL.wall, 26, 64);

const camera = new THREE.PerspectiveCamera(64, stage.clientWidth / stage.clientHeight, 0.1, 200);
camera.position.set(0, EYE, 2.6);
let yaw = 0;                  // facing −z: lobby welcome wall
let pitch = 0;

/* ---------------- lights ---------------- */
scene.add(new THREE.HemisphereLight(0xfffdf2, 0xe8ddc8, 1.35));
const amb = new THREE.AmbientLight(0xfffaf0, 0.55);
scene.add(amb);

/* ---------------- architecture ---------------- */
const lambert = (c) => new THREE.MeshLambertMaterial({ color: c });
const TOTAL_LEN = X_MAX - X_MIN;
const CENTER_X = (X_MIN + X_MAX) / 2;

function box(w, h, d, mat, x, y, z) {
  const m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), mat);
  m.position.set(x, y, z);
  scene.add(m);
  return m;
}

/* === West wing (Fresh Eyes) — cool sage/mint === */
const westMid = (X_MIN + (-LOBBY_HALF)) / 2;
const westLen = -LOBBY_HALF - X_MIN;
box(westLen, 0.1, HALL_HALF_W * 2, lambert(COL.westFloor), westMid, -0.05, 0);
box(westLen, 0.1, HALL_HALF_W * 2, lambert(COL.westCeil),  westMid, WALL_H + 0.05, 0);
box(westLen, WALL_H, 0.2, lambert(COL.westWall), westMid, WALL_H / 2, -HALL_HALF_W - 0.1);
box(westLen, WALL_H, 0.2, lambert(COL.westWall), westMid, WALL_H / 2,  HALL_HALF_W + 0.1);

/* === Lobby — marble neutral === */
const lobbyFloorMesh = new THREE.Mesh(
  new THREE.BoxGeometry(LOBBY_HALF * 2, 0.1, HALL_HALF_W * 2),
  new THREE.MeshLambertMaterial({ map: marbleTexture() }));
lobbyFloorMesh.position.set(0, -0.05, 0);
scene.add(lobbyFloorMesh);
box(LOBBY_HALF * 2, 0.1, HALL_HALF_W * 2, lambert(COL.ceil),  0, WALL_H + 0.05, 0);
box(LOBBY_HALF * 2, WALL_H, 0.2, lambert(COL.wall), 0, WALL_H / 2, -HALL_HALF_W - 0.1);
box(LOBBY_HALF * 2, WALL_H, 0.2, lambert(COL.wall), 0, WALL_H / 2,  HALL_HALF_W + 0.1);

/* === East wing (Art Showcase) — warm amber === */
const eastMid = (LOBBY_HALF + X_MAX) / 2;
const eastLen = X_MAX - LOBBY_HALF;
box(eastLen, 0.1, HALL_HALF_W * 2, lambert(COL.eastFloor), eastMid, -0.05, 0);
box(eastLen, 0.1, HALL_HALF_W * 2, lambert(COL.eastCeil),  eastMid, WALL_H + 0.05, 0);
box(eastLen, WALL_H, 0.2, lambert(COL.eastWall), eastMid, WALL_H / 2, -HALL_HALF_W - 0.1);
box(eastLen, WALL_H, 0.2, lambert(COL.eastWall), eastMid, WALL_H / 2,  HALL_HALF_W + 0.1);

/* end walls */
box(0.2, WALL_H, HALL_HALF_W * 2, lambert(COL.wallTint), X_MIN - 0.1, WALL_H / 2, 0);
box(0.2, WALL_H, HALL_HALF_W * 2, lambert(COL.wallTint), X_MAX + 0.1, WALL_H / 2, 0);
/* baseboards (unified — low visual impact) */
box(TOTAL_LEN, 0.18, 0.06, lambert(COL.base), CENTER_X, 0.09, -HALL_HALF_W + 0.03);
box(TOTAL_LEN, 0.18, 0.06, lambert(COL.base), CENTER_X, 0.09,  HALL_HALF_W - 0.03);
/* portal beams at wing entrances */
for (const px of [-LOBBY_HALF, LOBBY_HALF]) {
  box(0.5, 0.5, HALL_HALF_W * 2, lambert(COL.blush), px, WALL_H - 0.25, 0);
  box(0.5, WALL_H, 0.5, lambert(COL.blush), px, WALL_H / 2, -HALL_HALF_W + 0.25);
  box(0.5, WALL_H, 0.5, lambert(COL.blush), px, WALL_H / 2,  HALL_HALF_W - 0.25);
}
/* lobby marble inlay strip + bench */
box(LOBBY_HALF * 2 - 1.4, 0.015, HALL_HALF_W * 2 - 1.4, lambert(0xe8e4de), 0, 0.01, 0);
box(3.2, 0.42, 0.9, lambert(COL.blush), 0, 0.21, 0);

/* wing-tinted accent lights */
const eastLight = new THREE.PointLight(0xFFE8B8, 0.7, eastLen + 6);
eastLight.position.set(eastMid, WALL_H - 0.6, 0);
scene.add(eastLight);
const westLight = new THREE.PointLight(0xC8F0EA, 0.55, westLen + 6);
westLight.position.set(westMid, WALL_H - 0.6, 0);
scene.add(westLight);

/* ---------------- text helpers (canvas textures) ---------------- */
function textTexture(lines, w, h, opts = {}) {
  const cv = document.createElement('canvas');
  cv.width = w; cv.height = h;
  const cx = cv.getContext('2d');
  cx.fillStyle = opts.bg || '#FCFAED';
  cx.fillRect(0, 0, w, h);
  cx.fillStyle = opts.fg || '#15130A';
  cx.textAlign = 'center';
  let y = opts.top || h / 2;
  for (const ln of lines) {
    cx.font = ln.font;
    cx.fillText(ln.text, w / 2, y);
    y += ln.gap || 40;
  }
  const tx = new THREE.CanvasTexture(cv);
  tx.colorSpace = THREE.SRGBColorSpace;
  tx.anisotropy = 4;
  return tx;
}

function signPlane(texture, w, h, x, y, z, ry = 0) {
  const m = new THREE.Mesh(
    new THREE.PlaneGeometry(w, h),
    new THREE.MeshBasicMaterial({ map: texture, transparent: false })
  );
  m.position.set(x, y, z);
  m.rotation.y = ry;
  scene.add(m);
  return m;
}

/* wing name signs above each portal (both faces) + lobby welcome */
const eastSign = textTexture(
  [{ text: 'ART SHOWCASE', font: '500 64px Italiana, serif', gap: 0 }],
  1024, 160, { bg: '#F3EFE3' });
const westSign = textTexture(
  [{ text: 'FRESH EYES', font: '500 64px Italiana, serif', gap: 54 },
   { text: 'the student wing', font: '500 44px Caveat, cursive' }],
  1024, 220, { bg: '#F3EFE3' });
signPlane(eastSign, 4.4, 0.7, LOBBY_HALF + 0.36, WALL_H - 0.85, 0, -Math.PI / 2);
signPlane(eastSign, 4.4, 0.7, LOBBY_HALF - 0.36, WALL_H - 0.85, 0, Math.PI / 2);
signPlane(westSign, 4.4, 0.95, -LOBBY_HALF - 0.36, WALL_H - 0.95, 0, Math.PI / 2);
signPlane(westSign, 4.4, 0.95, -LOBBY_HALF + 0.36, WALL_H - 0.95, 0, -Math.PI / 2);
const welcome = textTexture(
  [{ text: "Crystal's Art School", font: '400 86px "Permanent Marker", cursive', gap: 74 },
   { text: 'T H E   G A L L E R Y', font: '500 40px Italiana, serif' }],
  1400, 320, { bg: '#FCFAED', top: 140 });
signPlane(welcome, 6.2, 1.42, 0, 2.4, -HALL_HALF_W + 0.02, 0);
const welcome2 = textTexture(
  [{ text: 'two wings · twenty works each', font: '500 52px Caveat, cursive' }],
  1200, 140, { bg: '#FCFAED', top: 90 });
signPlane(welcome2, 4.6, 0.54, 0, 2.3, HALL_HALF_W - 0.02, Math.PI);

/* ---------------- artworks ---------------- */
const loader = new THREE.TextureLoader();
const clickable = [];

function hangWork(work, x, sideZ) {
  /* sideZ −1 → north wall (faces +z) · +1 → south wall (faces −z) */
  const group = new THREE.Group();
  const wallZ = sideZ * (HALL_HALF_W - 0.02);
  group.position.set(x, EYE + 0.55, wallZ);
  group.rotation.y = sideZ === -1 ? 0 : Math.PI;
  scene.add(group);

  loader.load(work.img, (tx) => {
    tx.colorSpace = THREE.SRGBColorSpace;
    tx.anisotropy = 8;
    const iw = tx.image.width, ih = tx.image.height;
    const aspect = iw / ih;
    /* shared eye-line rail: cap both dimensions, honour native ratio */
    let h = 1.45, w = h * aspect;
    if (w > 2.3) { w = 2.3; h = w / aspect; }
    /* frame (pastel wood) + mat + canvas */
    const frame = new THREE.Mesh(
      new THREE.BoxGeometry(w + 0.22, h + 0.22, 0.07),
      lambert(work.kind === 'teacher' ? 0xe8dcc8 : 0xdfe9e3));
    frame.position.z = 0.035;
    const mat = new THREE.Mesh(
      new THREE.PlaneGeometry(w + 0.12, h + 0.12),
      new THREE.MeshBasicMaterial({ color: 0xffffff }));
    mat.position.z = 0.075;
    const canvas = new THREE.Mesh(
      new THREE.PlaneGeometry(w, h),
      new THREE.MeshBasicMaterial({ map: tx }));
    canvas.position.z = 0.078;
    canvas.userData.work = work;
    group.add(frame, mat, canvas);
    clickable.push(canvas);

    /* plaque beside the work */
    const plq = textTexture(
      [{ text: work.title, font: '700 34px Quicksand, sans-serif', gap: 44 },
       { text: work.author, font: '500 34px Caveat, cursive' }],
      512, 150, { bg: '#FFFFFF', top: 62 });
    const plaque = new THREE.Mesh(
      new THREE.PlaneGeometry(0.62, 0.18),
      new THREE.MeshBasicMaterial({ map: plq }));
    plaque.position.set(w / 2 + 0.45, -h / 2 + 0.05, 0.04);
    group.add(plaque);
  });
}

function hangWing(works, startX, dir) {
  works.forEach((work, i) => {
    const slot = Math.floor(i / 2);
    const side = i % 2 === 0 ? -1 : 1;
    const x = startX + dir * (2.6 + slot * SLOT);
    hangWork(work, x, side);
  });
}
hangWing(eastWorks, LOBBY_HALF, +1);
hangWing(westWorks, -LOBBY_HALF, -1);

/* ---------------- movement ---------------- */
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
let dragging = false, lastX = 0, lastY = 0, dragDist = 0;
let walkVel = 0, walkHold = 0;

/* speed multiplier — read from #speed-slider (default 1.4) */
let speedMult = 1.4;
const speedEl = document.getElementById('speed-slider');
if (speedEl) { speedMult = parseFloat(speedEl.value); speedEl.addEventListener('input', () => { speedMult = parseFloat(speedEl.value); }); }

renderer.domElement.style.touchAction = 'none';
renderer.domElement.addEventListener('pointerdown', (e) => {
  dragging = true; dragDist = 0;
  lastX = e.clientX; lastY = e.clientY;
  try { renderer.domElement.setPointerCapture(e.pointerId); } catch (err) { /* synthetic pointers */ }
});
renderer.domElement.addEventListener('pointermove', (e) => {
  if (!dragging) return;
  const dx = e.clientX - lastX, dy = e.clientY - lastY;
  dragDist += Math.abs(dx) + Math.abs(dy);
  lastX = e.clientX; lastY = e.clientY;
  yaw -= dx * 0.0042;
  pitch = Math.max(-0.7, Math.min(0.7, pitch - dy * 0.0032));
});
renderer.domElement.addEventListener('pointerup', (e) => {
  dragging = false;
  if (dragDist < 7) pick(e);   // it was a click/tap, not a drag
});
renderer.domElement.addEventListener('wheel', (e) => {
  e.preventDefault();
  walkVel += (e.deltaY > 0 ? -1 : 1) * 0.55;
}, { passive: false });

const keys = {};
window.addEventListener('keydown', (e) => { keys[e.key.toLowerCase()] = true; });
window.addEventListener('keyup', (e) => { keys[e.key.toLowerCase()] = false; });

function bindHold(id, dir) {
  const el = document.getElementById(id);
  const on = (e) => { e.preventDefault(); walkHold = dir; };
  const off = () => { walkHold = 0; };
  el.addEventListener('pointerdown', on);
  el.addEventListener('pointerup', off);
  el.addEventListener('pointerleave', off);
  el.addEventListener('pointercancel', off);
}
bindHold('walk-fwd', 1);
bindHold('walk-back', -1);

function clampPos() {
  camera.position.x = Math.max(X_MIN + 1, Math.min(X_MAX - 1, camera.position.x));
  camera.position.z = Math.max(-HALL_HALF_W + 0.9, Math.min(HALL_HALF_W - 0.9, camera.position.z));
}

/* wayfinding jumps */
let tween = null;
function goTo(targetX, targetYaw) {
  const sx = camera.position.x, sz = camera.position.z, sy = yaw;
  /* unwrap yaw to nearest equivalent so the turn is short */
  let ty = targetYaw;
  while (ty - sy > Math.PI) ty -= Math.PI * 2;
  while (sy - ty > Math.PI) ty += Math.PI * 2;
  const t0 = performance.now(), D = reduceMotion ? 1 : 1400;
  tween = (now) => {
    const k = Math.min(1, (now - t0) / D);
    const e = 1 - Math.pow(1 - k, 3);
    camera.position.x = sx + (targetX - sx) * e;
    camera.position.z = sz * (1 - e);
    yaw = sy + (ty - sy) * e;
    pitch *= (1 - e * 0.2);
    if (k >= 1) tween = null;
  };
}
document.querySelectorAll('[data-goto]').forEach((btn) => {
  btn.addEventListener('click', () => {
    const where = btn.dataset.goto;
    /* facing dir = (−sin yaw, −cos yaw): east (+x) needs yaw −π/2, west +π/2 */
    if (where === 'west') goTo(-LOBBY_HALF - 3, Math.PI / 2);
    else if (where === 'east') goTo(LOBBY_HALF + 3, -Math.PI / 2);
    else goTo(0, 0);
  });
});

/* jump directly in front of an artwork (used by dblclick) */
function jumpTo(tx, tz, targetYaw) {
  const sx = camera.position.x, sz = camera.position.z, sy = yaw;
  let ty = targetYaw;
  while (ty - sy > Math.PI) ty -= Math.PI * 2;
  while (sy - ty > Math.PI) ty += Math.PI * 2;
  const t0 = performance.now(), D = reduceMotion ? 1 : 860;
  tween = (now) => {
    const k = Math.min(1, (now - t0) / D);
    const e = 1 - Math.pow(1 - k, 3);
    camera.position.x = sx + (tx - sx) * e;
    camera.position.z = sz + (tz - sz) * e;
    yaw = sy + (ty - sy) * e;
    pitch *= (1 - e * 0.25);
    if (k >= 1) tween = null;
  };
}

/* double-click → jump in front of nearest artwork */
renderer.domElement.addEventListener('dblclick', (e) => {
  const r = renderer.domElement.getBoundingClientRect();
  const p = new THREE.Vector2(
    ((e.clientX - r.left) / r.width) * 2 - 1,
    -((e.clientY - r.top) / r.height) * 2 + 1);
  ray.setFromCamera(p, camera);
  const hit = ray.intersectObjects(clickable, false)[0];
  if (!hit) return;
  const gp = hit.object.parent.position;   // group world position
  /* stand 2.1 units in front of the wall, face it */
  const onSouth = gp.z > 0;
  const tz = onSouth ? gp.z - 2.1 : gp.z + 2.1;
  const ty = onSouth ? Math.PI : 0;
  jumpTo(gp.x, tz, ty);
});

/* hall chip + wayfind highlight */
const chip = document.getElementById('hall-chip');
const wayBtns = document.querySelectorAll('[data-goto]');
function updateZone() {
  const x = camera.position.x;
  const zone = x < -LOBBY_HALF ? 'west' : x > LOBBY_HALF ? 'east' : 'lobby';
  const label = zone === 'west' ? 'Fresh Eyes — Student Wing'
    : zone === 'east' ? 'Art Showcase — Crystal Lau' : 'The Lobby';
  if (chip.textContent !== label) {
    chip.textContent = label;
    wayBtns.forEach((b) => b.classList.toggle('here', b.dataset.goto === zone));
  }
}

/* ---------------- artwork picking ---------------- */
const ray = new THREE.Raycaster();
const card = document.getElementById('art-card');
function pick(e) {
  const r = renderer.domElement.getBoundingClientRect();
  const p = new THREE.Vector2(
    ((e.clientX - r.left) / r.width) * 2 - 1,
    -((e.clientY - r.top) / r.height) * 2 + 1);
  ray.setFromCamera(p, camera);
  const hit = ray.intersectObjects(clickable, false)[0];
  if (!hit) { card.classList.remove('open'); return; }
  const w = hit.object.userData.work;
  document.getElementById('ac-img').src = w.img;
  document.getElementById('ac-title').textContent = w.title;
  const who = document.getElementById('ac-who');
  const cta = document.getElementById('ac-cta');
  if (w.kind === 'teacher') {
    who.innerHTML = '<img src="img/ProfilePic.jpg" alt=""/><span style="font-size:14px;"><strong>Crystal Lau</strong> · Art Showcase</span>';
    cta.innerHTML = '<a class="btn primary" target="_blank" rel="noopener" href="' + ETSY +
      '">Buy on Etsy <span class="arr" aria-hidden="true">→</span></a>';
  } else {
    who.innerHTML = '<img src="' + w.img + '" alt=""/><span style="font-size:14px;"><strong>' +
      w.author + '</strong> · Fresh Eyes student wing</span>';
    cta.innerHTML = '<span class="hand-note">painted in Crystal’s class ✳</span>';
  }
  card.classList.add('open');
}
card.querySelector('.close').addEventListener('click', () => card.classList.remove('open'));

/* hint dismiss */
const hint = document.getElementById('hint');
document.getElementById('hint-ok').addEventListener('click', () => hint.remove());

/* ---------------- loop ---------------- */
const clock = new THREE.Clock();
function frame(now) {
  requestAnimationFrame(frame);
  const dt = Math.min(clock.getDelta(), 0.05);
  if (tween) tween(now);

  let move = walkHold;
  if (keys['w'] || keys['arrowup']) move = 1;
  if (keys['s'] || keys['arrowdown']) move = -1;
  if (keys['a'] || keys['arrowleft']) yaw += dt * 1.6;
  if (keys['d'] || keys['arrowright']) yaw -= dt * 1.6;

  walkVel += move * dt * 14 * speedMult;
  walkVel *= 0.88;                       // friction (slightly longer coast)
  if (Math.abs(walkVel) > 0.001 && !tween) {
    camera.position.x -= Math.sin(yaw) * walkVel * dt * 4;
    camera.position.z -= Math.cos(yaw) * walkVel * dt * 4;
    clampPos();
  }
  camera.rotation.set(0, 0, 0);
  camera.rotateY(yaw);
  camera.rotateX(pitch);
  /* gentle breathing bob while walking */
  camera.position.y = EYE + (reduceMotion ? 0 : Math.sin(now * 0.004) * Math.min(0.018, Math.abs(walkVel) * 0.02));

  updateZone();
  renderer.render(scene, camera);
}
requestAnimationFrame(frame);

window.addEventListener('resize', () => {
  renderer.setSize(stage.clientWidth, stage.clientHeight);
  camera.aspect = stage.clientWidth / stage.clientHeight;
  camera.updateProjectionMatrix();
});

/* ---------------- 2D fallback (stable row heights, mixed ratios) -------- */
function showFallback() {
  document.getElementById('stage').style.display = 'none';
  const fb = document.getElementById('fallback');
  fb.classList.add('show');
  const fill = (rootId, works) => {
    const root = document.getElementById(rootId);
    let row = null;
    works.forEach((w, i) => {
      if (i % 4 === 0) {
        row = document.createElement('div');
        row.className = 'fb-row';
        root.appendChild(row);
      }
      const d = document.createElement('div');
      d.className = 'work';
      d.style.flex = '1 1 0';
      d.innerHTML = '<img src="' + w.img + '" alt=""/><div class="scrim">' +
        w.title + ' · ' + w.author + '</div>';
      row.appendChild(d);
    });
  };
  fill('fb-east', eastWorks);
  fill('fb-west', westWorks);
}
