/* ============================================================
   Crystal's Art School — Wireframe variants (v3)
   - Real uploaded photos (img/*.jpg) replace SVG paintings
   - Fonts: Quicksand + Permanent Marker (brand) · Caveat only for annotations
   - 5 Front-Page waterfall-style variations
   - Annotations live in a separate overlay layer (no reflow)
   - Carousel slides enforced 1:1 cover, same layout
   - ProfilePic.jpg used for Crystal avatar
   - New: Sign Up, Log In, Pricing, Blog, About/Contact/T&C
   ============================================================ */

/* ---------- photo library ---------- */
/* All real user-supplied photos from /img. Each entry notes
   ideal use (portrait vs cover, etc). */
const PHOTOS = {
  profile:  "img/ProfilePic.jpg",
  banner:   "img/Bannerphoto.jpg",
  cover1:   "img/cover1.jpg",
  cover2:   "img/cover2.jpg",
  cover3:   "img/cover3.jpg",
  cover4:   "img/cover4.jpg",
  brushA:   "img/BrushA.jpg",
  brushB:   "img/BrushB.jpg",
  liquid:   "img/Liquidwatercolor.jpg",
  poster:   "img/Postercolor.jpg",
  rubber:   "img/Rubber.jpg",
  sketch:   "img/Sketchbook.jpg"
};
const COVERS = [PHOTOS.cover1, PHOTOS.cover2, PHOTOS.cover3, PHOTOS.cover4, PHOTOS.liquid, PHOTOS.poster, PHOTOS.sketch, PHOTOS.brushA];

const photo = (src, label, shape = "square") =>
  `<div class="photo ${shape}"><img src="${src}" alt=""/>${label ? `<div class="cap">${label}</div>` : ''}</div>`;

const avatar = (src = PHOTOS.profile, size = "") =>
  `<div class="avatar ${size}"><img src="${src}" alt=""/></div>`;

/* ---------- annotation overlay helper ---------- */
/* Each variant wraps the scroll content in position:relative, then
   overlays ONE .annot-layer holding all the floating notes.
   Notes are absolutely positioned (top/left/right/bottom in CSS px
   relative to the layer) — they float above content without reflow. */
function annotLayer(notes = []) {
  if (!notes.length) return '';
  return `<div class="annot-layer">${
    notes.map(n => `<div class="annot" data-dir="${n.dir || 'left-up'}" style="${n.pos}">${n.text}</div>`).join('')
  }</div>`;
}

/* ---------- shared blocks ---------- */
function buildNav() { return `
<nav class="wf-nav">
  <div class="row gap-sm" style="align-items:center;">
    <div class="site-avatar-sm"><img src="${PHOTOS.profile}" alt=""/></div>
    <div class="logo">Crystal's Art School</div>
  </div>
  <ul class="wf-nav-ul">
    <li class="nav-item"><span class="nav-parent">Home</span></li>
    <li class="nav-item">
      <span class="nav-parent">Courses <span class="nav-caret">▾</span></span>
      <span class="nav-children">Catalog · Lesson</span>
    </li>
    <li class="nav-item">
      <span class="nav-parent">Gallery <span class="nav-caret">▾</span></span>
      <span class="nav-children">Teacher · Student</span>
    </li>
    <li class="nav-item">
      <span class="nav-parent">Blog <span class="nav-caret">▾</span></span>
      <span class="nav-children">Menu · Post</span>
    </li>
    <li class="nav-item"><span class="nav-parent">About</span><span class="nav-children">+ Contact + T&amp;C</span></li>
  </ul>
  <div class="auth">
    <button class="cart-btn" type="button" aria-label="Shopping cart">🛒<span class="cart-badge">2</span></button>
    <span class="wf-btn ghost">Log in</span>
    <span class="wf-btn primary">Sign up</span>
  </div>
</nav>`; }
function buildNavMobile() { return `
<nav class="wf-nav" style="padding:6px 0;">
  <div class="row gap-sm" style="align-items:center;">
    <div class="site-avatar-sm"><img src="${PHOTOS.profile}" alt=""/></div>
    <div class="logo" style="font-size:14px;">Crystal's Art School</div>
  </div>
  <div class="row gap-sm" style="align-items:center;">
    <button class="cart-btn" type="button" aria-label="Shopping cart" style="width:30px;height:30px;font-size:13px;">🛒<span class="cart-badge" style="min-width:15px;height:15px;font-size:9px;">2</span></button>
    <div style="font-family:Quicksand,sans-serif;font-size:22px;">≡</div>
  </div>
</nav>`; }
// Proxy that re-evaluates when coerced to string, so the call-site ${NAV}
// (executed lazily inside variant functions) always sees current PHOTOS.
const NAV        = { toString: buildNav };
const NAV_MOBILE = { toString: buildNavMobile };

const FOOTER = `
<div class="section" style="border-bottom:none;padding-top:18px;">
  <div class="grid-4">
    <div><h4>About Me</h4><div class="ts-body">Crystal Lau · UK/HK · oils &amp; drawing.</div></div>
    <div><h4>Courses</h4><div class="ts-body">Oils · Acrylics · Drawing · Watercolour</div></div>
    <div><h4>Blog</h4><div class="ts-body">Studio notes · student showcases</div></div>
    <div><h4>Contact</h4><div class="ts-body">hello@crystalsartschool.com<br/>Discord · Instagram</div></div>
  </div>
</div>`;

/* ---------- lesson carousel (uniform 1:1) ---------- */
function LESSON_SLIDESHOW({title = "Featured lessons", kicker = "Lesson carousel", active = 2, stripeBorder = false, allNoBorder = false} = {}) {
  /* v0.10.24 — client locked palette to E · Ink + cream. Other combo variants removed. */
  const L = [
    { t: "Light & Shadow on the head", p: PHOTOS.cover1, combo: "e" },
    { t: "Blocking in large shapes",   p: PHOTOS.cover2, combo: "e" },
    { t: "Mixing greens from scratch", p: PHOTOS.cover3, combo: "e" },
    { t: "Loading a brush properly",   p: PHOTOS.cover4, combo: "e" },
    { t: "Warm vs cool greys",         p: PHOTOS.cover1, combo: "e" },
    { t: "Soft edges, clean paint",    p: PHOTOS.cover2, combo: "e" }
  ];
  const cart = `<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3.2l2.5 12.2a1.5 1.5 0 0 0 1.5 1.2h9a1.5 1.5 0 0 0 1.5-1.2L21.5 7H6.5"/></svg>`;
  return `
<div class="section">
  <div class="ts-meta">${kicker}</div>
  <h2 class="ts-h1" style="font-family:'Permanent Marker',cursive;font-size:26px;margin:2px 0 8px;">${title}</h2>
  <div class="slideshow">
    <div class="rail">
      ${L.map((l, i) => {
        const noBorder = allNoBorder || (stripeBorder && (i % 2 === 0));
        const cmpTag = (stripeBorder && !allNoBorder) ? `<div class="cmp-tag" aria-hidden="true">${noBorder ? 'no border' : 'with border'}</div>` : '';
        return `
        <div class="slide ${i === active ? 'active' : ''} ${noBorder ? 'no-border' : ''}" data-combo="${l.combo}">
          ${cmpTag}
          <div class="cover-wrap">
            ${photo(l.p, '1:1 cover')}
            <div class="cover-title">${l.t}</div>
            <div class="cover-actions">
              <button class="cover-btn primary" type="button">${cart}Add to cart</button>
              <button class="cover-btn secondary" type="button">View lesson <span class="arr" aria-hidden="true">→</span></button>
            </div>
          </div>
        </div>`;
      }).join('')}
    </div>
    <div class="arrows"><button class="arrow" type="button" data-dir="prev" aria-label="Previous lessons">‹</button><button class="arrow" type="button" data-dir="next" aria-label="Next lessons">›</button></div>
  </div>
</div>`;
}

const TIERS_BLOCK = `
<div class="section">
  <div class="ts-meta">Membership</div>
  <h2 style="font-family:'Permanent Marker',cursive;font-size:26px;margin:2px 0 10px;">Three ways to study</h2>
  <div class="grid-3">
    <div class="tier-card">
      <div class="tier-name">Wanderer</div>
      <div class="tier-price">Free</div>
      <div class="ts-body">Browse the blog and free intro lessons. Sign-up required to view.</div>
    </div>
    <div class="tier-card" style="background:var(--warm-white);">
      <div class="tier-name">Apprentice</div>
      <div class="tier-price">Per-course purchase</div>
      <div class="ts-body">Full video access, downloadable materials, resume from anywhere.</div>
    </div>
    <div class="tier-card" style="background:var(--mint);">
      <div class="tier-name">Fellow</div>
      <div class="tier-price">Per-course + Discord</div>
      <div class="ts-body">Everything in Apprentice, plus teacher Q&amp;A in a private Discord channel.</div>
    </div>
  </div>
</div>`;

function buildTestimonials() { return `
<div class="section">
  <div class="ts-meta">Students say</div>
  <h2 style="font-family:'Permanent Marker',cursive;font-size:26px;margin:2px 0 10px;">Real hands, real progress</h2>
  <div class="grid-3">
    ${[
      { n: "Alex W.",  q: "The block-in method finally made portraits click for me." },
      { n: "Priya M.", q: "Warm, patient teaching. Crystal's colour notes alone were worth the course." },
      { n: "Jun H.",   q: "Downloadable references saved me so much setup time." }
    ].map((s, i) => `
    <div class="card blush no-border">
      <div class="row gap-sm" style="align-items:center;margin-bottom:6px;">
        ${avatar(COVERS[(i + 4) % COVERS.length])}
        <div><strong>${s.n}</strong><div class="ts-meta" style="font-size:10px;">Student · 2025</div></div>
      </div>
      <div class="ts-body">"${s.q}"</div>
    </div>`).join('')}
  </div>
</div>`; }
const TESTIMONIALS = { toString: buildTestimonials };

function buildFreeLessonCta() { return `
<div class="section">
  <div class="card" style="display:flex;gap:18px;align-items:center;background:var(--forest);color:var(--cream);padding:18px;">
    <div style="width:140px;flex:none;">${photo(PHOTOS.cover2, 'free', 'square')}</div>
    <div class="grow">
      <div class="ts-meta" style="color:var(--mint);">Free intro</div>
      <h2 style="font-family:'Permanent Marker',cursive;margin:0 0 6px;font-size:26px;">Try a lesson, on me.</h2>
      <div class="ts-body" style="color:var(--cream);">One complete lesson — Materials &amp; setting up — free. Just make a free account.</div>
    </div>
    <div class="wf-btn primary big" style="background:var(--coral);flex:none;">Watch free lesson →</div>
  </div>
</div>`; }
function buildAboutShort() { return `
<div class="section">
  <div class="row gap-lg" style="align-items:flex-start;">
    <div style="flex:0 0 240px;">${photo(PHOTOS.profile, 'Crystal', 'portrait')}</div>
    <div class="grow">
      <div class="ts-meta">About</div>
      <h2 style="font-family:'Permanent Marker',cursive;font-size:30px;margin:4px 0 8px;">Hi, I'm Crystal.</h2>
      <div class="ts-body">I've taught small-group painting classes in Hong Kong for eight years. Online, I want to keep that same slow, careful rhythm: one lesson at a time, with real reference material, and room for questions.</div>
      <div class="ts-body" style="margin-top:8px;">Come paint with me.</div>
      <div class="row gap-sm" style="margin-top:12px;">
        <span class="wf-btn">Read my story</span>
        <span class="wf-btn ghost">Teaching approach</span>
      </div>
    </div>
  </div>
</div>`; }
const FREE_LESSON_CTA = { toString: buildFreeLessonCta };
const ABOUT_SHORT     = { toString: buildAboutShort };

/* ---------- reusable sidebar (used in all 5 waterfalls) ---------- */
function SIDEBAR_ABOUT() {
  return `
  <div class="card no-border" style="margin-bottom:12px;">
    ${photo(PHOTOS.profile, 'Crystal', 'square')}
    <h3 class="ts-h3" style="margin-top:8px;font-family:'Permanent Marker',cursive;">About Crystal</h3>
    <div class="ts-body">Oils &amp; drawing teacher. Small studio in Hong Kong.</div>
    <div class="wf-btn ghost" style="margin-top:6px;">Read more →</div>
  </div>`;
}
function SIDEBAR_FREE() {
  return `
  <div class="card blush no-border" style="margin-bottom:12px;">
    <h3 class="ts-h3">Try a lesson, free</h3>
    ${photo(PHOTOS.cover3, '', 'video')}
    <div class="wf-btn primary" style="margin-top:6px;">Watch →</div>
  </div>`;
}
function SIDEBAR_TIERS() {
  return `
  <div class="card no-border">
    <h3 class="ts-h3">Membership</h3>
    <div class="ts-body">
      <div><strong>Wanderer</strong> · Free</div>
      <div><strong>Apprentice</strong> · per course</div>
      <div><strong>Fellow</strong> · + Discord</div>
    </div>
    <div class="wf-btn" style="margin-top:6px;">Compare tiers</div>
  </div>`;
}

/* ---------- Course Reviews module (PRD P1 · rating + review system) ----------
   Full block for L1 / L2 sales pages: aggregate score, distribution bar, 3 sample
   reviews, "Leave review" CTA (logged-in users only), "All reviews" link. */
function COURSE_REVIEWS({ avg = 4.8, count = 42, dist = [32, 7, 2, 1, 0] } = {}) {
  const filled = (n) => '★★★★★'.slice(0, Math.round(n)) + '☆☆☆☆☆'.slice(0, 5 - Math.round(n));
  const max = Math.max(...dist);
  const samples = [
    { n: "Priya M.",    a: COVERS[4], r: 5, t: "Best portrait course I've done online", b: "Crystal's explanations of value structure finally made it click. The block-in chapter alone was worth the price.", d: "12 Apr 2026", helpful: 18 },
    { n: "Jordan K.",   a: COVERS[2], r: 5, t: "Patient, classical, no fluff",        b: "She doesn't rush. The skin-tone mixing video is the clearest I've ever seen — I rewatched it three times.", d: "08 Apr 2026", helpful: 11 },
    { n: "Mei H.",      a: COVERS[6], r: 4, t: "Loved it · wanted more on edges",     b: "Six lessons in and I'm already painting better than I was. The edges chapter felt a bit short though — would love an extension.", d: "02 Apr 2026", helpful: 7 }
  ];
  return `
  <div class="section" data-od-id="course-reviews">
    <div class="sp-between" style="align-items:baseline;margin-bottom:12px;">
      <div>
        <div class="ts-meta">STUDENT REVIEWS</div>
        <h2 style="font-family:'Permanent Marker',cursive;font-size:28px;margin:2px 0 0;">What students say</h2>
      </div>
      <span class="wf-btn">Leave a review</span>
    </div>

    <div class="row gap-lg" style="align-items:flex-start;">
      <!-- Aggregate score + distribution -->
      <div class="card no-border" style="flex:0 0 240px;padding:16px;text-align:center;">
        <div style="font-family:'Permanent Marker',cursive;font-size:48px;line-height:1;color:var(--ink);">${avg.toFixed(1)}</div>
        <div style="color:var(--coral,#F59595);font-size:18px;letter-spacing:2px;margin-top:4px;">${filled(avg)}</div>
        <div class="ts-meta" style="margin-top:6px;">Based on ${count} reviews</div>
        <div class="col" style="gap:4px;margin-top:14px;text-align:left;">
          ${dist.map((n, i) => `
          <div class="row gap-sm" style="align-items:center;font-size:11px;">
            <span class="ts-meta" style="width:22px;">${5 - i}★</span>
            <div style="flex:1;height:6px;background:var(--paper-2,#efe8d8);border-radius:3px;overflow:hidden;">
              <div style="width:${max ? (n / max) * 100 : 0}%;height:100%;background:var(--coral,#F59595);"></div>
            </div>
            <span class="ts-meta" style="width:24px;text-align:right;">${n}</span>
          </div>`).join('')}
        </div>
      </div>

      <!-- Sample reviews -->
      <div class="grow col" style="gap:12px;min-width:0;">
        ${samples.map(s => `
        <div class="card no-border" style="padding:14px;">
          <div class="row gap-sm" style="align-items:center;margin-bottom:6px;">
            ${avatar(s.a)}
            <div class="grow">
              <strong style="font-family:Quicksand,sans-serif;font-size:14px;">${s.n}</strong>
              <div class="ts-meta" style="font-size:10px;">${s.d} · verified purchase</div>
            </div>
            <div style="color:var(--coral,#F59595);font-size:13px;letter-spacing:1px;">${filled(s.r)}</div>
          </div>
          <div style="font-family:Quicksand,sans-serif;font-size:14px;font-weight:600;margin-bottom:4px;">${s.t}</div>
          <div class="ts-body" style="font-size:13px;line-height:1.5;">${s.b}</div>
          <div class="row gap-sm" style="margin-top:8px;align-items:center;">
            <span class="wf-btn ghost" style="font-size:11px;padding:3px 8px;">👍 Helpful · ${s.helpful}</span>
            <span class="wf-btn ghost" style="font-size:11px;padding:3px 8px;">Reply</span>
            <span class="ts-meta" style="font-size:10px;margin-left:auto;">Report</span>
          </div>
        </div>`).join('')}
        <div class="center" style="padding:6px;">
          <span class="wf-btn ghost">All ${count} reviews →</span>
        </div>
      </div>
    </div>
  </div>`;
}

/* ---------- Post-lesson rating prompt (PRD P1) ----------
   Compact inline block for L3 / L4 player. Asks for a quick 1-5 star + optional
   note after the lesson. Submit goes to per-lesson rating; aggregates roll up to
   the course-level COURSE_REVIEWS block. */
function POST_LESSON_RATING({ lesson = "this lesson" } = {}) {
  return `
  <div class="card no-border" data-od-id="post-lesson-rating" style="padding:14px;background:var(--cream,#FCFAED);">
    <div class="sp-between" style="align-items:center;flex-wrap:wrap;gap:8px;">
      <div>
        <strong style="font-family:'Permanent Marker',cursive;font-size:18px;">Was ${lesson} helpful?</strong>
        <div class="ts-meta" style="font-size:11px;">Quick rating · helps Crystal improve the course (~10 seconds).</div>
      </div>
      <div class="row gap-sm" style="align-items:center;">
        <div role="radiogroup" aria-label="Rate lesson" style="color:var(--coral,#F59595);font-size:24px;letter-spacing:4px;cursor:pointer;user-select:none;">
          <span aria-label="1 star">☆</span><span aria-label="2 stars">☆</span><span aria-label="3 stars">☆</span><span aria-label="4 stars">★</span><span aria-label="5 stars">★</span>
        </div>
        <span class="ts-meta" style="font-size:11px;">tap to rate</span>
      </div>
    </div>
    <div class="row gap-sm" style="margin-top:10px;align-items:stretch;">
      <div style="flex:1;border:1.5px solid var(--line-soft,#ddd);border-radius:8px;padding:8px 10px;background:var(--paper);font-size:12px;color:var(--muted);min-width:0;">Optional · one thing that worked or didn't (private to Crystal)</div>
      <span class="wf-btn primary" style="font-size:12px;padding:6px 14px;">Submit</span>
    </div>
  </div>`;
}

/* ---------- waterfall post generator ----------
   v0.10.0 redesign per client: image-on-top vertical tile so the right text column
   isn't squeezed inside a 2-col grid cell. Tag · date · short title · button.
   Button single-line, no arrow glyph (client request). */
function WFPosts(posts, { stripeBorder = false, allNoBorder = false } = {}) {
  return `<div class="waterfall">
    ${posts.map((p, i) => {
      const isLesson = p.kind === 'LESSON';
      const tag = isLesson ? 'LESSON POST' : 'BLOG POST';
      const tagCls = isLesson ? 'blog-tag lesson' : 'blog-tag';
      const cta = isLesson ? 'Watch lesson' : 'Read post';
      const noBorder = allNoBorder || (stripeBorder && (i % 2 === 0));
      const cmpTag = (stripeBorder && !allNoBorder) ? `<div class="cmp-tag" aria-hidden="true">${noBorder ? 'no border' : 'with border'}</div>` : '';
      return `
    <article class="wf-post tile compact ${noBorder ? 'no-border' : ''}" style="position:relative;">
      ${cmpTag}
      <div class="wf-thumb">${photo(p.src, isLesson ? '1:1 lesson' : '1:1 post', 'square')}</div>
      <div class="wf-body">
        <div class="ts-meta wf-tagrow">
          <span class="${tagCls}">${tag}</span>
          <span>${p.date || 'Apr 2026'}</span>
        </div>
        <h3 class="wf-title">${p.t}</h3>
        <div class="wf-cta">
          <span class="wf-btn ${isLesson ? 'primary' : ''}">${cta}</span>
          <span class="ts-meta">♡ ${p.hearts ?? 42}</span>
        </div>
      </div>
    </article>`;
    }).join('')}
    <div class="center" style="padding:10px;"><span class="wf-btn ghost">Load more ↓</span></div>
  </div>`;
}

const POST_SET = [
  { kind: "LESSON", src: COVERS[0], t: "How to block in a portrait — value first", date: "29 Apr 2026", hearts: 128, body: "The fastest way to make a portrait feel solid is to ignore features for the first hour. Big shapes, big values, nothing else." },
  { kind: "BLOG",   src: COVERS[1], t: "Why I keep a colour journal",              date: "24 Apr 2026", hearts: 42,  body: "A little notebook of mixes I've tried. Not for anyone else — just for me. Here's what goes in it." },
  { kind: "LESSON", src: COVERS[2], t: "Soft edges without muddying your paint",   date: "18 Apr 2026", hearts: 96,  body: "Edges make or break a painting. A cheap brush, a clean rag, and a little patience — that's all it takes." },
  { kind: "BLOG",   src: COVERS[3], t: "Student showcase — April 2026",            date: "11 Apr 2026", hearts: 71,  body: "A handful of pieces from students this month. I'm proud of all of them." },
  { kind: "LESSON", src: COVERS[4], t: "Mixing green from scratch",                date: "06 Apr 2026", hearts: 184, body: "Green tubes are a trap. Learn to mix greens from yellow, blue, and a bit of earth — your work will feel ten times more alive." },
  { kind: "LESSON", src: COVERS[1], t: "Loading a brush properly for watercolour", date: "29 Mar 2026", hearts: 53,  body: "Most beginners under-load. A brush should drink — not sip. Here's the loading rhythm I teach in studio." },
  { kind: "BLOG",   src: COVERS[2], t: "Why I paint slowly (and you should too)",  date: "22 Mar 2026", hearts: 38,  body: "Speed isn't the goal. Looking is. Slowing down by half doubled how much I noticed in every sitting." },
  { kind: "LESSON", src: COVERS[0], t: "Understanding warm vs cool greys",         date: "14 Mar 2026", hearts: 112, body: "Greys are never grey. Once you see them as warm or cool you stop reaching for the black tube." },
  { kind: "BLOG",   src: COVERS[4], t: "Three sketchbooks I actually finished",    date: "07 Mar 2026", hearts: 27,  body: "I have a graveyard of half-used books. Three that I finished cover-to-cover — and what they have in common." },
  { kind: "LESSON", src: COVERS[3], t: "Drawing hands without panic",              date: "28 Feb 2026", hearts: 156, body: "Hands aren't hard — they're just unfamiliar. A simple 4-shape construction makes them feel like any other form." },
  { kind: "BLOG",   src: COVERS[1], t: "A studio visit — Sai Kung, April",         date: "20 Feb 2026", hearts: 19,  body: "Spent the day painting at a friend's studio overlooking the bay. Notes, photos, and the colour I couldn't match." },
  { kind: "LESSON", src: COVERS[2], t: "The cheapest reference setup that works",  date: "12 Feb 2026", hearts: 64,  body: "You don't need a fancy iPad easel. A clip light, a phone stand, and a north window — this is my full setup." }
];

/* ============================================================ */
/*  FRONT PAGE — 5 waterfall variations                         */
/* ============================================================ */

/* F1 · Classic Waterfall — like Will Kemp, right sidebar + compact cinema banner.
   v0.9.4: banner stays full-width hero. Carousel moves BACK into the left .grow
   column (beside the sidebar) per client mark — sidebar is supposed to occupy
   the right space at carousel height, not below it. Sidebar squish prevented by
   minmax(0,1fr) on .slideshow .rail (see styles-v5.css). */
function F1_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}

  <!-- FULL-WIDTH HERO · F1 CRAFTI VANILLA-JS PORT (v0.10.31) ====================
       Source: client-supplied framework-free implementation (2026-05-25).
       Class-based markup .f1-hero / .f1-hero__slide drives CSS-only entrance
       (clip-path masked-wipe + skew + expo.out easing, plus power2.out fade-up).
       3 slides total, JS autoplay 6s loop, prev/next + counter live.
       Crystal token substitution: #FAF8F4→cream · Museo→Permanent Marker ·
       DM Sans→Quicksand · #BF5415→coral · #1F140B→ink. Site max-width 1440
       (Crafti was 1862) — % positions preserve composition.
       Phase 2 boundary clarified by impl: masked-wipe + carousel = NOW IN SCOPE
       (pure CSS, no GSAP). Still Phase 2: 16-tier 3D shared-camera parallax,
       split-text per-character rotation, GSAP power3/4 choreography across
       7 staggered layers. See PRD §Hero Banner Spec (v4). -->
  <section class="f1-hero" id="f1-hero">

    <!-- Two-tone cream background · Crafti Elementor 'sc_extra_bg_large_right' analog.
         Page tone = --cream (#FCFAED ≈ Crafti #FAF8F4). Right accent panel = --paper-2
         (#F3EFE3 ≈ Crafti #F3F0E7). Paper-texture noise via repeating-conic-gradient
         (no PNG dependency). z-index:1, sits behind swirl + photos. -->
    <div class="f1-hero__panel" aria-hidden="true"></div>

    <!-- Decorative swirl · inline SVG replaces Crafti's decorative-line.png (1920×6055).
         Stroke --ink @ opacity 0.12, scales with stage. z-index:2, behind photos/text. -->
    <svg class="f1-hero__swirl" viewBox="0 0 600 1200" aria-hidden="true" preserveAspectRatio="xMidYMid slice">
      <path d="M 60 40
               C 240 80, 380 200, 320 320
               S 60 460, 100 580
               C 160 720, 400 700, 420 820
               S 180 980, 220 1080
               C 260 1140, 460 1160, 540 1100"/>
      <path d="M 80 120
               C 200 180, 300 260, 240 360
               S 120 500, 180 600
               C 260 700, 360 760, 320 860
               S 140 1000, 200 1120"/>
      <path d="M 380 80
               C 420 200, 320 280, 380 380
               S 500 520, 460 640
               C 420 760, 540 820, 500 940
               S 380 1060, 420 1160"/>
    </svg>

    <!-- Brand mark · top-left, persists across slides -->
    <div class="f1-hero__brand">Crystal<span class="f1-hero__brand-dot">.</span></div>

    <!-- ===== SLIDE 1 ===== -->
    <article class="f1-hero__slide is-active" data-slide="1">
      <div class="f1-hero__text">
        <h2 class="f1-hero__title">Slow down.<br/>Draw with care.</h2>
        <p class="f1-hero__subtitle">Watercolour, acrylic &amp; drawing classes taught by Crystal Lau.</p>
        <a class="f1-hero__cta" href="#">View Collection</a>
      </div>
      <div class="f1-hero__media">
        <figure class="f1-hero__img f1-hero__img--large">
          <img src="${PHOTOS.cover1}" alt=""/>
        </figure>
        <figure class="f1-hero__img f1-hero__img--small">
          <img src="${PHOTOS.brushA}" alt=""/>
        </figure>
      </div>
    </article>

    <!-- ===== SLIDE 2 ===== -->
    <article class="f1-hero__slide" data-slide="2">
      <div class="f1-hero__text">
        <h2 class="f1-hero__title">Glorious<br/>watercolour.</h2>
        <p class="f1-hero__subtitle">Beginner to advanced — step by step, no rush.</p>
        <a class="f1-hero__cta" href="#">View Collection</a>
      </div>
      <div class="f1-hero__media">
        <figure class="f1-hero__img f1-hero__img--large">
          <img src="${PHOTOS.cover2}" alt=""/>
        </figure>
        <figure class="f1-hero__img f1-hero__img--small">
          <img src="${PHOTOS.liquid}" alt=""/>
        </figure>
      </div>
    </article>

    <!-- ===== SLIDE 3 ===== -->
    <article class="f1-hero__slide" data-slide="3">
      <div class="f1-hero__text">
        <h2 class="f1-hero__title">Built<br/>with care.</h2>
        <p class="f1-hero__subtitle">Real lessons from a working artist — not a content factory.</p>
        <a class="f1-hero__cta" href="#">View Collection</a>
      </div>
      <div class="f1-hero__media">
        <figure class="f1-hero__img f1-hero__img--large">
          <img src="${PHOTOS.cover3}" alt=""/>
        </figure>
        <figure class="f1-hero__img f1-hero__img--small">
          <img src="${PHOTOS.brushB}" alt=""/>
        </figure>
      </div>
    </article>

    <!-- Scroll Now affordance · bottom-left (persists across slides) -->
    <button class="f1-hero__scroll" type="button" aria-label="Scroll past banner">
      <span class="f1-hero__scroll-asterisk">✱</span>
      <span class="f1-hero__scroll-label">Scroll Now</span>
    </button>

    <!-- Arrows + counter · bottom-right (persists across slides) -->
    <nav class="f1-hero__nav" aria-label="Slider navigation">
      <button class="f1-hero__arrow f1-hero__arrow--prev" type="button" aria-label="Previous slide">
        <svg viewBox="0 0 64 12" aria-hidden="true">
          <path d="M0 6h62 M6 1 L1 6 L6 11" fill="none" stroke="currentColor" stroke-width="1"/>
        </svg>
      </button>
      <button class="f1-hero__arrow f1-hero__arrow--next" type="button" aria-label="Next slide">
        <svg viewBox="0 0 64 12" aria-hidden="true">
          <path d="M0 6h62 M58 1 L63 6 L58 11" fill="none" stroke="currentColor" stroke-width="1"/>
        </svg>
      </button>
      <div class="f1-hero__counter">
        <span class="f1-hero__counter-current">01</span> <span class="f1-hero__counter-total">/ 03</span>
      </div>
    </nav>

    <!-- Wireframe-only spec chip · top-left below brand -->
    <div class="f1-hero__chip" style="left:14px;top:90px;max-width:420px;">
      CRAFTI vanilla-JS port · aspect 1862/1009 · 2-image per slide<br/>
      large 41.7%w × 79%h right-anchored · small 24%w × 50%h overlap ⅓ + crosses seam<br/>
      CSS masked-wipe entrance · expo.out 1.6s · power2.out 1s fade-up<br/>
      3 slides · JS autoplay 6s · ← → counter live · CTA fixed<br/>
      <strong>v5 bg:</strong> two-tone cream — left --cream (#FCFAED) · right panel --paper-2 (#F3EFE3) · inline SVG swirl (no PNG)
    </div>
    <div class="f1-hero__chip" style="right:14px;top:50%;transform:translateY(-50%);writing-mode:vertical-rl;">
      ↑ accent panel · --paper-2 · Crafti 'sc_extra_bg_large_right' analog
    </div>
    <div class="f1-hero__chip f1-hero__chip--photo" style="left:10px;top:14%;">large · 41.7%w × 79%h · mouse-parallax target</div>
    <div class="f1-hero__chip f1-hero__chip--photo" style="right:35%;top:22%;">small · 24%w × 50%h · overlap ⅓ + left edge crosses cream/paper seam (v0.10.34)</div>
  </section>

  <!-- TWO-COLUMN ROW (left: carousel + posts · right: sidebar) ============== -->

  <div class="row gap-lg" style="align-items:flex-start;margin-top:2px;">
    <div class="grow" style="flex:2.2;min-width:0;">
      ${LESSON_SLIDESHOW({ title: "This week's lessons", kicker: "Featured carousel", allNoBorder: true })}
      ${WFPosts(POST_SET, { allNoBorder: true })}
    </div>
    <aside style="flex:1;min-width:0;position:sticky;top:14px;align-self:flex-start;">
      ${SIDEBAR_ABOUT()}
      ${SIDEBAR_FREE()}
      ${SIDEBAR_TIERS()}
    </aside>
  </div>
  ${TESTIMONIALS}
  ${FOOTER}

  ${annotLayer([
    { dir: "right-down", pos: "top:80px; left:20px;",   text: "CRAFTI vanilla-JS port (client-supplied framework-free impl, 2026-05-25). Stage aspect 1862/1009. 2 images per slide: LARGE BACK 41.7%w × 79%h right-anchored top:8.9% + SMALL FRONT 19.9%w × 43.4%h overlapping LARGE's LEFT-BOTTOM ⅓. Both plain rectangles · no radius / shadow / border. 3 slides (1→2→3 loop) — LIVE CAROUSEL: click ← → arrows to advance or wait 6s autoplay. Crystal token substitutions: cream/Permanent-Marker/Quicksand/coral/ink (no Museo, no DM Sans, no #BF5415). Site max-width 1440 (Crafti was 1862) — % positions preserve geometry. Phase 2 = client-editable slide content (auto-reflow on photo swap)." },
    { dir: "left-up",    pos: "top:80px; right:20px;",  text: "Per-slide entrance · CSS-ONLY · NO GSAP · NO SplitText. Large photo: clip-path masked-wipe + skew 3deg, expo.out 1.6s @ 310ms. Small photo: same recipe, 1.3s @ 610ms. Title / subtitle / CTA: power2.out fade-up 10px, 1s, stagger 1130/1240/1350ms. Slide transition: opacity fade 0.6s on .is-active toggle. Easings: cubic-bezier(.19,1,.22,1) = expo.out · cubic-bezier(.55,.06,.22,1) ≈ power2.out. Controller = ~30 lines vanilla JS. In Elementor build = single Custom CSS block + small Code widget (no paid lib, no Slider Revolution). Phase 2 still excluded: 16-tier shared-camera 3D parallax · per-character split-text rotation · GSAP power3/4 choreography across 7 staggered layers." },
    { dir: "right-up",   pos: "top:330px; left:20px;",  text: "Featured carousel · matches PRD §1 Home (Brand intro · featured courses · testimonials)" },
    { dir: "left-up",    pos: "top:330px; right:20px;", text: "Persistent sidebar — About / Free lesson / Tiers — sits BESIDE the carousel, scrolls independently · ~31% width (kept beyond PRD §1 baseline per Will Kemp pattern)" },
    { dir: "left-down",  pos: "top:620px; right:20px;", text: "Will-Kemp-style BLOG FEED below carousel · every tile a blog post (lesson-tagged OR studio-note) · supplementary to PRD §1 (matches baseline reference layout)" }
  ])}
</div>`;
}

function F1_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <!-- Mobile hero · SIMPLIFIED per PRD §Hero Banner Spec (Crafti pattern).
       Single photo + text + CTA. No mouse parallax (touch devices).
       Animation: lighter stagger ~0.9s · photo fadeInRight 0ms · text fadeInUp 200ms.
       See PRD §Hero Animation Spec for exact Elementor settings. -->
  <div class="section" style="padding:6px 0;">
    <div style="position:relative;aspect-ratio:5/4;overflow:hidden;border-radius:var(--radius-card);background:linear-gradient(180deg, var(--cream,#fdf6ee) 0%, var(--paper-2,#efe8d8) 100%);">
      <!-- single background photo · entrance fadeInRight 0ms 700ms -->
      <div data-entrance="fadeInRight" data-entrance-delay="0" data-entrance-duration="700"
           style="position:absolute;right:-12%;top:8%;width:62%;height:50%;border-radius:10px;overflow:hidden;box-shadow:0 10px 28px rgba(20,16,12,0.16);transform:rotate(2deg);">
        <img src="${PHOTOS.cover1}" alt="" style="width:100%;height:100%;object-fit:cover;display:block;"/>
      </div>
      <div data-entrance="fadeInUp" data-entrance-delay="200" data-entrance-duration="600"
           style="position:absolute;left:14px;right:14px;bottom:14px;">
        <div class="ts-meta" style="font-family:'JetBrains Mono',ui-monospace,monospace;font-size:9px;letter-spacing:0.16em;text-transform:uppercase;color:var(--forest,#3D6150);margin-bottom:4px;">Crystal Art School</div>
        <h1 style="font-family:'Permanent Marker',cursive;font-size:24px;line-height:1.0;margin:0 0 6px;color:var(--ink);">Slow down.<br/>Draw with care.</h1>
        <div class="ts-body" style="font-size:12px;margin-bottom:8px;color:var(--ink);max-width:78%;">Watercolour, acrylic & drawing classes online.</div>
        <span class="wf-btn primary" style="font-size:12px;padding:6px 12px;">Browse courses →</span>
      </div>
      <div style="position:absolute;right:8px;top:8px;font:9px/1.2 'JetBrains Mono',ui-monospace,monospace;color:rgba(20,16,12,0.7);background:rgba(255,255,255,0.85);padding:3px 6px;border-radius:3px;">mobile · simplified · no parallax</div>
    </div>
  </div>
  ${WFPosts(POST_SET.slice(0,3), { allNoBorder: true })}
</div>`;
}

/* F2–F5 variants removed per client v0.10.0 — only Classic Waterfall (F1) is in scope. */

/* ============================================================ */
/*  LESSON PAGE VARIANTS — keep existing 4                      */
/* ============================================================ */

function L1_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}
  <div class="ts-meta" style="margin:6px 0 10px;">Courses › Oils › <strong>Portrait Fundamentals</strong></div>
  <div class="section" style="padding-top:4px;">
    <div class="row gap-lg course-hero" style="align-items:flex-start;">
      <div class="course-hero-cover" style="flex:0 0 360px;">${photo(PHOTOS.cover1, '1:1 course cover')}</div>
      <div class="grow course-hero-info">
        <div class="ts-meta">Course · Oils · Beginner → Intermediate</div>
        <h1 style="font-family:'Permanent Marker',cursive;font-size:42px;line-height:1.02;margin:6px 0 10px;">Portrait Fundamentals</h1>
        <div class="ts-body">Twelve lessons covering everything I teach in-person: proportions, block-in, value structure, skin tones, edges, and features. Finish with a portrait you painted from start to finish.</div>
        <div class="row gap-md" style="margin-top:12px;">
          <span class="ts-meta">⏱ 6h 20m</span>
          <span class="ts-meta">🎞 12 lessons</span>
          <span class="ts-meta">⬇ materials included</span>
          <span class="ts-meta">★★★★☆ 4.8 (42)</span>
        </div>
        <div class="row gap-sm" style="margin-top:16px;">
          <span class="wf-btn primary big">Enroll — HK$680</span>
          <span class="wf-btn big">Watch free preview</span>
          <span class="wf-btn ghost big">+ Fellow / Discord · HK$780</span>
        </div>
      </div>
    </div>
  </div>

  ${LESSON_SLIDESHOW({ title: "Lessons in this course", kicker: "Syllabus preview" })}

  <div class="section">
    <div class="row gap-lg" style="align-items:flex-start;">
      <div class="grow">
        <h2 style="font-family:'Permanent Marker',cursive;font-size:26px;">Full syllabus</h2>
        <div class="col" style="margin-top:8px;">
          ${[
            { n: 1, t: "Materials & setting up",   d: "18 min", free: true },
            { n: 2, t: "Blocking in large shapes", d: "32 min" },
            { n: 3, t: "Value structure",          d: "28 min" },
            { n: 4, t: "Skin tone mixing",         d: "41 min" },
            { n: 5, t: "Edges — hard to lost",     d: "24 min" },
            { n: 6, t: "Features — eyes",          d: "36 min" }
          ].map(l => `
          <div class="card" style="display:grid;grid-template-columns:40px 1fr auto auto;gap:12px;align-items:center;">
            <div style="font-family:'Permanent Marker',cursive;font-size:22px;color:var(--teal-ink);">${String(l.n).padStart(2, '0')}</div>
            <div>
              <strong class="ts-h3">${l.t}</strong>
              <div class="ts-body" style="font-size:13px;">Short lesson description.</div>
            </div>
            <div class="ts-meta">${l.d}</div>
            ${l.free
              ? `<span class="wf-btn primary" style="padding:4px 10px;font-size:12px;">Free preview</span>`
              : `<span style="font-size:18px;color:var(--muted);">🔒</span>`}
          </div>`).join('')}
          <div class="center" style="padding:8px;"><span class="wf-btn ghost">Show all 12 lessons ↓</span></div>
        </div>
      </div>
      <aside style="flex:0 0 300px;position:sticky;top:14px;">
        <div class="card" style="border-width:2px;">
          ${photo(PHOTOS.cover1, 'preview')}
          <h3 class="ts-h3" style="margin-top:8px;">HK$680 lifetime</h3>
          <div class="ts-body" style="font-size:13px;">12 lessons · 6h 20m<br/>Materials included<br/>Resume anywhere</div>
          <div class="wf-btn primary" style="display:block;text-align:center;margin-top:8px;">Enroll now</div>
          <div class="wf-btn ghost" style="display:block;text-align:center;margin-top:6px;">Gift this course</div>
        </div>
      </aside>
    </div>
  </div>

  ${COURSE_REVIEWS({ avg: 4.8, count: 42, dist: [32, 7, 2, 1, 0] })}

  ${TESTIMONIALS}
  ${FOOTER}

  ${annotLayer([
    { dir: "left-down", pos: "top:80px; right:40px;",  text: "Course sales page · 1:1 cover per PRD" },
    { dir: "right-up",  pos: "top:380px; left:80px;",  text: "Carousel preview of syllabus — each slide opens the lesson if purchased" },
    { dir: "left-up",   pos: "top:560px; right:40px;", text: "Sticky enrol card · always visible while scrolling" },
    { dir: "right-up",  pos: "top:1180px; left:60px;", text: "PRD P1 · Course rating / review system. Aggregate score + 5-bar distribution + verified-purchase reviews + Leave-review CTA (logged-in only) + helpful/reply actions." }
  ])}
</div>`;
}

function L1_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="padding:6px 0;">
    ${photo(PHOTOS.cover1, '1:1 cover')}
    <div class="ts-meta" style="margin-top:8px;">Oils · Beginner</div>
    <h1 style="font-family:'Permanent Marker',cursive;font-size:26px;line-height:1.02;">Portrait Fundamentals</h1>
    <div class="ts-body">Twelve lessons — proportions, block-in, value, edges, features.</div>
    <div class="col" style="margin-top:10px;">
      <span class="wf-btn primary" style="text-align:center;">Enroll — HK$680</span>
      <span class="wf-btn" style="text-align:center;">Watch free preview</span>
    </div>
  </div>
</div>`;
}

function L2_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}
  <div class="ts-meta" style="margin:6px 0 10px;">Courses › <strong>Portrait Fundamentals</strong></div>
  <div class="section" style="padding-top:4px;">
    <div class="card dark" style="padding:16px;">
      <div class="row gap-lg" style="align-items:stretch;">
        <div style="flex:2;">${photo(PHOTOS.cover2, 'Free preview · big & playable', 'video')}</div>
        <div style="flex:1;display:flex;flex-direction:column;gap:10px;color:var(--cream);">
          <div class="ts-meta" style="color:var(--mint);">Course</div>
          <h1 style="font-family:'Permanent Marker',cursive;font-size:34px;line-height:1.02;margin:0;">Portrait Fundamentals</h1>
          <div class="ts-body" style="color:var(--cream);">Twelve lessons. Preview lesson 1 on the left — no signup needed.</div>
          <div style="background:var(--coral);color:var(--ink);padding:10px;border-radius:6px;">
            <strong style="font-family:Quicksand,sans-serif;font-size:20px;">HK$680</strong>
            <div class="ts-meta">Lifetime access · materials included</div>
          </div>
          <div class="wf-btn primary big">Enroll now →</div>
        </div>
      </div>
    </div>
  </div>
  ${LESSON_SLIDESHOW({ title: "Browse all 12 lessons", kicker: "Syllabus" })}

  ${COURSE_REVIEWS({ avg: 4.8, count: 42, dist: [32, 7, 2, 1, 0] })}

  ${TESTIMONIALS}
  ${FOOTER}

  ${annotLayer([
    { dir: "right-down", pos: "top:60px; left:30px;",   text: "Preview-led sales · big free preview video as the hero" },
    { dir: "left-up",    pos: "top:480px; right:40px;", text: "Click a carousel slide → it swaps into the hero preview" },
    { dir: "right-up",   pos: "top:900px; left:60px;",  text: "PRD P1 · Course rating / review system (same module as L1)." }
  ])}
</div>`;
}

function L2_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="padding:6px 0;">
    <div class="card dark">
      ${photo(PHOTOS.cover2, 'Free preview', 'video')}
      <h1 style="font-family:'Permanent Marker',cursive;font-size:24px;color:var(--cream);margin:6px 0;">Portrait Fundamentals</h1>
      <div class="wf-btn primary">Enroll — HK$680</div>
    </div>
  </div>
</div>`;
}

function L3_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}
  <div class="sp-between" style="margin:6px 0 12px;">
    <div class="ts-meta">Dashboard › Portrait Fundamentals › <strong>Lesson 02 · Blocking in</strong></div>
    <div class="ts-meta">Progress: 2/12 · 16%</div>
  </div>
  <div class="player-shell" style="padding:10px;">
    ${photo(PHOTOS.cover2, '▶ Lesson 02 · 32 min', 'video')}
    <div class="sp-between" style="color:var(--cream);margin-top:8px;font-family:Quicksand,sans-serif;font-size:13px;">
      <div class="row gap-sm"><span>▶</span><span>12:04 / 32:18</span></div>
      <div class="row gap-sm"><span>0.75×</span><span>1×</span><span>1.25×</span><span>⚙</span><span>⛶</span></div>
    </div>
    <div class="prog" style="margin-top:6px;"><span></span></div>
  </div>
  <div class="section">
    <div class="row gap-lg" style="align-items:flex-start;">
      <div class="grow">
        <h1 style="font-family:'Permanent Marker',cursive;font-size:32px;margin:0 0 4px;">Lesson 02 · Blocking in</h1>
        <div class="ts-meta" style="margin-bottom:10px;">Portrait Fundamentals · 32 min · Lesson 2 of 12</div>
        <div class="row gap-sm" style="margin-bottom:14px;">
          <span class="wf-btn">← Previous</span>
          <span class="wf-btn primary">Mark complete ✓</span>
          <span class="wf-btn">Next lesson →</span>
        </div>
        <div class="materials">
          <h4 class="ts-h3">Downloads for this lesson</h4>
          <ul>
            <li><span class="ts-body">📄 Reference photo — pose A</span><span class="dl">Download</span></li>
            <li><span class="ts-body">📄 Grid overlay template</span><span class="dl">Download</span></li>
            <li><span class="ts-body">📄 Worksheet — block-in steps</span><span class="dl">Download</span></li>
          </ul>
        </div>

        <div style="margin-top:14px;">
          ${POST_LESSON_RATING({ lesson: "Lesson 02" })}
        </div>
      </div>
      <aside style="flex:0 0 300px;">
        <div class="sp-between" style="margin-bottom:6px;">
          <strong class="ts-h3">Course lessons</strong><span class="ts-meta">2/12</span>
        </div>
        <div class="prog" style="margin-bottom:8px;"><span></span></div>
        <div class="lesson-rail">
          ${[
            { n: "01", t: "Materials & setting up",   d: "18:04", s: "✓" },
            { n: "02", t: "Blocking in",              d: "32:18", s: "▶", active: true },
            { n: "03", t: "Value structure",          d: "28:02" },
            { n: "04", t: "Skin tone mixing",         d: "41:11" },
            { n: "05", t: "Edges",                    d: "24:40" },
            { n: "06", t: "Eyes",                     d: "36:21" }
          ].map(l => `
          <div class="lesson-item ${l.active ? 'active' : ''}">
            <div class="lesson-thumb"></div>
            <div class="grow">
              <div class="ts-h3" style="font-size:13px;"><strong>${l.n} · ${l.t}</strong></div>
              <div class="dur">${l.d}${l.s ? ' · ' + l.s : ''}</div>
            </div>
          </div>`).join('')}
        </div>
      </aside>
    </div>
  </div>
  ${FOOTER}

  ${annotLayer([
    { dir: "left-down", pos: "top:80px; right:40px;",  text: "Cinema layout · big video, lesson rail to the right" },
    { dir: "right-up",  pos: "top:520px; left:40px;",  text: "Downloads section — same materials list pattern as PRD" },
    { dir: "right-up",  pos: "top:720px; left:40px;",  text: "PRD P1 · post-lesson rating prompt. 5-star tap + optional private note. Submits per-lesson; aggregates roll up to course-level reviews on L1/L2." }
  ])}
</div>`;
}

function L3_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="player-shell" style="padding:6px;margin-top:6px;">
    ${photo(PHOTOS.cover2, 'Lesson 02', 'video')}
    <div class="prog" style="margin-top:6px;"><span></span></div>
  </div>
  <div class="section" style="padding:10px 0;">
    <h1 style="font-family:'Permanent Marker',cursive;font-size:22px;">Blocking in</h1>
  </div>
</div>`;
}

function L4_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}
  <div class="sp-between" style="margin:6px 0 10px;">
    <div class="ts-meta">Portrait Fundamentals › <strong>Lesson 02 · Blocking in</strong></div>
    <div class="row gap-sm"><span class="chip active">Study desk</span><span class="chip">Cinema</span></div>
  </div>
  <div class="row gap-md" style="align-items:flex-start;">
    <aside style="flex:0 0 240px;">
      <div class="sp-between" style="margin-bottom:6px;">
        <strong class="ts-h3">Lessons</strong><span class="ts-meta">2/12</span>
      </div>
      <div class="prog" style="margin-bottom:8px;"><span></span></div>
      <div class="lesson-rail">
        ${[
          { n: "01", t: "Materials",    d: "18:04", s: "✓" },
          { n: "02", t: "Blocking in",  d: "32:18", s: "▶", active: true },
          { n: "03", t: "Value",        d: "28:02" },
          { n: "04", t: "Skin tones",   d: "41:11" },
          { n: "05", t: "Edges",        d: "24:40" }
        ].map(l => `
        <div class="lesson-item ${l.active ? 'active' : ''}">
          <div class="lesson-thumb"></div>
          <div class="grow">
            <div style="font-size:13px;"><strong>${l.n} · ${l.t}</strong></div>
            <div class="dur">${l.d}${l.s ? ' · ' + l.s : ''}</div>
          </div>
        </div>`).join('')}
      </div>
    </aside>
    <div class="grow">
      <div class="player-shell" style="padding:8px;">
        ${photo(PHOTOS.cover2, '▶ Lesson 02', 'video')}
        <div class="prog" style="margin-top:4px;"><span></span></div>
      </div>
      <h1 style="font-family:'Permanent Marker',cursive;font-size:26px;margin:12px 0 2px;">Lesson 02 · Blocking in</h1>
      <div class="ts-meta" style="margin-bottom:10px;">32 min · Lesson 2 of 12</div>
      <div class="card" style="background:var(--paper);border-style:solid;">
        <h4 class="ts-h3">About this lesson</h4>
        <div class="ts-body" style="margin-top:4px;">
          We lay the foundation of the whole painting here — the block-in.
          You'll see how I use 2B pencil to map the big shapes, how I
          measure proportions by eye, and when to commit vs. stay loose.
          No features, no details — just structure.
        </div>
        <ul class="ts-body" style="margin:8px 0 0;padding-left:18px;font-size:13px;">
          <li>Measuring proportions from life</li>
          <li>Why we block in values before colour</li>
          <li>How hard to press (and when to erase)</li>
        </ul>
      </div>

      <div style="margin-top:12px;">
        ${POST_LESSON_RATING({ lesson: "Lesson 02" })}
      </div>

      <!-- NOTE: 'My notes' feature removed per client feedback.
           Tutor LMS free tier doesn't ship it and the PRD keeps Discord
           as the only student-side interaction surface. -->
    </div>
    <aside style="flex:0 0 260px;">
      <div class="materials">
        <h4 class="ts-h3">Materials</h4>
        <ul>
          <li><span class="ts-body">📄 Reference A</span><span class="dl">Get</span></li>
          <li><span class="ts-body">📄 Reference B</span><span class="dl">Get</span></li>
          <li><span class="ts-body">📄 Grid template</span><span class="dl">Get</span></li>
        </ul>
      </div>
      <div class="card mint" style="margin-top:12px;">
        <h4 class="ts-h3">Fellow · Discord</h4>
        <div class="ts-body">Ask Crystal a question in #portraits-blocking.</div>
        <div class="wf-btn" style="margin-top:6px;">Open channel ↗</div>
      </div>
    </aside>
  </div>
  ${FOOTER}

  ${annotLayer([
    { dir: "down",      pos: "top:10px; left:260px;",   text: "Three-column Study Desk · lessons / video + notes / materials + Discord" },
    { dir: "left-up",   pos: "top:380px; right:280px;", text: "Timestamp-linked notes — click a timestamp to jump in the video" },
    { dir: "right-up",  pos: "top:620px; left:260px;",  text: "PRD P1 · post-lesson rating prompt (cream card). Same module as L3, rolls up to course-level reviews." }
  ])}
</div>`;
}

function L4_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="player-shell" style="padding:6px;margin-top:6px;">
    ${photo(PHOTOS.cover2, 'Lesson', 'video')}
  </div>
  <div class="section" style="padding:8px 0;">
    <h1 style="font-family:'Permanent Marker',cursive;font-size:22px;">02 · Blocking in</h1>
    <div class="row gap-sm" style="overflow:auto;margin-top:6px;">
      <span class="chip active">About</span><span class="chip">Materials</span><span class="chip">Discord</span>
    </div>
  </div>
</div>`;
}

/* ============================================================ */
/*  A1 · SIGN UP                                                */
/* ============================================================ */

function A1_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}
  <div class="row gap-lg" style="margin-top:20px;align-items:stretch;">
    <div style="flex:1;">${photo(PHOTOS.banner, '', 'portrait')}</div>
    <div style="flex:1.1;padding:10px 20px;">
      <div class="ts-meta">Create your free account</div>
      <h1 style="font-family:'Permanent Marker',cursive;font-size:38px;line-height:1.02;margin:6px 0 6px;">Join the school</h1>
      <div class="ts-body">Free sign-up. Browse blog and free lessons right away — course purchases are separate.</div>
      <div class="col" style="margin-top:16px;gap:10px;max-width:440px;">
        <div>
          <div class="ts-meta" style="margin-bottom:2px;">Name</div>
          <div style="border:2px solid var(--line);border-radius:8px;padding:10px 12px;background:var(--paper);">Crystal's Apprentice</div>
        </div>
        <div>
          <div class="ts-meta" style="margin-bottom:2px;">Email</div>
          <div style="border:2px solid var(--line);border-radius:8px;padding:10px 12px;background:var(--paper);">you@example.com</div>
        </div>
        <div>
          <div class="ts-meta" style="margin-bottom:2px;">Password</div>
          <div style="border:2px solid var(--line);border-radius:8px;padding:10px 12px;background:var(--paper);">••••••••</div>
        </div>
        <label class="row gap-sm" style="align-items:center;margin-top:6px;">
          <span style="width:18px;height:18px;border:2px solid var(--line);display:inline-block;"></span>
          <span class="ts-body" style="font-size:13px;">I agree to the Terms &amp; Privacy Policy</span>
        </label>
        <div class="wf-btn primary big" style="text-align:center;">Create account →</div>
        <div class="ts-meta" style="text-align:center;">or sign up with Google · Facebook</div>
        <div class="ts-body" style="font-size:13px;text-align:center;">Already have an account? <strong>Log in</strong></div>
      </div>
    </div>
  </div>
  ${FOOTER}

  ${annotLayer([
    { dir: "left-down", pos: "top:60px; right:30px;",  text: "Email verification required per PRD P0 · sends link after submit" },
    { dir: "right-up",  pos: "top:430px; left:60px;",  text: "T&C checkbox gates the Create button (GDPR requirement)" },
    { dir: "left-up",   pos: "top:500px; right:120px;",text: "Social sign-up optional · can be enabled per WP plugin" }
  ])}
</div>`;
}

function A1_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="padding:10px 0;">
    <h1 style="font-family:'Permanent Marker',cursive;font-size:26px;">Join the school</h1>
    <div class="col" style="margin-top:10px;gap:8px;">
      <div style="border:2px solid var(--line);border-radius:8px;padding:8px;background:var(--paper);">Email</div>
      <div style="border:2px solid var(--line);border-radius:8px;padding:8px;background:var(--paper);">Password</div>
      <div class="wf-btn primary" style="text-align:center;">Create account</div>
    </div>
  </div>
</div>`;
}

/* ============================================================ */
/*  A2 · LOG IN                                                 */
/* ============================================================ */

function A2_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}
  <div style="max-width:460px;margin:40px auto;">
    <div class="card" style="padding:26px;">
      <div style="text-align:center;">
        ${avatar(PHOTOS.profile, 'lg')}
        <h1 style="font-family:'Permanent Marker',cursive;font-size:32px;margin:10px 0 4px;">Welcome back</h1>
        <div class="ts-body">Pick up where you left off.</div>
      </div>
      <div class="col" style="margin-top:16px;gap:10px;">
        <div>
          <div class="ts-meta" style="margin-bottom:2px;">Email</div>
          <div style="border:2px solid var(--line);border-radius:8px;padding:10px 12px;background:var(--paper);">you@example.com</div>
        </div>
        <div>
          <div class="sp-between" style="margin-bottom:2px;">
            <span class="ts-meta">Password</span>
            <span class="ts-meta" style="text-decoration:underline;">Forgot?</span>
          </div>
          <div style="border:2px solid var(--line);border-radius:8px;padding:10px 12px;background:var(--paper);">••••••••</div>
        </div>
        <label class="row gap-sm" style="align-items:center;">
          <span style="width:18px;height:18px;border:2px solid var(--line);display:inline-block;background:var(--teal);"></span>
          <span class="ts-body" style="font-size:13px;">Remember me on this device</span>
        </label>
        <div class="wf-btn primary big" style="text-align:center;">Log in →</div>
        <div class="ts-meta" style="text-align:center;">— or —</div>
        <div class="wf-btn" style="text-align:center;">Continue with Google</div>
        <div class="ts-body" style="font-size:13px;text-align:center;margin-top:4px;">New here? <strong>Create an account</strong></div>
      </div>
    </div>
  </div>
  ${FOOTER}

  ${annotLayer([
    { dir: "right-down", pos: "top:80px; left:60px;", text: "Centered auth card · uses Crystal's profile photo as visual anchor" },
    { dir: "left-up",    pos: "top:260px; right:60px;", text: "Post-login lands on Dashboard (last-watched lesson resumes)" }
  ])}
</div>`;
}

function A2_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="text-align:center;padding:20px 0;">
    ${avatar(PHOTOS.profile, 'lg')}
    <h1 style="font-family:'Permanent Marker',cursive;font-size:24px;margin:8px 0;">Welcome back</h1>
    <div class="col" style="gap:8px;">
      <div style="border:2px solid var(--line);border-radius:8px;padding:8px;background:var(--paper);">Email</div>
      <div style="border:2px solid var(--line);border-radius:8px;padding:8px;background:var(--paper);">Password</div>
      <div class="wf-btn primary">Log in</div>
    </div>
  </div>
</div>`;
}

/* ============================================================ */
/*  A3 · PRICING / PLANS                                        */
/* ============================================================ */

function A3_desktop() {
  const plans = [
    { n: "Wanderer", p: "Free", b: "var(--paper)", f: ["Browse blog", "Free intro lessons", "Community posts (read)"], cta: "Sign up free", ctaStyle: "" },
    { n: "Apprentice", p: "Per course", sub: "from HK$480", b: "var(--warm-white)", f: ["Everything in Wanderer", "Full video access", "Downloadable materials", "Resume anywhere", "Lifetime access"], cta: "Browse courses", ctaStyle: "primary", highlight: true },
    { n: "Fellow", p: "Per course + Discord", sub: "+HK$100 per course", b: "var(--mint)", f: ["Everything in Apprentice", "Private Discord channel", "Direct Q&A with Crystal", "Monthly group review"], cta: "Buy Fellow course", ctaStyle: "" }
  ];
  return `<div class="wf" style="position:relative;">
  ${NAV}
  <div class="section" style="text-align:center;padding-top:16px;">
    <div class="ts-meta">Membership &amp; pricing</div>
    <h1 style="font-family:'Permanent Marker',cursive;font-size:42px;margin:6px 0;">Pay per course — nothing more.</h1>
    <div class="ts-body" style="max-width:560px;margin:6px auto 0;">No monthly subscriptions. Buy a course, keep it for life. Add Fellow to any course for private Discord access.</div>
  </div>

  <div class="section">
    <div class="grid-3" style="align-items:stretch;">
      ${plans.map(p => `
      <div class="tier-card" style="background:${p.b};position:relative;${p.highlight ? 'box-shadow:4px 6px 0 var(--line);' : ''}padding:20px;">
        ${p.highlight ? `<div style="position:absolute;top:-14px;right:14px;background:var(--coral);border:2px solid var(--line);border-radius:6px;padding:2px 10px;font-family:'Permanent Marker',cursive;font-size:14px;">most picked</div>` : ''}
        <div class="tier-name" style="font-size:24px;">${p.n}</div>
        <div class="tier-price" style="font-size:16px;">${p.p}</div>
        ${p.sub ? `<div class="ts-meta">${p.sub}</div>` : ''}
        <ul style="margin-top:12px;list-style:none;padding:0;">
          ${p.f.map(x => `<li style="padding:6px 0;border-bottom:1px dashed var(--line-soft);font-size:13px;">✓ ${x}</li>`).join('')}
        </ul>
        <div class="wf-btn ${p.ctaStyle} big" style="display:block;text-align:center;margin-top:14px;">${p.cta}</div>
      </div>`).join('')}
    </div>
  </div>

  <div class="section">
    <h2 style="font-family:'Permanent Marker',cursive;font-size:26px;">Common questions</h2>
    <div class="grid-2" style="margin-top:10px;">
      ${[
        ["How long do I keep access?", "Lifetime access to any course you buy. Watch as many times as you want."],
        ["Can I refund a course?", "No refunds (per PRD). Preview the first lesson free before you buy."],
        ["What currencies?", "HKD is the base. Stripe displays your local currency automatically."],
        ["How does Discord work?", "Fellow courses unlock a private channel. You get an invite code on your dashboard after purchase."]
      ].map(([q, a]) => `
      <div class="card soft"><strong class="ts-h3">${q}</strong><div class="ts-body" style="font-size:13px;margin-top:4px;">${a}</div></div>`).join('')}
    </div>
  </div>
  ${FOOTER}

  ${annotLayer([
    { dir: "down",       pos: "top:140px; left:50%;",    text: "3 tier cards · Apprentice highlighted as 'most picked'" },
    { dir: "left-up",    pos: "top:360px; right:60px;",  text: "'+HK$100 per course' = Fellow premium (price TBD per PRD)" },
    { dir: "right-up",   pos: "top:620px; left:60px;",   text: "FAQ sits directly below — common purchase questions" }
  ])}
</div>`;
}

function A3_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="text-align:center;padding:10px 0;">
    <h1 style="font-family:'Permanent Marker',cursive;font-size:24px;">Pay per course</h1>
  </div>
  <div class="col">
    <div class="tier-card"><div class="tier-name">Wanderer</div><div class="tier-price">Free</div></div>
    <div class="tier-card" style="background:var(--warm-white);"><div class="tier-name">Apprentice</div><div class="tier-price">Per course</div></div>
    <div class="tier-card" style="background:var(--mint);"><div class="tier-name">Fellow</div><div class="tier-price">+ Discord</div></div>
  </div>
</div>`;
}

/* ============================================================ */
/*  A4 · BLOG INDEX (full blog menu / archive / categories)     */
/* ============================================================ */

function A4_desktop() {
  const featured = POST_SET[0];
  const categories = [
    { n: "Studio notes",  c: 18, active: true },
    { n: "Lesson tips",   c: 24 },
    { n: "Student showcases", c: 9 },
    { n: "Materials & tools", c: 7 },
    { n: "Behind the scenes", c: 5 }
  ];
  return `<div class="wf" style="position:relative;">
  ${NAV}

  <!-- BLOG MASTHEAD -->
  <div class="section" style="padding-top:10px;">
    <div class="sp-between" style="align-items:flex-end;flex-wrap:wrap;gap:10px;">
      <div>
        <div class="ts-meta">Blog · the studio journal</div>
        <h1 style="font-family:'Permanent Marker',cursive;font-size:44px;margin:4px 0 0;">The Journal</h1>
        <div class="ts-body" style="max-width:520px;">Weekly posts from the studio — lesson notes, student showcases, materials, and the occasional ramble. Free to read for everyone; comments for signed-in students.</div>
      </div>
      <div class="row gap-sm" style="align-items:center;">
        <div style="border:2px solid var(--line);border-radius:999px;padding:6px 14px;background:var(--paper);font-family:Quicksand,sans-serif;font-size:13px;">🔍 Search posts…</div>
        <span class="wf-btn">RSS ↗</span>
      </div>
    </div>
  </div>

  <div class="section">
    <div class="row gap-lg" style="align-items:flex-start;">
      <!-- LEFT: blog menu / category nav -->
      <aside style="flex:0 0 240px;position:sticky;top:14px;">
        <div class="card" style="padding:12px;">
          <div class="ts-meta" style="margin-bottom:6px;">Categories</div>
          <ul style="list-style:none;padding:0;margin:0;">
            ${categories.map(cat => `
            <li style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;border-radius:6px;${cat.active ? 'background:var(--teal);font-weight:700;' : ''}">
              <span>${cat.n}</span>
              <span class="ts-meta">${cat.c}</span>
            </li>`).join('')}
          </ul>
        </div>

        <div class="card" style="padding:12px;margin-top:12px;">
          <div class="ts-meta" style="margin-bottom:6px;">Archive</div>
          <div class="ts-body" style="font-size:13px;">
            <div>▸ Apr 2026 <span class="ts-meta">(4)</span></div>
            <div>▸ Mar 2026 <span class="ts-meta">(5)</span></div>
            <div>▸ Feb 2026 <span class="ts-meta">(3)</span></div>
            <div>▸ Jan 2026 <span class="ts-meta">(6)</span></div>
            <div style="margin-top:6px;text-decoration:underline;">See all months →</div>
          </div>
        </div>

        <div class="card" style="padding:12px;margin-top:12px;">
          <div class="ts-meta" style="margin-bottom:6px;">Tags</div>
          <div class="row gap-sm" style="flex-wrap:wrap;">
            ${["oils","portraits","greens","edges","warm-ups","still-life","watercolour","skin-tones"].map(t => `<span class="chip" style="font-size:11px;">#${t}</span>`).join('')}
          </div>
        </div>

        <div class="card mint" style="padding:12px;margin-top:12px;">
          <div class="ts-meta">Heads up</div>
          <div class="ts-body" style="font-size:13px;">No newsletter — check back weekly or follow on Instagram.</div>
        </div>
      </aside>

      <!-- RIGHT: posts list -->
      <div class="grow">
        <!-- FEATURED post -->
        <article class="card" style="padding:0;overflow:hidden;margin-bottom:16px;">
          ${photo(featured.src, '', 'video')}
          <div style="padding:14px 16px;">
            <div class="ts-meta" style="display:flex;align-items:center;gap:6px;">
              <span class="blog-tag">FEATURED</span>
              <span>Studio notes · Apr 17, 2026 · 6 min read</span>
            </div>
            <h2 style="font-family:'Permanent Marker',cursive;font-size:30px;margin:6px 0 6px;">${featured.t}</h2>
            <div class="ts-body">${featured.body}</div>
            <div class="row gap-sm" style="margin-top:10px;">
              <span class="wf-btn primary">Continue reading →</span>
              <span class="ts-meta">♡ 128 · 24 comments</span>
            </div>
          </div>
        </article>

        <!-- LATEST list (3-col) -->
        <div class="ts-meta">Latest posts</div>
        <h3 style="font-family:'Permanent Marker',cursive;font-size:22px;margin:4px 0 10px;">From the last few weeks</h3>
        <div class="grid-3" style="align-items:stretch;">
          ${POST_SET.slice(1).concat(POST_SET.slice(0, 3)).map(p => {
            const tag = p.kind === 'LESSON' ? 'LESSON TIP' : 'STUDIO NOTE';
            const tagCls = p.kind === 'LESSON' ? 'blog-tag lesson' : 'blog-tag';
            return `
          <article class="wf-post tile" style="padding:0;overflow:hidden;">
            ${photo(p.src, '', 'square')}
            <div class="tile-body">
              <div class="ts-meta" style="display:flex;align-items:center;gap:4px;">
                <span class="${tagCls}">${tag}</span>
                <span>Apr 2026</span>
              </div>
              <h3 style="font-family:'Permanent Marker',cursive;font-size:18px;margin:6px 0;">${p.t}</h3>
              <div class="ts-body" style="font-size:13px;">${p.body.slice(0, 90)}…</div>
              <div class="tile-cta"><span class="wf-btn">Read →</span><span class="ts-meta">♡ 42 · 12</span></div>
            </div>
          </article>`;
          }).join('')}
        </div>

        <div class="center" style="padding:14px;">
          <span class="wf-btn ghost">Load more posts ↓</span>
        </div>

        <!-- Pagination / "all posts by" summary -->
        <div class="card cream" style="margin-top:14px;padding:14px;">
          <div class="ts-meta">All posts</div>
          <div class="ts-body" style="font-size:13px;margin-top:4px;">
            <strong>62 posts</strong> · <strong>5 categories</strong> · <strong>14 months</strong>. Sort:
            <span style="text-decoration:underline;">Newest</span> ·
            <span style="text-decoration:underline;">Most read</span> ·
            <span style="text-decoration:underline;">A-Z</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  ${FOOTER}

  ${annotLayer([
    { dir: "right-down", pos: "top:40px; left:30px;",    text: "Full Blog MENU — category list + archive + tag cloud live in the left rail. No newsletter per PRD." },
    { dir: "left-up",    pos: "top:220px; right:40px;",  text: "Single featured post at top · then 3-col grid of latest posts" },
    { dir: "right-up",   pos: "top:680px; left:260px;",  text: "The individual post page is a SEPARATE route (not shown here) — scroll to A4 · variant caption for the post-detail sketch" }
  ])}
</div>`;
}

function A4_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="padding:8px 0;">
    <h1 style="font-family:'Permanent Marker',cursive;font-size:26px;">The Journal</h1>
    <div style="border:2px solid var(--line);border-radius:999px;padding:6px 12px;background:var(--paper);font-size:13px;margin-top:6px;">🔍 Search posts…</div>
    <div class="row gap-sm" style="overflow:auto;margin-top:8px;">
      <span class="chip active">All</span><span class="chip">Studio notes</span><span class="chip">Lesson tips</span><span class="chip">Showcases</span>
    </div>
  </div>
  ${WFPosts(POST_SET.slice(0, 3))}
</div>`;
}

/* ============================================================ */
/*  A5 · ABOUT / CONTACT / T&C                                  */
/* ============================================================ */

function A5_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}
  <!-- ABOUT -->
  <div class="section" style="padding:10px 0;">
    <div class="row gap-lg">
      <div style="flex:0 0 340px;">${photo(PHOTOS.profile, 'Crystal Lau', 'portrait')}</div>
      <div class="grow">
        <div class="ts-meta">About</div>
        <h1 style="font-family:'Permanent Marker',cursive;font-size:40px;margin:4px 0 10px;">Hi, I'm Crystal.</h1>
        <div class="ts-body">Eight years ago I started teaching painting out of a small studio in Hong Kong. Oils, mostly — but also acrylics, watercolour, and drawing. I believe good painting comes from looking carefully, mixing patiently, and being kind to yourself while you learn.</div>
        <div class="ts-body" style="margin-top:8px;">This site is the online version of the same classroom. Same pace, same care.</div>
        <div class="row gap-sm" style="margin-top:14px;">
          <span class="wf-btn primary">See my courses</span>
          <span class="wf-btn">Read my teaching approach</span>
        </div>
      </div>
    </div>
  </div>

  <!-- CONTACT -->
  <div class="section">
    <div class="row gap-lg" style="align-items:flex-start;">
      <div class="grow">
        <div class="ts-meta">Contact</div>
        <h2 style="font-family:'Permanent Marker',cursive;font-size:28px;">Say hello</h2>
        <div class="ts-body">Questions about a course, a gift, or just a nice note. I read every message.</div>
        <div class="col" style="max-width:480px;margin-top:12px;gap:10px;">
          <div style="border:2px solid var(--line);border-radius:8px;padding:10px 12px;background:var(--paper);">Your name</div>
          <div style="border:2px solid var(--line);border-radius:8px;padding:10px 12px;background:var(--paper);">Email</div>
          <div style="border:2px solid var(--line);border-radius:8px;padding:10px 12px;background:var(--paper);min-height:100px;">Your message</div>
          <div class="wf-btn primary" style="text-align:center;">Send →</div>
        </div>
      </div>
      <aside style="flex:0 0 300px;">
        <div class="card cream">
          <h4 class="ts-h3">Faster ways</h4>
          <div class="ts-body" style="font-size:13px;margin-top:4px;">
            <div><strong>Email</strong> · hello@crystalsartschool.com</div>
            <div style="margin-top:4px;"><strong>Instagram</strong> · @crystalauart</div>
            <div style="margin-top:4px;"><strong>Discord</strong> · Fellow students only</div>
          </div>
        </div>
        <div class="card" style="margin-top:12px;background:var(--mint);">
          <h4 class="ts-h3">FAQ</h4>
          <div class="ts-body" style="font-size:13px;">
            <div>→ How long do I keep a course?</div>
            <div>→ Do you offer refunds?</div>
            <div>→ What materials do I need?</div>
            <div>→ Is this suitable for beginners?</div>
          </div>
          <div class="wf-btn" style="margin-top:6px;">See all FAQ</div>
        </div>
      </aside>
    </div>
  </div>

  <!-- T&C anchor -->
  <div class="section">
    <div class="ts-meta">Legal</div>
    <h2 style="font-family:'Permanent Marker',cursive;font-size:28px;">Terms &amp; Privacy</h2>
    <div class="grid-2" style="margin-top:10px;">
      <div class="card soft">
        <h4 class="ts-h3">Terms of Service</h4>
        <div class="ts-body" style="font-size:13px;">Account rules, course access duration, video protection, no refunds. Plain-language summary at the top, full text below.</div>
        <div class="wf-btn ghost" style="margin-top:6px;">Read terms →</div>
      </div>
      <div class="card soft">
        <h4 class="ts-h3">Privacy Policy (GDPR)</h4>
        <div class="ts-body" style="font-size:13px;">What we collect, how we use it, cookie consent (GDPR required per PRD). Account-deletion request link at the bottom.</div>
        <div class="wf-btn ghost" style="margin-top:6px;">Read privacy →</div>
      </div>
    </div>
    <div class="card" style="margin-top:12px;background:var(--warm-white);border-style:dashed;">
      <div class="row gap-sm" style="align-items:center;">
        <span style="font-size:22px;">🍪</span>
        <div class="grow"><strong class="ts-h3">Cookie consent</strong><div class="ts-body" style="font-size:13px;">GDPR banner appears on first visit. "Accept all", "Reject non-essential", "Customise".</div></div>
        <span class="wf-btn">Accept</span>
        <span class="wf-btn ghost">Reject</span>
      </div>
    </div>
  </div>
  ${FOOTER}

  ${annotLayer([
    { dir: "right-down", pos: "top:40px; left:60px;",    text: "About · Crystal's profile photo anchors the page" },
    { dir: "left-up",    pos: "top:440px; right:60px;",  text: "Contact form + sidecar with direct channels + short FAQ" },
    { dir: "right-up",   pos: "top:800px; left:40px;",   text: "T&C / Privacy live here too (linked from footer) · cookie banner pattern shown" }
  ])}
</div>`;
}

function A5_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="padding:8px 0;text-align:center;">
    ${photo(PHOTOS.profile, '', 'portrait')}
    <h1 style="font-family:'Permanent Marker',cursive;font-size:24px;margin-top:8px;">Hi, I'm Crystal.</h1>
  </div>
</div>`;
}

/* ============================================================ */
/*  A6 · ACCOUNT DETAILS (dashboard · settings)                 */
/*  Post-login account page per PRD Dashboard requirements:     */
/*  progress / resume / purchase history / account settings.    */
/* ============================================================ */

function A6_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}

  <div class="sp-between" style="margin:6px 0 10px;align-items:flex-end;">
    <div>
      <div class="ts-meta">Signed in · Dashboard</div>
      <h1 style="font-family:'Permanent Marker',cursive;font-size:34px;margin:4px 0 0;">My account</h1>
    </div>
    <div class="row gap-sm" style="align-items:center;">
      ${avatar(PHOTOS.profile)}
      <div class="ts-body" style="font-size:13px;"><strong>Alex W.</strong><br/><span class="ts-meta">Apprentice · joined Mar 2026</span></div>
      <span class="wf-btn ghost" style="margin-left:8px;">Log out</span>
    </div>
  </div>

  <div class="acct-grid">
    <!-- LEFT nav -->
    <nav class="acct-nav">
      <ul>
        <li>Overview</li>
        <li class="active">Account details</li>
        <li>My courses</li>
        <li>Purchase history</li>
        <li>Email & notifications</li>
        <li>Discord access</li>
        <li>Privacy & data</li>
        <li style="color:var(--coral);">Delete account</li>
      </ul>
    </nav>

    <!-- RIGHT content -->
    <div>
      <!-- Profile section -->
      <div class="card" style="padding:18px;margin-bottom:14px;">
        <div class="sp-between" style="margin-bottom:10px;">
          <h2 class="ts-h3" style="font-size:18px;">Profile</h2>
          <span class="wf-btn ghost" style="font-size:12px;">Edit</span>
        </div>
        <div class="row gap-lg" style="align-items:flex-start;">
          <div style="flex:0 0 110px;">${photo(COVERS[0], '', 'square')}<div class="wf-btn" style="margin-top:6px;font-size:11px;text-align:center;">Change photo</div></div>
          <div class="grow" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
              <div class="ts-meta">Display name</div>
              <div style="border:2px solid var(--line);border-radius:8px;padding:8px 10px;background:var(--paper);">Alex W.</div>
            </div>
            <div>
              <div class="ts-meta">Username</div>
              <div style="border:2px solid var(--line);border-radius:8px;padding:8px 10px;background:var(--paper);">@alex-paints</div>
            </div>
            <div>
              <div class="ts-meta">Email</div>
              <div style="border:2px solid var(--line);border-radius:8px;padding:8px 10px;background:var(--paper);">alex@example.com <span class="ts-meta" style="color:var(--forest);">✓ verified</span></div>
            </div>
            <div>
              <div class="ts-meta">Country</div>
              <div style="border:2px solid var(--line);border-radius:8px;padding:8px 10px;background:var(--paper);">United Kingdom ▾</div>
            </div>
            <div style="grid-column:span 2;">
              <div class="ts-meta">Short bio (optional — shows on comments)</div>
              <div style="border:2px solid var(--line);border-radius:8px;padding:8px 10px;background:var(--paper);min-height:60px;">Hobbyist painter from Bristol. Loves portraits.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Password & security -->
      <div class="card" style="padding:18px;margin-bottom:14px;">
        <h2 class="ts-h3" style="font-size:18px;margin-bottom:8px;">Password &amp; security</h2>
        <div class="row gap-md" style="align-items:center;">
          <div class="grow">
            <div class="ts-meta">Password</div>
            <div class="ts-body" style="font-size:13px;">Last changed 42 days ago</div>
          </div>
          <span class="wf-btn">Change password</span>
        </div>
        <div class="row gap-md" style="align-items:center;margin-top:10px;padding-top:10px;border-top:1px dashed var(--line-soft);">
          <div class="grow">
            <div class="ts-meta">Two-factor authentication</div>
            <div class="ts-body" style="font-size:13px;">Not set up · recommended</div>
          </div>
          <span class="wf-btn primary">Enable 2FA</span>
        </div>
      </div>

      <!-- Billing -->
      <div class="card" style="padding:18px;margin-bottom:14px;background:var(--paper);">
        <h2 class="ts-h3" style="font-size:18px;margin-bottom:8px;">Billing &amp; payment</h2>
        <div class="ts-body" style="font-size:13px;">Payment methods are managed at checkout through Stripe. No card is stored on this site.</div>
        <div class="row gap-sm" style="margin-top:10px;">
          <span class="wf-btn">See purchase history →</span>
          <span class="wf-btn ghost">Download receipts</span>
        </div>
      </div>

      <!-- Communication prefs -->
      <div class="card" style="padding:18px;margin-bottom:14px;">
        <h2 class="ts-h3" style="font-size:18px;margin-bottom:8px;">Email &amp; notifications</h2>
        <div class="ts-body" style="font-size:13px;margin-bottom:8px;color:var(--muted);">Per PRD: no newsletter. These are transactional-only.</div>
        ${[
          ["Receipts &amp; course access emails", true, true],
          ["Reply notifications on my blog comments", true, false],
          ["New lesson added to a course I own", true, false],
          ["Marketing / announcements", false, false]
        ].map(([label, on, locked]) => `
        <div class="sp-between" style="padding:8px 0;border-bottom:1px dashed var(--line-soft);">
          <div class="ts-body" style="font-size:13px;">${label}${locked ? ` <span class="ts-meta" style="color:var(--coral);">required</span>` : ''}</div>
          <div style="width:36px;height:20px;border:2px solid var(--line);border-radius:999px;background:${on ? 'var(--teal)' : 'var(--paper-2)'};position:relative;">
            <div style="position:absolute;${on ? 'right:2px' : 'left:2px'};top:50%;transform:translateY(-50%);width:14px;height:14px;background:var(--paper);border:2px solid var(--line);border-radius:50%;"></div>
          </div>
        </div>`).join('')}
      </div>

      <!-- Privacy / delete -->
      <div class="card" style="padding:18px;background:var(--warm-white);border-style:dashed;">
        <h2 class="ts-h3" style="font-size:18px;margin-bottom:8px;">Privacy &amp; data (GDPR)</h2>
        <div class="row gap-sm" style="margin-top:6px;flex-wrap:wrap;">
          <span class="wf-btn">Download my data</span>
          <span class="wf-btn ghost">Manage cookie consent</span>
          <span class="wf-btn" style="background:var(--coral);border-color:var(--line);">Delete my account</span>
        </div>
        <div class="ts-body" style="font-size:12px;margin-top:8px;color:var(--muted);">Deleting removes your login and comments. Course purchases become anonymised. This cannot be undone.</div>
      </div>
    </div>
  </div>

  ${FOOTER}

  ${annotLayer([
    { dir: "right-down", pos: "top:60px; left:30px;",    text: "A4 · Account Details · lives under Dashboard. Left nav = sections from PRD dashboard reqs." },
    { dir: "left-up",    pos: "top:360px; right:40px;",  text: "2FA and account deletion are required for GDPR — Delete button in danger colour" },
    { dir: "right-up",   pos: "top:680px; left:80px;",   text: "No marketing emails by default · transactional only (PRD says no newsletter)" }
  ])}
</div>`;
}

function A6_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="padding:10px 0;">
    <div class="row gap-sm" style="align-items:center;">
      ${avatar(PHOTOS.profile)}
      <div><strong>Alex W.</strong><div class="ts-meta">Apprentice</div></div>
    </div>
    <h1 style="font-family:'Permanent Marker',cursive;font-size:24px;margin:10px 0 4px;">My account</h1>
    <div class="col" style="gap:8px;margin-top:8px;">
      ${["Account details","My courses","Purchase history","Email & notifications","Discord access","Privacy & data"].map(l => `
      <div class="card" style="padding:10px;display:flex;justify-content:space-between;align-items:center;">
        <span class="ts-body">${l}</span>
        <span style="font-size:18px;">›</span>
      </div>`).join('')}
      <div class="card" style="padding:10px;background:var(--warm-white);border-style:dashed;">
        <span class="ts-body" style="color:var(--coral);">Delete account</span>
      </div>
    </div>
  </div>
</div>`;
}

/* ============================================================ */
/*  A7 · SHOPPING CART / CHECKOUT (WooCommerce-style)           */
/* ============================================================ */

function A7_desktop() {
  const cart = [
    { t: "Portrait Fundamentals",    variant: "Apprentice · lifetime access",       p: 680, cover: PHOTOS.cover1 },
    { t: "Mixing greens from scratch", variant: "Fellow · + Discord channel",       p: 560, cover: PHOTOS.cover3 },
    { t: "Soft edges workshop",      variant: "Apprentice · lifetime access",       p: 380, cover: PHOTOS.cover4 }
  ];
  const subtotal = cart.reduce((s, i) => s + i.p, 0);
  const bundle = -100;   // pretend small discount
  const tax = 0;
  const total = subtotal + bundle + tax;
  return `<div class="wf" style="position:relative;">
  ${NAV}

  <div class="ts-meta" style="margin:6px 0 10px;">
    <span>Courses › Cart</span> ·
    <span>1. Cart</span> ›
    <span style="color:var(--muted);">2. Checkout</span> ›
    <span style="color:var(--muted);">3. Done</span>
  </div>

  <div class="section" style="padding-top:4px;">
    <div class="sp-between" style="align-items:flex-end;">
      <div>
        <div class="ts-meta">Shopping cart</div>
        <h1 style="font-family:'Permanent Marker',cursive;font-size:36px;margin:4px 0 0;">Your basket</h1>
        <div class="ts-body">${cart.length} courses · multi-course checkout enabled per PRD</div>
      </div>
      <span class="wf-btn ghost">← Keep browsing</span>
    </div>
  </div>

  <div class="section">
    <div class="row gap-lg" style="align-items:flex-start;">
      <!-- LEFT: cart table -->
      <div class="grow">
        <div class="card" style="padding:16px;">
          <!-- table head -->
          <div class="cart-row" style="border-bottom:2px solid var(--line);padding-bottom:8px;">
            <div class="ts-meta">Cover</div>
            <div class="ts-meta">Course</div>
            <div class="ts-meta" style="text-align:right;">Qty</div>
            <div class="ts-meta" style="text-align:right;">Price</div>
            <div></div>
          </div>
          ${cart.map(i => `
          <div class="cart-row">
            <div class="cart-thumb"><img src="${i.cover}" alt=""/></div>
            <div>
              <strong class="ts-h3" style="font-size:15px;">${i.t}</strong>
              <div class="ts-meta">${i.variant}</div>
              <div class="row gap-sm" style="margin-top:4px;">
                <span class="ts-meta" style="text-decoration:underline;">Change to Apprentice</span>
                <span class="ts-meta" style="text-decoration:underline;">Apply coupon</span>
              </div>
            </div>
            <div style="text-align:right;">
              <span style="border:2px solid var(--line);border-radius:8px;padding:4px 10px;background:var(--paper);">1 ▾</span>
            </div>
            <div style="text-align:right;font-family:Quicksand,sans-serif;font-weight:700;">HK$${i.p}</div>
            <div style="text-align:center;color:var(--coral);font-size:18px;cursor:pointer;">×</div>
          </div>`).join('')}

          <div class="row gap-sm" style="margin-top:12px;align-items:center;">
            <div style="border:2px solid var(--line);border-radius:8px;padding:8px 12px;background:var(--paper);font-family:Quicksand;flex:1;max-width:220px;">Coupon code</div>
            <span class="wf-btn">Apply</span>
            <span class="grow"></span>
            <span class="wf-btn ghost">Update cart ↻</span>
          </div>
        </div>

        <!-- Related / upsell -->
        <div class="card" style="padding:16px;margin-top:14px;background:var(--paper);">
          <div class="ts-meta">You might also like</div>
          <div class="grid-3" style="margin-top:8px;">
            ${[1,2,3].map(i => `
            <div class="tier-card" style="padding:10px;">
              ${photo(COVERS[(i + 2) % COVERS.length], '', 'square')}
              <div class="ts-meta" style="margin-top:4px;">Course</div>
              <div class="ts-h3" style="font-size:13px;">Sample related lesson ${i}</div>
              <div class="row gap-sm" style="margin-top:6px;align-items:center;">
                <span style="font-family:Quicksand;font-weight:700;">HK$420</span>
                <span class="wf-btn" style="font-size:12px;padding:4px 8px;">Add</span>
              </div>
            </div>`).join('')}
          </div>
        </div>
      </div>

      <!-- RIGHT: order summary (sticky) -->
      <aside style="flex:0 0 320px;position:sticky;top:14px;">
        <div class="card" style="padding:18px;border-width:2px;">
          <h3 class="ts-h3" style="margin:0 0 8px;">Order summary</h3>
          <div class="col" style="gap:6px;">
            <div class="sp-between"><span class="ts-body">Subtotal</span><span>HK$${subtotal}</span></div>
            <div class="sp-between"><span class="ts-body">Multi-course saving</span><span style="color:var(--forest);">HK$${bundle}</span></div>
            <div class="sp-between"><span class="ts-body">Tax (HK)</span><span>HK$${tax}</span></div>
            <div style="height:1px;background:var(--line);margin:6px 0;"></div>
            <div class="sp-between" style="font-family:'Permanent Marker',cursive;font-size:22px;">
              <span>Total</span><span>HK$${total}</span>
            </div>
          </div>
          <div class="wf-btn primary big" style="display:block;text-align:center;margin-top:14px;">Checkout →</div>

          <div class="ts-meta" style="text-align:center;margin-top:10px;">Pay with</div>
          <div class="row gap-sm" style="justify-content:center;margin-top:4px;">
            <span style="border:2px solid var(--line);border-radius:6px;padding:4px 10px;font-size:12px;background:var(--paper);">💳 Stripe</span>
            <span style="border:2px solid var(--line);border-radius:6px;padding:4px 10px;font-size:12px;background:var(--paper);">🅿 PayPal</span>
          </div>

          <div class="card" style="margin-top:14px;padding:10px;background:var(--mint);border-style:dashed;">
            <div class="ts-body" style="font-size:12px;">
              <strong>Lifetime access</strong> unlocks instantly after payment. Fellow courses also send a Discord invite code to your dashboard.
            </div>
          </div>
        </div>
      </aside>
    </div>
  </div>

  ${FOOTER}

  ${annotLayer([
    { dir: "right-down", pos: "top:60px; left:30px;",    text: "A5 · WooCommerce-style cart. Multi-course checkout (per PRD). Breadcrumb at top doubles as step indicator." },
    { dir: "left-up",    pos: "top:320px; right:40px;",  text: "Per-line controls: change tier (Apprentice ↔ Fellow), apply coupon, remove" },
    { dir: "left-up",    pos: "top:700px; right:40px;",  text: "Sticky summary card · Stripe + PayPal (PRD) · instant access after payment" }
  ])}
</div>`;
}

function A7_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="padding:8px 0;">
    <div class="ts-meta">Cart · Step 1/3</div>
    <h1 style="font-family:'Permanent Marker',cursive;font-size:24px;margin:4px 0 10px;">Your basket</h1>

    <div class="col" style="gap:8px;">
      ${[
        { t: "Portrait Fundamentals", variant: "Apprentice", p: 680, cover: PHOTOS.cover1 },
        { t: "Mixing greens",         variant: "Fellow",     p: 560, cover: PHOTOS.cover3 }
      ].map(i => `
      <div class="card" style="padding:10px;display:flex;gap:10px;align-items:flex-start;">
        <div style="width:60px;aspect-ratio:1/1;background:var(--placeholder);border:1.5px solid var(--line);border-radius:var(--radius-card);overflow:hidden;">
          <img src="${i.cover}" style="width:100%;height:100%;object-fit:cover;"/>
        </div>
        <div class="grow">
          <strong class="ts-h3" style="font-size:14px;">${i.t}</strong>
          <div class="ts-meta">${i.variant}</div>
          <div style="font-weight:700;margin-top:2px;">HK$${i.p}</div>
        </div>
        <span style="color:var(--coral);font-size:20px;">×</span>
      </div>`).join('')}
    </div>

    <div class="card" style="margin-top:14px;padding:14px;">
      <div class="sp-between"><span>Subtotal</span><span>HK$1,240</span></div>
      <div class="sp-between"><span>Saving</span><span style="color:var(--forest);">−HK$100</span></div>
      <div class="sp-between" style="font-family:'Permanent Marker',cursive;font-size:20px;margin-top:4px;">
        <span>Total</span><span>HK$1,140</span>
      </div>
      <div class="wf-btn primary big" style="text-align:center;margin-top:10px;">Checkout →</div>
    </div>
  </div>
</div>`;
}

/* ============================================================ */
/*  C3 · GALLERY  (Teacher's Work Showcase + Student Work Showcase) */
/* ============================================================ */

/* v0.10.7 — Gallery split into two showcases:
   1. Teacher's Work Showcase — Google-Photos-style 1:1 grid with adjustable
      columns + classic-waterfall hover animation (scale + black gradient).
      Hover reveals two CTAs: View (lightbox) and Buy (opens Etsy).
   2. Student Work Showcase — mixed-orientation collage (portrait, landscape,
      A4). Every row shares a STABLE height; widths derive from aspect ratio
      so landscape items appear wider while remaining the same height as a
      portrait sibling on the same row. Hover reveals student name + avatar
      over a black gradient. */

/* ---------- TEACHER items (sellable studio works, 1:1) ------- */
const TEACHER_ITEMS = [
  { src: PHOTOS.cover1, t: "Olive grove, late light",   date: "April 2026",    etsy: "https://www.etsy.com/listing/olive-grove-late-light" },
  { src: PHOTOS.brushA, t: "Brushwork study no.4",      date: "April 2026",    etsy: "https://www.etsy.com/listing/brushwork-study-4" },
  { src: PHOTOS.cover2, t: "Pomegranate, half-eaten",   date: "April 2026",    etsy: "https://www.etsy.com/listing/pomegranate-half-eaten" },
  { src: PHOTOS.liquid, t: "Liquid watercolour test",   date: "April 2026",    etsy: "https://www.etsy.com/listing/liquid-watercolour-test" },
  { src: PHOTOS.cover3, t: "Harbour, blue hour",        date: "March 2026",    etsy: "https://www.etsy.com/listing/harbour-blue-hour" },
  { src: PHOTOS.poster, t: "Poster colour drills",      date: "March 2026",    etsy: "https://www.etsy.com/listing/poster-colour-drills" },
  { src: PHOTOS.cover4, t: "Studio window, morning",    date: "March 2026",    etsy: "https://www.etsy.com/listing/studio-window-morning" },
  { src: PHOTOS.brushB, t: "Edge-control sampler",      date: "March 2026",    etsy: "https://www.etsy.com/listing/edge-control-sampler" },
  { src: PHOTOS.sketch, t: "Sketchbook · February",     date: "February 2026", etsy: "https://www.etsy.com/listing/sketchbook-feb" },
  { src: PHOTOS.rubber, t: "Eraser study (warm-up)",    date: "February 2026", etsy: "https://www.etsy.com/listing/eraser-study-warmup" },
  { src: PHOTOS.cover1, t: "Hillside, after rain",      date: "February 2026", etsy: "https://www.etsy.com/listing/hillside-after-rain" },
  { src: PHOTOS.liquid, t: "Wet-into-wet · greens",     date: "February 2026", etsy: "https://www.etsy.com/listing/wet-into-wet-greens" },
  { src: PHOTOS.cover2, t: "Persimmons, studio bench",  date: "January 2026",  etsy: "https://www.etsy.com/listing/persimmons-studio-bench" },
  { src: PHOTOS.cover3, t: "Pier at sunrise",           date: "January 2026",  etsy: "https://www.etsy.com/listing/pier-at-sunrise" },
  { src: PHOTOS.cover4, t: "Window light, vase",        date: "January 2026",  etsy: "https://www.etsy.com/listing/window-light-vase" },
  { src: PHOTOS.brushA, t: "Brushwork no.3",            date: "January 2026",  etsy: "https://www.etsy.com/listing/brushwork-3" }
];

/* ---------- STUDENT items (mixed orientation, 8 students) ----
   ratio = width / height. Three supported orientations:
     portrait  ≈ 0.75   (3:4)
     a4        ≈ 0.707  (1:√2)
     landscape ≈ 1.333  (4:3)  — but height stays equal to portrait siblings,
                                 so landscape items occupy MORE horizontal space.
*/
const STUDENT_ITEMS = [
  { src: PHOTOS.cover1, t: "First plein-air attempt",       student: "Amy Chan",      avatar: PHOTOS.profile, orient: "portrait",  date: "April 2026" },
  { src: PHOTOS.cover2, t: "Apple study",                   student: "Jordan Lee",    avatar: PHOTOS.profile, orient: "landscape", date: "April 2026" },
  { src: PHOTOS.cover3, t: "Sketchbook spread · greens",    student: "Mei Lam",       avatar: PHOTOS.profile, orient: "a4",        date: "April 2026" },
  { src: PHOTOS.cover4, t: "Window light, week 2",          student: "Tomás Reyes",   avatar: PHOTOS.profile, orient: "portrait",  date: "April 2026" },
  { src: PHOTOS.liquid, t: "Wet-on-wet exercise",           student: "Priya Patel",   avatar: PHOTOS.profile, orient: "landscape", date: "March 2026" },
  { src: PHOTOS.poster, t: "Poster-colour grid",            student: "Ken Wong",      avatar: PHOTOS.profile, orient: "a4",        date: "March 2026" },
  { src: PHOTOS.brushA, t: "Brush mileage",                 student: "Hana Kim",      avatar: PHOTOS.profile, orient: "portrait",  date: "March 2026" },
  { src: PHOTOS.cover1, t: "Coastline, low tide",           student: "Amy Chan",      avatar: PHOTOS.profile, orient: "landscape", date: "March 2026" },
  { src: PHOTOS.sketch, t: "Daily warm-ups",                student: "Jordan Lee",    avatar: PHOTOS.profile, orient: "a4",        date: "March 2026" },
  { src: PHOTOS.cover3, t: "First oil — pears",             student: "Mei Lam",       avatar: PHOTOS.profile, orient: "portrait",  date: "February 2026" },
  { src: PHOTOS.brushB, t: "Edge control study",            student: "Tomás Reyes",   avatar: PHOTOS.profile, orient: "landscape", date: "February 2026" },
  { src: PHOTOS.rubber, t: "Value scale practice",          student: "Priya Patel",   avatar: PHOTOS.profile, orient: "portrait",  date: "February 2026" },
  { src: PHOTOS.cover2, t: "Lemons in afternoon light",     student: "Ken Wong",      avatar: PHOTOS.profile, orient: "a4",        date: "February 2026" },
  { src: PHOTOS.liquid, t: "Sky studies",                   student: "Hana Kim",      avatar: PHOTOS.profile, orient: "landscape", date: "February 2026" }
];

const RATIO = { portrait: 0.75, a4: 1/Math.sqrt(2), landscape: 4/3 };

/* ---------- TEACHER tile (1:1, hover → View / Buy) ----------- */
function teacherTile(item, idx) {
  const safeT = item.t.replace(/"/g,'&quot;');
  return `
  <div class="t-tile" data-idx="${idx}" tabindex="0" role="button" aria-label="Open ${safeT}"
       style="position:relative;aspect-ratio:1/1;overflow:hidden;border:2px solid var(--line);border-radius:var(--radius-card);background:var(--paper-2,#efe8d8);cursor:zoom-in;">
    <img src="${item.src}" alt="${safeT}" loading="lazy"
         style="width:100%;height:100%;object-fit:cover;display:block;transform:scale(1);transition:transform .5s cubic-bezier(.25,0,.25,1);will-change:transform;"/>
    <div class="t-overlay" aria-hidden="true"
         style="position:absolute;inset:0;background:linear-gradient(180deg, rgba(0,0,0,0) 35%, rgba(0,0,0,0.78) 100%);opacity:0;transition:opacity .35s cubic-bezier(.25,0,.25,1);pointer-events:none;"></div>
    <div class="t-meta"
         style="position:absolute;left:10px;right:10px;bottom:48px;color:#fff;font-family:Quicksand,sans-serif;font-size:12px;line-height:1.3;opacity:0;transform:translateY(6px);transition:opacity .35s ease, transform .35s ease;pointer-events:none;">
      <div style="font-weight:600;">${safeT}</div>
      <div style="opacity:0.8;font-size:11px;">${item.date}</div>
    </div>
    <div class="t-cta"
         style="position:absolute;left:10px;right:10px;bottom:10px;display:flex;gap:6px;opacity:0;transform:translateY(6px);transition:opacity .35s ease, transform .35s ease;">
      <button type="button" class="t-btn t-view" data-act="view" data-idx="${idx}"
              style="flex:1;border:none;border-radius:999px;padding:7px 10px;font-family:Quicksand,sans-serif;font-size:12px;font-weight:600;cursor:pointer;background:#fff;color:#222;">View</button>
      <button type="button" class="t-btn t-buy" data-act="buy" data-idx="${idx}"
              style="flex:1;border:none;border-radius:999px;padding:7px 10px;font-family:Quicksand,sans-serif;font-size:12px;font-weight:600;cursor:pointer;background:var(--coral,#e6776a);color:#fff;">Buy on Etsy ↗</button>
    </div>
  </div>`;
}

function teacherGroup(label, items, startIdx) {
  return `
  <div class="gallery-group" style="margin-top:18px;">
    <div style="font-family:Quicksand,sans-serif;font-size:13px;font-weight:600;color:var(--ink);margin:0 0 8px 2px;">${label}</div>
    <div class="t-grid" style="display:grid;grid-template-columns:repeat(var(--t-cols, 4), 1fr);gap:6px;">
      ${items.map((it, i) => teacherTile(it, startIdx + i)).join('')}
    </div>
  </div>`;
}

function teacherShowcaseHTML(items) {
  const groups = {};
  const order = [];
  items.forEach((it, i) => {
    it._gidx = i;
    if (!groups[it.date]) { groups[it.date] = []; order.push(it.date); }
    groups[it.date].push(it);
  });
  const css = `<style>
    .t-tile:hover img, .t-tile:focus-visible img { transform: scale(1.08) !important; }
    .t-tile:hover .t-overlay, .t-tile:focus-within .t-overlay { opacity: 1 !important; }
    .t-tile:hover .t-cta, .t-tile:focus-within .t-cta,
    .t-tile:hover .t-meta, .t-tile:focus-within .t-meta {
      opacity: 1 !important; transform: translateY(0) !important; pointer-events: auto !important;
    }
    .t-btn:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
    .t-btn { transition: transform .15s ease, box-shadow .15s ease; }
  </style>`;
  let buf = '';
  let cursor = 0;
  for (const k of order) {
    buf += teacherGroup(k, groups[k], cursor);
    cursor += groups[k].length;
  }
  return css + buf;
}

/* ---------- STUDENT tile (mixed orientation, stable row height) ----
   Each tile has a fixed --row-h (set on the row container). Item width =
   row-h × ratio, so portrait + landscape can sit side-by-side at equal
   height. Flex-wrap handles overflow; gaps stay even. */
function studentTile(item, idx, rowH) {
  const ratio = RATIO[item.orient] || 1;
  const w = Math.round(rowH * ratio);
  const safeT = item.t.replace(/"/g,'&quot;');
  const safeN = item.student.replace(/"/g,'&quot;');
  return `
  <div class="s-tile" data-idx="${idx}" tabindex="0" role="button" aria-label="Open ${safeT} by ${safeN}"
       style="position:relative;flex:0 0 auto;width:${w}px;height:${rowH}px;overflow:hidden;border:2px solid var(--line);border-radius:var(--radius-card);background:var(--paper-2,#efe8d8);cursor:zoom-in;">
    <span class="s-ratio-tag" aria-hidden="true"
          style="position:absolute;top:6px;left:6px;z-index:2;background:rgba(255,255,255,0.85);color:var(--ink);font-family:Quicksand,sans-serif;font-size:9px;font-weight:600;padding:2px 6px;border-radius:999px;letter-spacing:0.04em;text-transform:uppercase;opacity:0.78;">${item.orient}</span>
    <img src="${item.src}" alt="${safeT}" loading="lazy"
         style="width:100%;height:100%;object-fit:cover;display:block;transform:scale(1);transition:transform .5s cubic-bezier(.25,0,.25,1);will-change:transform;"/>
    <div class="s-overlay" aria-hidden="true"
         style="position:absolute;inset:0;background:linear-gradient(180deg, rgba(0,0,0,0) 45%, rgba(0,0,0,0.82) 100%);opacity:0;transition:opacity .35s cubic-bezier(.25,0,.25,1);pointer-events:none;"></div>
    <div class="s-user"
         style="position:absolute;left:10px;right:10px;bottom:10px;display:flex;gap:8px;align-items:center;color:#fff;font-family:Quicksand,sans-serif;opacity:0;transform:translateY(6px);transition:opacity .35s ease, transform .35s ease;pointer-events:none;">
      <img src="${item.avatar}" alt=""
           style="width:30px;height:30px;border-radius:999px;object-fit:cover;border:1.5px solid #fff;flex:none;"/>
      <div style="min-width:0;line-height:1.2;">
        <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${safeN}</div>
        <div style="font-size:10px;opacity:0.85;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${safeT}</div>
      </div>
    </div>
  </div>`;
}

function studentGroup(label, items, startIdx, rowH) {
  return `
  <div class="gallery-group" style="margin-top:18px;">
    <div style="font-family:Quicksand,sans-serif;font-size:13px;font-weight:600;color:var(--ink);margin:0 0 8px 2px;">${label}</div>
    <div class="s-row" style="display:flex;flex-wrap:wrap;gap:6px;">
      ${items.map((it, i) => studentTile(it, startIdx + i, rowH)).join('')}
    </div>
  </div>`;
}

function studentShowcaseHTML(items, rowH = 220) {
  const groups = {};
  const order = [];
  items.forEach(it => {
    if (!groups[it.date]) { groups[it.date] = []; order.push(it.date); }
    groups[it.date].push(it);
  });
  const css = `<style>
    .s-tile:hover img, .s-tile:focus-visible img { transform: scale(1.06) !important; }
    .s-tile:hover .s-overlay, .s-tile:focus-within .s-overlay { opacity: 1 !important; }
    .s-tile:hover .s-user, .s-tile:focus-within .s-user {
      opacity: 1 !important; transform: translateY(0) !important; pointer-events: auto !important;
    }
    .s-tile:hover .s-ratio-tag, .s-tile:focus-within .s-ratio-tag { opacity: 0 !important; }
  </style>`;
  let buf = '';
  let cursor = 0;
  for (const k of order) {
    buf += studentGroup(k, groups[k], cursor, rowH);
    cursor += groups[k].length;
  }
  return css + buf;
}

/* ---------- Shared lightbox markup ---------- */
function lightboxMarkup() {
  return `
  <div id="gallery-lightbox" role="dialog" aria-modal="true" aria-label="Image preview"
       style="position:fixed;inset:0;background:rgba(10,10,12,0.92);z-index:9999;display:none;align-items:center;justify-content:center;padding:32px;">
    <button type="button" id="lb-close" aria-label="Close"
            style="position:absolute;top:18px;right:22px;background:rgba(255,255,255,0.12);border:1.5px solid rgba(255,255,255,0.4);color:#fff;font-size:22px;width:42px;height:42px;border-radius:999px;cursor:pointer;line-height:1;z-index:2;">×</button>
    <button type="button" id="lb-autoplay" aria-label="Toggle autoplay"
            style="position:absolute;top:18px;right:74px;background:rgba(255,255,255,0.12);border:1.5px solid rgba(255,255,255,0.4);color:#fff;font-family:Quicksand,sans-serif;font-size:12px;height:42px;padding:0 14px;border-radius:999px;cursor:pointer;line-height:1;z-index:2;">▶ Auto</button>
    <button type="button" id="lb-prev" aria-label="Previous"
            style="position:absolute;left:22px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.12);border:1.5px solid rgba(255,255,255,0.4);color:#fff;font-size:24px;width:46px;height:46px;border-radius:999px;cursor:pointer;line-height:1;z-index:2;">‹</button>
    <button type="button" id="lb-next" aria-label="Next"
            style="position:absolute;right:22px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.12);border:1.5px solid rgba(255,255,255,0.4);color:#fff;font-size:24px;width:46px;height:46px;border-radius:999px;cursor:pointer;line-height:1;z-index:2;">›</button>
    <figure style="margin:0;max-width:min(92vw, 1200px);max-height:88vh;display:flex;flex-direction:column;align-items:center;gap:14px;">
      <img id="lb-img" alt="" src=""
           style="max-width:100%;max-height:72vh;object-fit:contain;border-radius:6px;box-shadow:0 12px 40px rgba(0,0,0,0.5);background:#222;"/>
      <figcaption id="lb-cap"
                  style="color:#fff;font-family:Quicksand,sans-serif;font-size:14px;text-align:center;line-height:1.45;max-width:680px;">
        <div id="lb-title" style="font-weight:600;font-size:16px;"></div>
        <div id="lb-sub" style="opacity:0.75;font-size:12px;margin-top:4px;"></div>
      </figcaption>
    </figure>
  </div>`;
}

/* Inline script that powers Teacher hover-CTAs + shared lightbox.
   Re-binds each time C3/C4 renders (the wireframe app swaps innerHTML). */
function galleryScript() {
  return `
  <script>
  (function(){
    const root = document.getElementById('gallery-root');
    if (!root) return;
    const teacher = ${JSON.stringify(TEACHER_ITEMS)};
    const student = ${JSON.stringify(STUDENT_ITEMS)};
    const lb     = root.querySelector('#gallery-lightbox');
    const lbImg  = lb.querySelector('#lb-img');
    const lbT    = lb.querySelector('#lb-title');
    const lbS    = lb.querySelector('#lb-sub');
    const lbAuto = lb.querySelector('#lb-autoplay');
    let activeSet = teacher;
    let activeIdx = 0;
    let autoTimer = null;

    function isOpen(){ return lb.style.display === 'flex'; }
    function open(set, idx){
      activeSet = set;
      activeIdx = idx;
      render();
      lb.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
    function close(){
      stopAuto();
      lb.style.display = 'none';
      document.body.style.overflow = '';
    }
    function render(){
      const it = activeSet[activeIdx];
      if (!it) return;
      lbImg.src = it.src;
      lbImg.alt = it.t;
      lbT.textContent = it.t;
      lbS.textContent = it.student ? (it.student + ' · ' + it.date) : it.date;
    }
    function step(d){
      activeIdx = (activeIdx + d + activeSet.length) % activeSet.length;
      render();
    }
    function startAuto(){
      stopAuto();
      autoTimer = setInterval(() => step(1), 2500);
      lbAuto.textContent = '⏸ Auto';
      lbAuto.style.background = 'rgba(230,119,106,0.85)';
    }
    function stopAuto(){
      if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
      lbAuto.textContent = '▶ Auto';
      lbAuto.style.background = 'rgba(255,255,255,0.12)';
    }
    function toggleAuto(){ autoTimer ? stopAuto() : startAuto(); }

    lb.querySelector('#lb-close').addEventListener('click', close);
    lb.querySelector('#lb-prev').addEventListener('click', () => { stopAuto(); step(-1); });
    lb.querySelector('#lb-next').addEventListener('click', () => { stopAuto(); step(1); });
    lbAuto.addEventListener('click', toggleAuto);
    lb.addEventListener('click', e => { if (e.target === lb) close(); });
    document.addEventListener('keydown', function onKey(e){
      if (!isOpen()) return;
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowLeft') { stopAuto(); step(-1); }
      else if (e.key === 'ArrowRight') { stopAuto(); step(1); }
      else if (e.key === ' ') { e.preventDefault(); toggleAuto(); }
    });

    // Teacher tiles: View + Buy
    root.querySelectorAll('.t-tile').forEach(tile => {
      const idx = parseInt(tile.dataset.idx, 10);
      tile.addEventListener('click', e => {
        const actBtn = e.target.closest('[data-act]');
        if (actBtn) {
          e.stopPropagation();
          const act = actBtn.dataset.act;
          if (act === 'buy') {
            const url = teacher[idx] && teacher[idx].etsy;
            if (url) window.open(url, '_blank', 'noopener');
          } else {
            open(teacher, idx);
          }
          return;
        }
        open(teacher, idx);
      });
      tile.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(teacher, idx); }
      });
    });

    // Student tiles: lightbox only
    root.querySelectorAll('.s-tile').forEach(tile => {
      const idx = parseInt(tile.dataset.idx, 10);
      tile.addEventListener('click', () => open(student, idx));
      tile.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(student, idx); }
      });
    });

    // Teacher columns slider — write var to #teacher-grid container
    root.querySelectorAll('.t-cols-stop').forEach(btn => {
      btn.addEventListener('click', () => {
        const n = btn.dataset.cols;
        const wrap = root.querySelector('#teacher-grid-wrap');
        if (wrap) wrap.style.setProperty('--t-cols', n);
        root.querySelectorAll('.t-cols-stop').forEach(b => b.classList.toggle('active', b === btn));
      });
    });
  })();
  </script>`;
}

// Slider controls COLUMN COUNT directly (3..7 desktop, 2..4 mobile). Tile size is derived
// from container width ÷ cols. The readout now shows the discrete stops (e.g. "3·4·5·6·7")
// with the current value highlighted, so the available column counts are visible at a glance.
function zoomSlider(initial, min, max) {
  const btn = `width:26px;height:26px;border-radius:999px;border:2px solid var(--line);background:var(--paper-2,#efe8d8);font-family:Quicksand,sans-serif;font-weight:700;line-height:1;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:var(--ink);padding:0;`;
  const stops = [];
  for (let n = min; n <= max; n++) stops.push(n);
  const stopsHTML = stops.map(n =>
    `<button type="button" class="col-stop${n===initial?' active':''}" data-cols="${n}"
       onclick="window.zoomSliderJumpTo && window.zoomSliderJumpTo(this, ${n})"
       style="font-family:Quicksand,sans-serif;font-size:12px;font-weight:600;width:22px;height:22px;border-radius:999px;border:none;background:transparent;color:var(--ink-mute);cursor:pointer;padding:0;line-height:1;font-variant-numeric:tabular-nums;">${n}</button>`
  ).join('<span style="color:var(--ink-mute);opacity:0.4;font-size:11px;line-height:1;">·</span>');
  return `
  <div class="gallery-zoom" style="display:inline-flex;align-items:center;gap:6px;border:2px solid var(--line);border-radius:999px;background:var(--paper);padding:4px 8px 4px 14px;">
    <span style="font-family:Quicksand,sans-serif;font-size:12px;color:var(--ink-mute);letter-spacing:0.03em;">Columns</span>
    <button type="button" class="zoom-step" aria-label="Fewer columns (bigger tiles)" style="${btn}font-size:16px;"
      onclick="window.zoomSliderStep && window.zoomSliderStep(this,-1)">−</button>
    <input type="range" min="${min}" max="${max}" value="${initial}" step="1" class="zoom-range"
           style="width:110px;height:22px;accent-color:var(--coral,#e6776a);cursor:grab;"
           oninput="window.zoomSliderHandle && window.zoomSliderHandle(this)"/>
    <button type="button" class="zoom-step" aria-label="More columns (smaller tiles)" style="${btn}font-size:16px;"
      onclick="window.zoomSliderStep && window.zoomSliderStep(this,1)">+</button>
    <div class="col-stops zoom-readout" style="display:inline-flex;align-items:center;gap:2px;padding:0 4px 0 6px;">${stopsHTML}</div>
  </div>`;
}

/* ---------- Teacher columns picker (3..7) ---------- */
function teacherColsPicker(initial, min, max) {
  const stops = [];
  for (let n = min; n <= max; n++) stops.push(n);
  const stopsHTML = stops.map(n =>
    `<button type="button" class="t-cols-stop${n===initial?' active':''}" data-cols="${n}"
       style="font-family:Quicksand,sans-serif;font-size:12px;font-weight:600;width:24px;height:24px;border-radius:999px;border:1.5px solid transparent;background:transparent;color:var(--ink-mute);cursor:pointer;padding:0;line-height:1;font-variant-numeric:tabular-nums;">${n}</button>`
  ).join('');
  return `
  <div style="display:inline-flex;align-items:center;gap:6px;border:2px solid var(--line);border-radius:999px;background:var(--paper);padding:4px 10px 4px 14px;">
    <span style="font-family:Quicksand,sans-serif;font-size:12px;color:var(--ink-mute);letter-spacing:0.03em;">Images per row</span>
    <div style="display:inline-flex;align-items:center;gap:2px;">${stopsHTML}</div>
    <style>.t-cols-stop.active { color: var(--ink); border-color: var(--ink); background: var(--paper-2, #efe8d8); }</style>
  </div>`;
}

/* ---------- Section header helper ---------- */
function showcaseHeader(eyebrow, title, sub, right) {
  return `
  <div class="section" style="padding-top:18px;">
    <div class="sp-between" style="align-items:flex-end;flex-wrap:wrap;gap:10px;">
      <div>
        <div class="ts-meta">${eyebrow}</div>
        <h2 style="font-family:'Permanent Marker',cursive;font-size:30px;margin:4px 0 0;">${title}</h2>
        <div class="ts-body" style="max-width:600px;font-size:13px;">${sub}</div>
      </div>
      <div class="row gap-sm" style="align-items:center;flex-wrap:wrap;">${right || ''}</div>
    </div>
  </div>`;
}

function C3_desktop() {
  return `<div class="wf" style="position:relative;" id="gallery-root">
  ${NAV}

  <!-- MASTHEAD -->
  <div class="section" style="padding-top:10px;">
    <div>
      <div class="ts-meta">Gallery · Teacher</div>
      <h1 style="font-family:'Permanent Marker',cursive;font-size:44px;margin:4px 0 0;">Crystal's Studio Works</h1>
      <div class="ts-body" style="max-width:680px;">A Google-Photos-style showcase of Crystal's own studio pieces. Hover any tile to View in lightbox, or Buy the original on Etsy.</div>
    </div>
  </div>

  <!-- TEACHER SHOWCASE -->
  ${showcaseHeader(
    "Teacher's Work Showcase",
    "Studio Works",
    "1:1 Google-Photos grid. Adjust how many images sit per row.",
    teacherColsPicker(4, 3, 7)
  )}
  <div class="section" id="teacher-grid-wrap" style="--t-cols:4;padding-top:0;">
    ${teacherShowcaseHTML(TEACHER_ITEMS)}
  </div>

  ${lightboxMarkup()}
  ${galleryScript()}
  ${FOOTER}

  ${annotLayer([
    { dir: "right-down", pos: "top:32px; left:30px;",   text: "C3 = Teacher's Work Showcase. C4 (next tab) = Student Work Showcase. Both share the same lightbox." },
    { dir: "left-up",    pos: "top:200px; right:50px;", text: "Hover reveals VIEW (lightbox) + BUY (opens Etsy). Black gradient + scale animation (matches classic waterfall hover)." },
    { dir: "right-up",   pos: "top:360px; left:120px;", text: "Images-per-row chip (3·4·5·6·7) reflows grid live." }
  ])}
</div>`;
}

function C3_mobile() {
  return `<div class="wf" id="gallery-root">
  ${NAV_MOBILE}
  <div class="section" style="padding:8px 0;">
    <h1 style="font-family:'Permanent Marker',cursive;font-size:26px;margin:0;">Teacher's Works</h1>
    <div class="ts-body" style="font-size:12px;margin-top:2px;">Crystal's studio pieces · tap to View or Buy on Etsy.</div>
  </div>

  <div class="section" style="padding:6px 0 0;">
    <div style="margin-bottom:8px;">${teacherColsPicker(3, 2, 4)}</div>
  </div>
  <div class="section" id="teacher-grid-wrap" style="--t-cols:3;padding-top:0;">
    ${teacherShowcaseHTML(TEACHER_ITEMS)}
  </div>

  ${lightboxMarkup()}
  ${galleryScript()}
</div>`;
}

/* ============================================================ */
/*  C4 · GALLERY — STUDENT WORK SHOWCASE                        */
/* ============================================================ */
function C4G_desktop() {
  return `<div class="wf" style="position:relative;" id="gallery-root">
  ${NAV}

  <!-- MASTHEAD -->
  <div class="section" style="padding-top:10px;">
    <div>
      <div class="ts-meta">Gallery · Student</div>
      <h1 style="font-family:'Permanent Marker',cursive;font-size:44px;margin:4px 0 0;">From the Class</h1>
      <div class="ts-body" style="max-width:680px;">Selected works submitted by students. Mixed orientation collage — portrait, A4 and landscape pieces share a stable row height.</div>
    </div>
  </div>

  <!-- STUDENT SHOWCASE -->
  ${showcaseHeader(
    "Student Work Showcase",
    "Mixed-Orientation Collage",
    "Each row keeps a STABLE height. Width = height × aspect ratio, so landscape items appear wider but never taller than portrait siblings on the same row.",
    `<span style="font-family:Quicksand,sans-serif;font-size:12px;color:var(--ink-mute);">Row height locked · widths follow ratio</span>`
  )}
  <div class="section" style="padding-top:0;">
    ${studentShowcaseHTML(STUDENT_ITEMS, 220)}

    <div class="center" style="padding:18px 0 6px;">
      <span class="wf-btn ghost">Load more student works ↓</span>
    </div>

    <div class="card cream" style="margin-top:8px;padding:14px;">
      <div class="ts-meta">Submission</div>
      <div class="ts-body" style="font-size:13px;margin-top:4px;">
        Students can upload their work from the <strong>Dashboard → My Courses</strong>. Crystal reviews and features selected pieces here.
      </div>
    </div>
  </div>

  ${lightboxMarkup()}
  ${galleryScript()}
  ${FOOTER}

  ${annotLayer([
    { dir: "right-down", pos: "top:32px; left:30px;",   text: "C4 = Student Work Showcase. Companion to C3 (Teacher)." },
    { dir: "left-up",    pos: "top:340px; right:50px;", text: "Stable row HEIGHT, width follows aspect ratio (portrait 3:4 · A4 1:√2 · landscape 4:3). Landscape items end up wider, never taller." },
    { dir: "right-up",   pos: "top:520px; left:90px;",  text: "Hover reveals student name + avatar over a black gradient. Click = lightbox." }
  ])}
</div>`;
}

function C4G_mobile() {
  return `<div class="wf" id="gallery-root">
  ${NAV_MOBILE}
  <div class="section" style="padding:8px 0;">
    <h1 style="font-family:'Permanent Marker',cursive;font-size:26px;margin:0;">Student Works</h1>
    <div class="ts-body" style="font-size:12px;margin-top:2px;">Mixed orientation, stable row height. Tap to enlarge.</div>
  </div>

  <div class="section" style="padding-top:0;">
    ${studentShowcaseHTML(STUDENT_ITEMS, 150)}
    <div class="center" style="padding:12px 0;"><span class="wf-btn ghost">Load more ↓</span></div>
  </div>

  ${lightboxMarkup()}
  ${galleryScript()}
</div>`;
}

/* ============================================================ */
/*  L5 · COURSES CATALOG (browse + filter)                      */
/* ============================================================ */

const CATALOG_ITEMS = [
  { t: "Portrait Fundamentals",        med: "Oils",       lvl: "Beginner→Intermediate", lessons: 12, dur: "6h 20m", price: "HK$680", src: PHOTOS.cover1, tier: "Apprentice" },
  { t: "Still Life in Watercolour",    med: "Watercolour",lvl: "Beginner",              lessons:  8, dur: "3h 40m", price: "HK$420", src: PHOTOS.cover3, tier: "Apprentice" },
  { t: "Mixing Greens From Scratch",   med: "Oils",       lvl: "All levels",            lessons:  5, dur: "2h 10m", price: "HK$280", src: PHOTOS.liquid, tier: "Apprentice" },
  { t: "Drawing the Human Head",       med: "Drawing",    lvl: "Beginner→Intermediate", lessons: 10, dur: "5h 00m", price: "HK$560", src: PHOTOS.cover2, tier: "Apprentice" },
  { t: "Acrylic Landscape Studies",    med: "Acrylics",   lvl: "Intermediate",          lessons:  9, dur: "4h 30m", price: "HK$520", src: PHOTOS.cover4, tier: "Fellow" },
  { t: "Edges — Hard to Lost",         med: "Oils",       lvl: "Intermediate",          lessons:  4, dur: "1h 50m", price: "HK$240", src: PHOTOS.brushA, tier: "Apprentice" },
  { t: "Liquid Watercolour Techniques",med: "Watercolour",lvl: "Beginner",              lessons:  6, dur: "2h 30m", price: "HK$320", src: PHOTOS.liquid, tier: "Apprentice" },
  { t: "Poster Colour Crash Course",   med: "Poster",     lvl: "Beginner",              lessons:  5, dur: "1h 40m", price: "HK$220", src: PHOTOS.poster, tier: "Apprentice" },
  { t: "Sketchbook Habits",            med: "Drawing",    lvl: "All levels",            lessons:  7, dur: "2h 50m", price: "HK$300", src: PHOTOS.sketch, tier: "Apprentice" }
];

function L5_desktop() {
  const cats = [
    { n: "All",          c: CATALOG_ITEMS.length, active: true },
    { n: "Oils",         c: 3 },
    { n: "Watercolour",  c: 2 },
    { n: "Acrylics",     c: 1 },
    { n: "Drawing",      c: 2 },
    { n: "Poster",       c: 1 }
  ];
  return `<div class="wf" style="position:relative;">
  ${NAV}
  <div class="ts-meta" style="margin:6px 0 8px;">Courses › <strong>All courses</strong></div>

  <div class="section" style="padding-top:4px;">
    <div class="sp-between" style="align-items:flex-end;">
      <div>
        <div class="ts-meta">Course catalogue</div>
        <h1 style="font-family:'Permanent Marker',cursive;font-size:38px;margin:4px 0 6px;">Browse every lesson</h1>
        <div class="ts-body" style="max-width:560px;">${CATALOG_ITEMS.length} courses across oils, watercolour, acrylics, drawing and poster. Filter by medium, level, or length — preview the first lesson of any course for free.</div>
      </div>
      <div style="text-align:right;">
        <div class="ts-meta">Sort by</div>
        <div style="border:2px solid var(--line);border-radius:8px;padding:6px 10px;background:var(--paper);">Newest ▾</div>
      </div>
    </div>
  </div>

  <div class="section" style="padding-top:0;">
    <div class="row gap-lg" style="align-items:flex-start;">
      <!-- LEFT filter rail -->
      <aside style="flex:0 0 220px;">
        <div class="card" style="padding:14px;margin-bottom:10px;">
          <h3 class="ts-h3">Category</h3>
          <ul style="list-style:none;padding:0;margin:6px 0 0;">
            ${cats.map(c => `
              <li style="padding:6px 0;border-bottom:1px dashed var(--line-soft);${c.active ? 'font-weight:600;color:var(--teal-ink);' : ''}">
                ${c.n} <span class="ts-meta">(${c.c})</span>
              </li>`).join('')}
          </ul>
        </div>
        <div class="card" style="padding:14px;margin-bottom:10px;">
          <h3 class="ts-h3">Level</h3>
          <div class="col" style="gap:6px;margin-top:6px;">
            ${["Beginner","Intermediate","All levels"].map(x => `
              <label class="row gap-sm" style="align-items:center;font-size:13px;">
                <span style="width:14px;height:14px;border:2px solid var(--line);display:inline-block;"></span>
                ${x}
              </label>`).join('')}
          </div>
        </div>
        <div class="card" style="padding:14px;">
          <h3 class="ts-h3">Length</h3>
          <div class="col" style="gap:6px;margin-top:6px;">
            ${["Under 2 h","2–4 h","4 h+"].map(x => `
              <label class="row gap-sm" style="align-items:center;font-size:13px;">
                <span style="width:14px;height:14px;border:2px solid var(--line);display:inline-block;"></span>
                ${x}
              </label>`).join('')}
          </div>
        </div>
        <div class="card blush" style="margin-top:10px;padding:12px;">
          <h3 class="ts-h3">Free preview</h3>
          <div class="ts-body" style="font-size:13px;">Every course includes one free lesson — preview before you buy.</div>
        </div>
      </aside>

      <!-- RIGHT grid -->
      <div class="grow">
        <div class="row gap-sm" style="margin-bottom:10px;align-items:center;flex-wrap:wrap;">
          ${cats.map(c => `<span class="wf-btn ${c.active ? 'primary' : 'ghost'}" style="font-size:12px;">${c.n} · ${c.c}</span>`).join('')}
        </div>
        <div class="grid-3" style="align-items:stretch;">
          ${CATALOG_ITEMS.map(c => `
          <div class="card" style="padding:0;overflow:hidden;display:flex;flex-direction:column;">
            <div style="position:relative;">
              ${photo(c.src, '', 'square')}
              <span class="ts-meta" style="position:absolute;top:8px;left:8px;background:var(--paper);border:2px solid var(--line);border-radius:4px;padding:2px 6px;font-size:11px;">${c.med}</span>
            </div>
            <div style="padding:12px;flex:1;display:flex;flex-direction:column;">
              <h3 class="ts-h3" style="font-size:16px;margin:0;">${c.t}</h3>
              <div class="ts-meta" style="margin-top:4px;">${c.lvl} · ${c.lessons} lessons · ${c.dur}</div>
              <div class="sp-between" style="margin-top:auto;padding-top:10px;align-items:center;">
                <strong style="font-family:'Permanent Marker',cursive;font-size:18px;">${c.price}</strong>
                <span class="wf-btn primary" style="padding:4px 10px;font-size:12px;">View →</span>
              </div>
            </div>
          </div>`).join('')}
        </div>
        <div class="center" style="padding:14px 0;"><span class="wf-btn ghost">Load more ↓</span></div>
      </div>
    </div>
  </div>
  ${FOOTER}

  ${annotLayer([
    { dir: "right-up",  pos: "top:90px; left:80px;",  text: "Courses catalogue per PRD §1 — filter by oils / acrylics / drawing / watercolour" },
    { dir: "left-up",   pos: "top:240px; right:40px;",text: "Sort + filter rail · cards link to L1/L2 sales page" },
    { dir: "right-down",pos: "top:480px; left:60px;", text: "Course card = 1:1 cover · medium chip · level · price · CTA" }
  ])}
</div>`;
}

function L5_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="padding:8px 0;">
    <h1 style="font-family:'Permanent Marker',cursive;font-size:24px;margin:0;">Browse courses</h1>
    <div class="row gap-sm" style="margin-top:8px;flex-wrap:wrap;">
      <span class="wf-btn primary" style="font-size:11px;">All · 9</span>
      <span class="wf-btn ghost" style="font-size:11px;">Oils</span>
      <span class="wf-btn ghost" style="font-size:11px;">Watercolour</span>
      <span class="wf-btn ghost" style="font-size:11px;">Drawing</span>
    </div>
    <div style="border:2px solid var(--line);border-radius:8px;padding:6px 10px;background:var(--paper);margin-top:8px;font-size:12px;">Sort: Newest ▾  ·  Filters (2)</div>
  </div>
  <div class="col">
    ${CATALOG_ITEMS.slice(0,4).map(c => `
    <div class="card" style="padding:10px;display:flex;gap:10px;">
      <div style="flex:0 0 90px;">${photo(c.src, '', 'square')}</div>
      <div class="grow">
        <div class="ts-meta">${c.med} · ${c.dur}</div>
        <strong class="ts-h3" style="font-size:14px;">${c.t}</strong>
        <div class="sp-between" style="margin-top:6px;align-items:center;">
          <strong style="font-family:'Permanent Marker',cursive;">${c.price}</strong>
          <span class="wf-btn primary" style="padding:2px 8px;font-size:11px;">View</span>
        </div>
      </div>
    </div>`).join('')}
  </div>
</div>`;
}

/* ============================================================ */
/*  L6 · PAYWALL / LOCKED LESSON STATE                          */
/* ============================================================ */

function L6_desktop() {
  const syllabus = [
    { n: 1, t: "Materials & setting up",     d: "18 min", state: "free"   },
    { n: 2, t: "Blocking in large shapes",   d: "32 min", state: "locked" },
    { n: 3, t: "Value structure",            d: "28 min", state: "locked" },
    { n: 4, t: "Skin tone mixing",           d: "41 min", state: "locked" },
    { n: 5, t: "Edges — hard to lost",       d: "24 min", state: "locked" },
    { n: 6, t: "Features — eyes",            d: "36 min", state: "locked" }
  ];
  return `<div class="wf" style="position:relative;">
  ${NAV}
  <div class="ts-meta" style="margin:6px 0 8px;">Courses › Oils › Portrait Fundamentals › <strong>Lesson 2 · Blocking in</strong></div>

  <!-- Locked player band -->
  <div class="section" style="padding-top:4px;">
    <div style="position:relative;background:var(--ink);border:2px solid var(--line);border-radius:var(--radius-card);overflow:hidden;aspect-ratio:16/9;display:flex;align-items:center;justify-content:center;">
      <!-- Blurred poster + lock overlay -->
      <img src="${PHOTOS.cover1}" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:blur(8px) brightness(0.5);"/>
      <div style="position:relative;text-align:center;color:var(--cream);padding:30px;max-width:520px;">
        <div style="font-size:42px;">🔒</div>
        <div class="ts-meta" style="color:var(--mint);">Lesson locked</div>
        <h2 style="font-family:'Permanent Marker',cursive;font-size:30px;margin:6px 0;">Buy the course to keep watching</h2>
        <div class="ts-body" style="color:var(--cream);font-size:14px;">You've finished the free preview (Lesson 1). The remaining 11 lessons unlock when you buy the course — lifetime access, no subscription.</div>
        <div class="row gap-sm" style="justify-content:center;margin-top:14px;">
          <span class="wf-btn primary big" style="background:var(--coral);">Buy course — HK$680</span>
          <span class="wf-btn big" style="background:var(--paper);">+ Fellow — HK$780</span>
        </div>
        <div class="ts-meta" style="color:var(--mint);margin-top:8px;">Lifetime access · download materials · resume anywhere</div>
      </div>
    </div>
  </div>

  <div class="section">
    <div class="row gap-lg" style="align-items:flex-start;">
      <div class="grow">
        <h2 style="font-family:'Permanent Marker',cursive;font-size:24px;">Syllabus · 12 lessons</h2>
        <div class="col" style="margin-top:8px;">
          ${syllabus.map(l => `
          <div class="card" style="display:grid;grid-template-columns:40px 1fr auto auto;gap:12px;align-items:center;${l.state === 'locked' ? 'opacity:0.6;' : ''}">
            <div style="font-family:'Permanent Marker',cursive;font-size:22px;color:var(--teal-ink);">${String(l.n).padStart(2,'0')}</div>
            <div>
              <strong class="ts-h3">${l.t}</strong>
              <div class="ts-body" style="font-size:13px;">Short lesson description.</div>
            </div>
            <div class="ts-meta">${l.d}</div>
            ${l.state === 'free'
              ? `<span class="wf-btn primary" style="padding:4px 10px;font-size:12px;">✓ Watched</span>`
              : `<span style="font-size:18px;color:var(--muted);">🔒</span>`}
          </div>`).join('')}
        </div>
      </div>

      <!-- Sticky buy card with tier picker -->
      <aside style="flex:0 0 320px;position:sticky;top:14px;">
        <div class="card" style="border-width:2px;padding:18px;">
          ${photo(PHOTOS.cover1, 'preview', 'square')}
          <h3 class="ts-h3" style="margin-top:10px;font-family:'Permanent Marker',cursive;">Unlock all 12 lessons</h3>
          <div class="col" style="gap:8px;margin-top:10px;">
            <label class="card" style="padding:10px;cursor:pointer;background:var(--warm-white);border:2px solid var(--teal-ink);">
              <div class="sp-between">
                <strong>Apprentice</strong><strong>HK$680</strong>
              </div>
              <div class="ts-body" style="font-size:12px;">Lifetime course access · downloads</div>
            </label>
            <label class="card" style="padding:10px;cursor:pointer;background:var(--paper);">
              <div class="sp-between">
                <strong>+ Fellow</strong><strong>HK$780</strong>
              </div>
              <div class="ts-body" style="font-size:12px;">Adds private Discord channel · direct Q&amp;A with Crystal</div>
            </label>
          </div>
          <div class="wf-btn primary big" style="display:block;text-align:center;margin-top:12px;">Buy now →</div>
          <div class="ts-meta" style="text-align:center;margin-top:6px;">No refunds · preview first lesson free</div>
        </div>
      </aside>
    </div>
  </div>
  ${FOOTER}

  ${annotLayer([
    { dir: "right-down", pos: "top:80px; left:60px;",  text: "PRD P0 · free preview → paywall. Lesson 1 watched, Lesson 2 locked." },
    { dir: "left-up",    pos: "top:240px; right:60px;",text: "Blurred poster + lock overlay communicates 'content exists, gated by purchase'" },
    { dir: "right-up",   pos: "top:560px; left:60px;", text: "Sticky tier picker · per-course Apprentice ↔ Fellow upsell" }
  ])}
</div>`;
}

function L6_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="padding:8px 0;">
    <div style="position:relative;background:var(--ink);border:2px solid var(--line);border-radius:var(--radius-card);aspect-ratio:16/9;display:flex;align-items:center;justify-content:center;text-align:center;color:var(--cream);">
      <div style="padding:16px;">
        <div style="font-size:32px;">🔒</div>
        <strong>Lesson locked</strong>
        <div class="ts-meta" style="color:var(--mint);">Buy course to keep watching</div>
      </div>
    </div>
    <div class="card" style="margin-top:10px;padding:12px;">
      <strong>Portrait Fundamentals</strong>
      <div class="ts-meta">12 lessons · 6h 20m · lifetime</div>
      <div class="wf-btn primary big" style="display:block;text-align:center;margin-top:8px;">Buy — HK$680</div>
      <div class="wf-btn" style="display:block;text-align:center;margin-top:6px;">+ Fellow — HK$780</div>
    </div>
  </div>
</div>`;
}

/* ============================================================ */
/*  D1 · DASHBOARD (post-login, resume + my courses + history)  */
/* ============================================================ */

function D1_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}

  <div class="sp-between" style="margin:6px 0 12px;align-items:flex-end;">
    <div>
      <div class="ts-meta">Signed in</div>
      <h1 style="font-family:'Permanent Marker',cursive;font-size:34px;margin:4px 0 0;">Welcome back, Alex.</h1>
    </div>
    <div class="row gap-sm" style="align-items:center;">
      ${avatar(PHOTOS.profile)}
      <div class="ts-body" style="font-size:13px;"><strong>Alex W.</strong><br/><span class="ts-meta">Apprentice · 3 courses</span></div>
      <span class="wf-btn ghost" style="margin-left:8px;">Account ▾</span>
    </div>
  </div>

  <div class="acct-grid">
    <!-- LEFT nav -->
    <nav class="acct-nav">
      <ul>
        <li class="active">Overview</li>
        <li>Account details</li>
        <li>My courses</li>
        <li>Purchase history</li>
        <li>Email &amp; notifications</li>
        <li>Discord access</li>
        <li>Privacy &amp; data</li>
        <li style="color:var(--coral);">Delete account</li>
      </ul>
    </nav>

    <!-- RIGHT content -->
    <div>
      <!-- Continue watching -->
      <div class="card" style="padding:0;overflow:hidden;margin-bottom:14px;">
        <div class="row" style="gap:0;align-items:stretch;">
          <div style="flex:0 0 320px;position:relative;">
            ${photo(PHOTOS.cover1, '', 'square')}
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.25);">
              <div style="width:56px;height:56px;border-radius:50%;background:var(--paper);border:2px solid var(--line);display:flex;align-items:center;justify-content:center;font-size:22px;">▶</div>
            </div>
            <!-- progress bar -->
            <div style="position:absolute;left:0;right:0;bottom:0;height:6px;background:rgba(255,255,255,0.4);">
              <div style="width:42%;height:100%;background:var(--coral);"></div>
            </div>
          </div>
          <div style="flex:1;padding:18px;">
            <div class="ts-meta">Continue watching · 42% through</div>
            <h2 style="font-family:'Permanent Marker',cursive;font-size:26px;margin:4px 0 6px;">Portrait Fundamentals — Lesson 4 · Skin tone mixing</h2>
            <div class="ts-body" style="font-size:13px;">Paused 2 days ago at 18:42. Resume from where you left off — your spot is saved.</div>
            <div class="row gap-sm" style="margin-top:12px;">
              <span class="wf-btn primary big">Resume lesson →</span>
              <span class="wf-btn ghost">Restart from beginning</span>
            </div>
          </div>
        </div>
      </div>

      <!-- My courses -->
      <div class="card" style="padding:18px;margin-bottom:14px;">
        <div class="sp-between" style="margin-bottom:10px;">
          <h2 class="ts-h3" style="font-size:18px;">My courses · 3 enrolled</h2>
          <span class="wf-btn ghost" style="font-size:12px;">Browse more →</span>
        </div>
        <div class="grid-3" style="align-items:stretch;">
          ${[
            { t: "Portrait Fundamentals",        prog: 42, src: PHOTOS.cover1, last: "Lesson 4" },
            { t: "Mixing Greens From Scratch",   prog: 100,src: PHOTOS.liquid, last: "Completed" },
            { t: "Still Life in Watercolour",    prog: 0,  src: PHOTOS.cover3, last: "Not started" }
          ].map(c => `
          <div class="card" style="padding:0;overflow:hidden;">
            <div style="position:relative;">
              ${photo(c.src, '', 'square')}
              <div style="position:absolute;left:0;right:0;bottom:0;height:5px;background:rgba(0,0,0,0.2);">
                <div style="width:${c.prog}%;height:100%;background:var(--coral);"></div>
              </div>
            </div>
            <div style="padding:10px;">
              <strong class="ts-h3" style="font-size:14px;">${c.t}</strong>
              <div class="ts-meta">${c.last} · ${c.prog}%</div>
              <div class="wf-btn ${c.prog === 100 ? 'ghost' : 'primary'}" style="display:block;text-align:center;margin-top:8px;font-size:12px;">${c.prog === 100 ? 'Review' : c.prog === 0 ? 'Start' : 'Resume'}</div>
            </div>
          </div>`).join('')}
        </div>
      </div>

      <!-- Two-column: Purchases + Discord/Community -->
      <div class="row gap-md">
        <!-- Purchase history -->
        <div class="card grow" style="padding:18px;">
          <div class="sp-between" style="margin-bottom:8px;">
            <h2 class="ts-h3" style="font-size:18px;">Recent purchases</h2>
            <span class="wf-btn ghost" style="font-size:12px;">See all →</span>
          </div>
          <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
              <tr style="text-align:left;color:var(--muted);border-bottom:1px dashed var(--line-soft);">
                <th style="padding:6px 0;">Order</th><th>Course</th><th>Tier</th><th style="text-align:right;">Total</th>
              </tr>
            </thead>
            <tbody>
              ${[
                ["#1042 · 12 Apr 2026", "Portrait Fundamentals",      "Apprentice", "HK$680"],
                ["#1027 · 03 Apr 2026", "Mixing Greens From Scratch", "Apprentice", "HK$280"],
                ["#1011 · 22 Mar 2026", "Still Life in Watercolour",  "Apprentice", "HK$420"]
              ].map(r => `
              <tr style="border-bottom:1px dashed var(--line-soft);">
                <td style="padding:8px 0;">${r[0]}</td><td>${r[1]}</td><td>${r[2]}</td><td style="text-align:right;font-family:'Permanent Marker',cursive;">${r[3]}</td>
              </tr>`).join('')}
            </tbody>
          </table>
        </div>

        <!-- Fellow Discord card -->
        <div class="card blush" style="padding:18px;flex:0 0 280px;">
          <div class="ts-meta">Fellow access</div>
          <h2 class="ts-h3" style="font-size:18px;margin:4px 0 6px;">Private Discord channel</h2>
          <div class="ts-body" style="font-size:13px;">You bought Fellow on <strong>Mixing Greens</strong>. Join the private studio channel.</div>
          <div style="margin-top:10px;border:2px dashed var(--line);border-radius:8px;padding:10px;background:var(--paper);">
            <div class="ts-meta">Your invite code</div>
            <div style="font-family:ui-monospace,monospace;font-size:16px;letter-spacing:1px;">CRYSTAL-7HQ4-FELLOW</div>
          </div>
          <div class="wf-btn primary" style="display:block;text-align:center;margin-top:10px;">Open Discord →</div>
          <div class="ts-meta" style="text-align:center;margin-top:6px;">Code reveals after Fellow purchase</div>
        </div>
      </div>
    </div>
  </div>
  ${FOOTER}

  ${annotLayer([
    { dir: "left-down", pos: "top:60px; right:40px;",  text: "PRD §Dashboard · resume video + course progress + purchase history" },
    { dir: "right-up",  pos: "top:240px; left:60px;",  text: "Continue Watching = hero of dashboard · saved position from L3/L4 player" },
    { dir: "left-up",   pos: "top:540px; right:60px;", text: "My Courses · per-card progress bar reads from same store as Resume" },
    { dir: "right-up",  pos: "top:820px; left:60px;",  text: "Q#14 resolved: manual invite code shown after Fellow purchase (no auto-bot)" }
  ])}
</div>`;
}

function D1_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="padding:8px 0;">
    <h1 style="font-family:'Permanent Marker',cursive;font-size:24px;margin:0;">Welcome back</h1>
    <div class="ts-meta">Alex · Apprentice</div>
  </div>

  <div class="card" style="padding:0;overflow:hidden;margin-bottom:10px;">
    <div style="position:relative;">
      ${photo(PHOTOS.cover1, '', 'square')}
      <div style="position:absolute;left:0;right:0;bottom:0;height:5px;background:rgba(255,255,255,0.4);">
        <div style="width:42%;height:100%;background:var(--coral);"></div>
      </div>
    </div>
    <div style="padding:10px;">
      <div class="ts-meta">Continue · 42%</div>
      <strong>Portrait Fundamentals · L4</strong>
      <div class="wf-btn primary" style="display:block;text-align:center;margin-top:8px;">Resume →</div>
    </div>
  </div>

  <div class="col">
    ${[
      { t: "Mixing Greens", prog: 100, src: PHOTOS.liquid },
      { t: "Still Life",    prog: 0,   src: PHOTOS.cover3 }
    ].map(c => `
    <div class="card" style="padding:10px;display:flex;gap:10px;">
      <div style="flex:0 0 70px;">${photo(c.src, '', 'square')}</div>
      <div class="grow">
        <strong>${c.t}</strong>
        <div class="ts-meta">${c.prog}% complete</div>
        <div class="wf-btn ${c.prog === 100 ? 'ghost' : 'primary'}" style="padding:2px 8px;font-size:11px;margin-top:4px;display:inline-block;">${c.prog === 100 ? 'Review' : 'Start'}</div>
      </div>
    </div>`).join('')}
  </div>
</div>`;
}

/* ============================================================ */
/*  A6N · ORDER CONFIRMATION / RECEIPT (post-checkout)          */
/* ============================================================ */

function A6N_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}

  <!-- 3-step breadcrumb showing complete -->
  <div class="row gap-sm" style="margin:10px 0 12px;align-items:center;font-size:12px;color:var(--muted);">
    <span>① Cart</span><span>›</span><span>② Payment</span><span>›</span><strong style="color:var(--forest);">③ Done ✓</strong>
  </div>

  <div class="section" style="text-align:center;padding-top:6px;">
    <div style="font-size:54px;">🎨</div>
    <div class="ts-meta" style="color:var(--forest);">Payment complete</div>
    <h1 style="font-family:'Permanent Marker',cursive;font-size:42px;margin:6px 0 4px;">Your courses are unlocked.</h1>
    <div class="ts-body" style="max-width:520px;margin:0 auto;">Thanks Alex. We've sent your receipt to <strong>alex@example.com</strong>. Start any course below or jump back to the dashboard whenever you're ready.</div>
  </div>

  <div class="section">
    <div class="row gap-lg" style="align-items:flex-start;">
      <!-- LEFT: order summary -->
      <div class="grow card" style="padding:20px;">
        <div class="sp-between" style="margin-bottom:10px;">
          <div>
            <div class="ts-meta">Order</div>
            <strong style="font-family:'Permanent Marker',cursive;font-size:22px;">#1042</strong>
          </div>
          <div style="text-align:right;">
            <div class="ts-meta">Placed</div>
            <strong>23 Apr 2026 · 14:08</strong>
          </div>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:13px;margin-top:8px;">
          <thead>
            <tr style="text-align:left;color:var(--muted);border-bottom:2px solid var(--line);">
              <th style="padding:8px 0;">Course</th><th>Tier</th><th style="text-align:right;">Price</th>
            </tr>
          </thead>
          <tbody>
            ${[
              ["Portrait Fundamentals",        "Apprentice", "HK$680"],
              ["Mixing Greens From Scratch",   "Fellow",     "HK$380"]
            ].map(r => `
            <tr style="border-bottom:1px dashed var(--line-soft);">
              <td style="padding:10px 0;"><strong>${r[0]}</strong></td>
              <td>${r[1]}</td>
              <td style="text-align:right;font-family:'Permanent Marker',cursive;">${r[2]}</td>
            </tr>`).join('')}
          </tbody>
          <tfoot>
            <tr><td colspan="2" style="padding:10px 0;text-align:right;" class="ts-meta">Subtotal</td><td style="text-align:right;">HK$1,060</td></tr>
            <tr><td colspan="2" style="padding:2px 0;text-align:right;" class="ts-meta">Coupon · STUDIO10</td><td style="text-align:right;color:var(--coral);">−HK$106</td></tr>
            <tr style="border-top:2px solid var(--line);"><td colspan="2" style="padding:10px 0;text-align:right;"><strong>Total paid (Stripe · •••• 4242)</strong></td><td style="text-align:right;font-family:'Permanent Marker',cursive;font-size:20px;">HK$954</td></tr>
          </tfoot>
        </table>
      </div>

      <!-- RIGHT: next steps -->
      <aside style="flex:0 0 300px;">
        <div class="card" style="padding:16px;margin-bottom:10px;">
          <h3 class="ts-h3" style="font-family:'Permanent Marker',cursive;">What's next</h3>
          <ul style="list-style:none;padding:0;margin:8px 0 0;font-size:13px;">
            <li style="padding:6px 0;border-bottom:1px dashed var(--line-soft);">▶ Start Portrait Fundamentals</li>
            <li style="padding:6px 0;border-bottom:1px dashed var(--line-soft);">💬 Get Fellow Discord invite</li>
            <li style="padding:6px 0;border-bottom:1px dashed var(--line-soft);">⬇ Download materials PDF</li>
            <li style="padding:6px 0;">📧 Receipt emailed to alex@…</li>
          </ul>
          <div class="wf-btn primary big" style="display:block;text-align:center;margin-top:12px;">Go to dashboard →</div>
          <div class="wf-btn ghost" style="display:block;text-align:center;margin-top:6px;font-size:12px;">Print receipt</div>
        </div>
        <div class="card blush" style="padding:14px;">
          <div class="ts-meta">Fellow access</div>
          <strong>Discord invite code</strong>
          <div style="font-family:ui-monospace,monospace;font-size:14px;letter-spacing:1px;margin-top:4px;background:var(--paper);border:2px dashed var(--line);padding:6px;">CRYSTAL-7HQ4-FELLOW</div>
        </div>
      </aside>
    </div>
  </div>
  ${FOOTER}

  ${annotLayer([
    { dir: "right-down", pos: "top:120px; left:80px;",  text: "Order confirmation per PRD checkout flow · arrives after A5 Stripe success" },
    { dir: "left-up",    pos: "top:240px; right:60px;", text: "Receipt + courses unlocked CTA · 'Go to dashboard' is the primary path" },
    { dir: "right-up",   pos: "top:520px; left:60px;",  text: "Fellow invite code revealed inline · also lives in D1 dashboard" }
  ])}
</div>`;
}

function A6N_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="text-align:center;padding:14px 0;">
    <div style="font-size:38px;">🎨</div>
    <h1 style="font-family:'Permanent Marker',cursive;font-size:24px;margin:6px 0;">Courses unlocked</h1>
    <div class="ts-body" style="font-size:13px;">Order #1042 · HK$954</div>
  </div>
  <div class="card" style="padding:12px;">
    <strong>Portrait Fundamentals</strong><div class="ts-meta">Apprentice · HK$680</div>
    <hr style="border:none;border-top:1px dashed var(--line-soft);margin:8px 0;"/>
    <strong>Mixing Greens</strong><div class="ts-meta">Fellow · HK$380</div>
    <div class="wf-btn primary big" style="display:block;text-align:center;margin-top:10px;">Go to dashboard →</div>
  </div>
</div>`;
}

/* ============================================================ */
/*  A7N · EMAIL VERIFICATION (two states stacked)               */
/* ============================================================ */

function A7N_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}

  <!-- STATE 1 · check inbox -->
  <div style="max-width:520px;margin:30px auto 14px;">
    <div class="ts-meta" style="text-align:center;">State 1 of 2 · just signed up</div>
    <div class="card" style="padding:28px;text-align:center;border-width:2px;">
      <div style="font-size:48px;">📬</div>
      <h1 style="font-family:'Permanent Marker',cursive;font-size:30px;margin:8px 0 6px;">Check your inbox</h1>
      <div class="ts-body">We sent a verification link to <strong>alex@example.com</strong>. Click it within 24 hours to activate your free account.</div>

      <div style="margin-top:18px;padding:12px;border:2px dashed var(--line);border-radius:8px;background:var(--paper);text-align:left;">
        <div class="ts-meta">Didn't get the email?</div>
        <ul style="margin:6px 0 0 18px;font-size:13px;padding:0;">
          <li>Check spam / promotions</li>
          <li>Wait 60 seconds — it can be slow</li>
          <li>Wrong address? Sign up again with the correct one</li>
        </ul>
      </div>

      <div class="row gap-sm" style="justify-content:center;margin-top:14px;">
        <span class="wf-btn primary">Resend email</span>
        <span class="wf-btn ghost">Change email</span>
      </div>
      <div class="ts-meta" style="margin-top:8px;">Resend available in 47s</div>
    </div>
  </div>

  <!-- STATE 2 · success -->
  <div style="max-width:520px;margin:14px auto 30px;">
    <div class="ts-meta" style="text-align:center;">State 2 of 2 · link clicked</div>
    <div class="card" style="padding:28px;text-align:center;background:var(--mint);border-width:2px;">
      <div style="font-size:48px;">✅</div>
      <h1 style="font-family:'Permanent Marker',cursive;font-size:30px;margin:8px 0 6px;">You're verified.</h1>
      <div class="ts-body">Welcome, Alex. Your account is active. You can now browse free lessons and the studio blog.</div>
      <div class="row gap-sm" style="justify-content:center;margin-top:14px;">
        <span class="wf-btn primary big">Go to dashboard →</span>
        <span class="wf-btn ghost">Browse courses</span>
      </div>
    </div>
  </div>
  ${FOOTER}

  ${annotLayer([
    { dir: "right-up",  pos: "top:90px; left:60px;",   text: "PRD P0 · email verification on signup (requires WP plugin)" },
    { dir: "left-up",   pos: "top:200px; right:80px;", text: "Resend with cooldown — prevents spam abuse" },
    { dir: "right-down",pos: "top:500px; left:60px;",  text: "State 2 is what they see AFTER clicking the email link · routes to D1 dashboard" }
  ])}
</div>`;
}

function A7N_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="text-align:center;padding:20px 0;">
    <div style="font-size:36px;">📬</div>
    <h1 style="font-family:'Permanent Marker',cursive;font-size:22px;margin:6px 0;">Check your inbox</h1>
    <div class="ts-body" style="font-size:13px;">Link sent to alex@example.com</div>
    <div class="wf-btn primary" style="margin-top:10px;display:inline-block;">Resend</div>
  </div>
  <div class="section" style="text-align:center;padding:14px 0;background:var(--mint);border-radius:var(--radius-card);">
    <div style="font-size:32px;">✅</div>
    <strong style="font-family:'Permanent Marker',cursive;">Verified</strong>
    <div class="wf-btn primary" style="margin-top:8px;display:inline-block;">Continue</div>
  </div>
</div>`;
}

/* ============================================================ */
/*  A8 · FORGOT / RESET PASSWORD (three states stacked)         */
/* ============================================================ */

function A8_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}

  <div class="section" style="text-align:center;padding-top:14px;">
    <div class="ts-meta">Account recovery</div>
    <h1 style="font-family:'Permanent Marker',cursive;font-size:34px;margin:6px 0;">Forgot your password?</h1>
    <div class="ts-body" style="max-width:520px;margin:0 auto;">Three short states. Reset link works for 30 minutes.</div>
  </div>

  <div class="grid-3" style="align-items:stretch;">
    <!-- STATE 1 · forgot form -->
    <div class="card" style="padding:20px;">
      <div class="ts-meta">① Request</div>
      <h2 class="ts-h3" style="font-family:'Permanent Marker',cursive;">Enter your email</h2>
      <div class="col" style="margin-top:10px;gap:10px;">
        <div>
          <div class="ts-meta">Email</div>
          <div style="border:2px solid var(--line);border-radius:8px;padding:10px 12px;background:var(--paper);">alex@example.com</div>
        </div>
        <div class="wf-btn primary big" style="text-align:center;">Send reset link →</div>
        <div class="ts-body" style="font-size:12px;text-align:center;"><strong>Back to log in</strong></div>
      </div>
    </div>

    <!-- STATE 2 · link sent -->
    <div class="card" style="padding:20px;background:var(--warm-white);">
      <div class="ts-meta">② Link sent</div>
      <h2 class="ts-h3" style="font-family:'Permanent Marker',cursive;">Check your inbox</h2>
      <div style="font-size:38px;text-align:center;margin:8px 0;">✉️</div>
      <div class="ts-body" style="font-size:13px;">If <strong>alex@example.com</strong> is an account, we've sent a reset link. Open it on the same device within 30 minutes.</div>
      <div class="wf-btn ghost" style="display:block;text-align:center;margin-top:12px;font-size:12px;">Resend in 60s</div>
      <div class="ts-meta" style="margin-top:6px;font-size:11px;">We don't confirm whether an email exists — GDPR.</div>
    </div>

    <!-- STATE 3 · new password -->
    <div class="card" style="padding:20px;background:var(--mint);">
      <div class="ts-meta">③ Set new password</div>
      <h2 class="ts-h3" style="font-family:'Permanent Marker',cursive;">Pick a new one</h2>
      <div class="col" style="margin-top:10px;gap:10px;">
        <div>
          <div class="ts-meta">New password</div>
          <div style="border:2px solid var(--line);border-radius:8px;padding:10px 12px;background:var(--paper);">••••••••</div>
        </div>
        <div>
          <div class="ts-meta">Confirm</div>
          <div style="border:2px solid var(--line);border-radius:8px;padding:10px 12px;background:var(--paper);">••••••••</div>
        </div>
        <div class="ts-body" style="font-size:11px;">8+ chars · 1 number · 1 symbol</div>
        <div class="wf-btn primary big" style="text-align:center;">Save &amp; log in →</div>
      </div>
    </div>
  </div>
  ${FOOTER}

  ${annotLayer([
    { dir: "right-up",   pos: "top:140px; left:60px;",  text: "3 stacked states show entire flow on one wireframe — implementation = 3 separate routes" },
    { dir: "down",       pos: "top:280px; left:50%;",   text: "Reset token expires in 30 min · GDPR-friendly: don't leak whether email exists" },
    { dir: "left-up",    pos: "top:400px; right:60px;", text: "Pairs with A1 sign-up + A7 verify · completes the auth quartet" }
  ])}
</div>`;
}

function A8_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}
  <div class="section" style="padding:14px 0;">
    <h1 style="font-family:'Permanent Marker',cursive;font-size:22px;margin:0;">Reset password</h1>
    <div class="col" style="margin-top:10px;gap:8px;">
      <div style="border:2px solid var(--line);border-radius:8px;padding:8px;background:var(--paper);">Email</div>
      <div class="wf-btn primary" style="text-align:center;">Send link</div>
    </div>
    <div class="card" style="margin-top:12px;padding:10px;text-align:center;">
      <div style="font-size:28px;">✉️</div>
      <strong>Check your inbox</strong>
      <div class="ts-meta">Resend in 60s</div>
    </div>
  </div>
</div>`;
}

/* ============================================================ */
/*  C5 · BLOG POST DETAIL (article reader · reimagined v0.10.14) */
/* ============================================================ */
/* Layout rhythm:
   1. Breadcrumb
   2. Cinematic full-width hero (16:9) — overlay title + byline + ratio chip
   3. Three-column action rail (sticky-share L · article centre · TOC R)
   4. Article body — lead paragraph, body, figure w/ caption, pull-quote,
      sub-heading, list, closing CTA strip
   5. Tag cloud + author bio card
   6. Engagement bar (likes / comments / save / share)
   7. Comments — composer + thread + load-more
   8. Image-heavy related-posts rail (per client feedback: images > text)
   9. Footer (newsletter strip removed — PRD §OUT)
*/

function C5_desktop() {
  return `<div class="wf" style="position:relative;">
  ${NAV}

  <!-- 1 · Breadcrumb -->
  <div class="section" style="padding:6px 0 8px;">
    <div class="ts-meta">Blog › Studio notes › <strong>Why I keep a colour journal</strong></div>
  </div>

  <!-- 2 · Cinematic hero · F1 banner treatment (64:9 ~7.1:1, chip H1) -->
  <div class="section" style="padding:6px 0 10px;">
    <div style="position:relative;aspect-ratio:64/9;overflow:hidden;border-radius:var(--radius-card);background:var(--placeholder);">
      <img src="${PHOTOS.cover2}" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"/>
      <div style="position:absolute;inset:0;background:linear-gradient(90deg, rgba(20,16,12,0.50) 0%, rgba(20,16,12,0.15) 60%, rgba(20,16,12,0) 100%);"></div>
      <div style="position:absolute;left:28px;top:50%;transform:translateY(-50%);color:#fff;max-width:640px;">
        <h1 style="display:inline-block;font-family:'Permanent Marker',cursive;font-size:32px;line-height:1.05;margin:0 0 8px;color:#fff;background:rgba(20,16,12,0.45);padding:6px 14px;border-radius:6px;backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);">Why I keep a colour journal</h1>
        <div class="row gap-sm" style="align-items:center;flex-wrap:wrap;color:#fff;font-family:Quicksand,sans-serif;font-size:12px;background:rgba(20,16,12,0.45);padding:4px 10px;border-radius:6px;display:inline-flex;width:auto;">
          <strong>Crystal Lau</strong><span style="opacity:0.85;">· 22 Apr 2026 · 8 min · Studio Notes</span>
        </div>
      </div>
      <div style="position:absolute;right:10px;bottom:6px;font:10px/1 'JetBrains Mono',ui-monospace,monospace;color:rgba(255,255,255,0.92);background:rgba(0,0,0,0.55);padding:3px 7px;border-radius:4px;letter-spacing:0.04em;">banner · 64:9 (~7.1:1) · matches F1</div>
    </div>
  </div>

  <!-- 3 · Three-column body: sticky share rail · article · sticky TOC -->
  <div class="section" style="padding-top:0;">
    <div class="row gap-lg" style="align-items:flex-start;">

      <!-- LEFT · sticky share rail (icon column) -->
      <aside style="flex:0 0 56px;position:sticky;top:14px;">
        <div class="col" style="gap:8px;align-items:center;padding:8px 6px;border:2px solid var(--line);border-radius:999px;background:var(--paper);">
          <button class="wf-btn" style="padding:6px 0;width:32px;text-align:center;line-height:1;" title="Like">♡<div style="font-size:9px;font-weight:700;margin-top:2px;">42</div></button>
          <button class="wf-btn ghost" style="padding:6px 0;width:32px;text-align:center;line-height:1;" title="Comments">💬<div style="font-size:9px;font-weight:700;margin-top:2px;">12</div></button>
          <button class="wf-btn ghost" style="padding:6px 0;width:32px;text-align:center;line-height:1;" title="Save">🔖</button>
          <button class="wf-btn ghost" style="padding:6px 0;width:32px;text-align:center;line-height:1;" title="Share">⤴</button>
        </div>
        <div class="ts-meta" style="font-size:9px;text-align:center;margin-top:4px;line-height:1.2;">sticky · scrolls with article</div>
      </aside>

      <!-- CENTRE · article · F1 typography (Permanent Marker display + Quicksand body) -->
      <article class="grow" style="max-width:720px;font-family:Quicksand,sans-serif;">

        <!-- Lead paragraph -->
        <p class="ts-body" style="font-size:18px;line-height:1.55;font-weight:500;color:var(--ink);">
          A little notebook of mixes I've tried. Not for anyone else — just for me. Here's what goes in it, and why I've kept one for the last decade.
        </p>

        <!-- H2 · Section 1 -->
        <h2 id="toc-why" style="font-family:'Permanent Marker',cursive;font-size:30px;line-height:1.05;margin:28px 0 10px;color:var(--ink);">Why a colour journal?</h2>
        <p class="ts-body" style="font-size:16px;line-height:1.65;">
          When I started teaching, I noticed every student asks the same question by week two: "how did you get that grey?". The honest answer is I don't remember unless I wrote it down. The journal isn't art — it's bookkeeping for paint.
        </p>

        <h3 id="toc-why-precious" style="font-family:Quicksand,sans-serif;font-weight:700;font-size:18px;line-height:1.3;margin:16px 0 6px;color:var(--ink);">The trap of being precious</h3>
        <p class="ts-body" style="font-size:16px;line-height:1.65;">
          If it takes more than a minute per mix, you'll stop. Mine has coffee rings on every page and that's the point.
        </p>

        <!-- Inline figure -->
        <figure style="margin:22px 0;">
          <div style="border-radius:var(--radius-card);overflow:hidden;aspect-ratio:3/2;">
            <img src="${PHOTOS.liquid}" alt="" style="width:100%;height:100%;object-fit:cover;display:block;"/>
          </div>
          <figcaption class="ts-meta" style="margin-top:6px;text-align:center;font-style:italic;">
            Fig. 1 — A spread from the journal: ratios, swatches, and one line of notes per mix.
          </figcaption>
        </figure>

        <!-- H2 · Section 2 -->
        <h2 id="toc-page" style="font-family:'Permanent Marker',cursive;font-size:30px;line-height:1.05;margin:28px 0 10px;color:var(--ink);">What goes on each page</h2>
        <p class="ts-body" style="font-size:16px;line-height:1.65;">
          Three things, always in the same order. Skip any of them and the entry becomes useless six months later.
        </p>

        <h3 id="toc-page-recipe" style="font-family:Quicksand,sans-serif;font-weight:700;font-size:18px;line-height:1.3;margin:16px 0 6px;color:var(--ink);">1 · The mix recipe</h3>
        <p class="ts-body" style="font-size:16px;line-height:1.65;">Ratios, brand, water temperature. Numbers, not adjectives — "1:3 with cool water" beats "a little watery".</p>

        <h3 id="toc-page-swatch" style="font-family:Quicksand,sans-serif;font-weight:700;font-size:18px;line-height:1.3;margin:16px 0 6px;color:var(--ink);">2 · The swatch</h3>
        <p class="ts-body" style="font-size:16px;line-height:1.65;">Pure, plus three dilutions side-by-side. You're cataloguing how the mix behaves at every strength, not just one.</p>

        <h3 id="toc-page-review" style="font-family:Quicksand,sans-serif;font-weight:700;font-size:18px;line-height:1.3;margin:16px 0 6px;color:var(--ink);">3 · One-line review</h3>
        <p class="ts-body" style="font-size:16px;line-height:1.65;">What you'd do differently next time. This is the line you'll thank yourself for in six months.</p>

        <!-- Pull quote (the one decisive flourish) -->
        <blockquote style="margin:28px 0;padding:18px 24px;border-left:4px solid var(--coral,#e6776a);background:var(--paper-2,#efe8d8);border-radius:0 10px 10px 0;">
          <p style="font-family:'Permanent Marker',cursive;font-size:24px;line-height:1.25;margin:0;color:var(--ink);">
            "The journal isn't precious. It's a receipt."
          </p>
          <div class="ts-meta" style="margin-top:6px;">— pull quote · Permanent Marker · 24px</div>
        </blockquote>

        <!-- H2 · Section 3 -->
        <h2 id="toc-notebook" style="font-family:'Permanent Marker',cursive;font-size:30px;line-height:1.05;margin:28px 0 10px;color:var(--ink);">The notebook itself</h2>
        <p class="ts-body" style="font-size:16px;line-height:1.65;">
          I use a Stillman &amp; Birn Beta — cold-press, hot-glued spine so it lies flat. The choice matters more than people expect.
        </p>

        <h3 id="toc-notebook-paper" style="font-family:Quicksand,sans-serif;font-weight:700;font-size:18px;line-height:1.3;margin:16px 0 6px;color:var(--ink);">Paper weight · 140gsm</h3>
        <p class="ts-body" style="font-size:16px;line-height:1.65;">Anything thinner buckles under a wash; anything thicker feels too precious to mess up.</p>

        <h3 id="toc-notebook-binding" style="font-family:Quicksand,sans-serif;font-weight:700;font-size:18px;line-height:1.3;margin:16px 0 6px;color:var(--ink);">Binding · lies flat</h3>
        <p class="ts-body" style="font-size:16px;line-height:1.65;">Hot-glued, not stapled. A journal that won't stay open is a journal that won't get used.</p>

        <!-- Tags strip -->
        <div class="row gap-sm" style="margin-top:24px;flex-wrap:wrap;align-items:center;">
          <span class="ts-meta">Tagged ›</span>
          ${['#watercolour', '#studio-notes', '#teaching', '#materials', '#process'].map(t => `<span class="wf-btn ghost" style="font-size:11px;padding:4px 10px;">${t}</span>`).join('')}
        </div>

        <!-- Author bio (in-article, expanded) -->
        <div class="card" style="margin-top:20px;padding:14px;display:flex;gap:14px;align-items:flex-start;">
          <div style="flex:0 0 72px;">${avatar(PHOTOS.profile, 'lg')}</div>
          <div class="grow">
            <div class="ts-meta">WRITTEN BY</div>
            <h3 style="font-family:'Permanent Marker',cursive;font-size:20px;margin:2px 0;">Crystal Lau</h3>
            <div class="ts-body" style="font-size:13px;">Oils &amp; watercolour teacher · small studio in Hong Kong · 12 years teaching beginners.</div>
            <div class="row gap-sm" style="margin-top:6px;">
              <span class="wf-btn ghost" style="font-size:11px;">Follow</span>
              <span class="wf-btn ghost" style="font-size:11px;">All posts (47)</span>
            </div>
          </div>
        </div>

        <!-- 6 · Engagement bar -->
        <div class="row gap-sm" style="margin-top:24px;align-items:center;padding:14px;border:2px solid var(--line);border-radius:var(--radius-card);background:var(--paper);">
          <span class="wf-btn primary">♡ 42 likes</span>
          <span class="wf-btn">💬 12 comments</span>
          <span class="wf-btn ghost">⤴ Share</span>
          <span class="wf-btn ghost" style="margin-left:auto;">🔖 Save</span>
        </div>

        <!-- 7 · Comments -->
        <div style="margin-top:28px;">
          <div class="sp-between" style="align-items:baseline;">
            <h2 class="ts-h3" style="font-family:'Permanent Marker',cursive;font-size:24px;margin:0;">Comments · 12</h2>
            <span class="ts-meta">Sort: Newest ▾</span>
          </div>

          <!-- Composer (registered-only) -->
          <div class="card" style="padding:14px;margin-top:10px;">
            <div class="row gap-sm" style="align-items:flex-start;">
              ${avatar(COVERS[2])}
              <div class="grow">
                <div class="ts-meta">Signed in as Alex W. · Apprentice</div>
                <div style="border:2px solid var(--line);border-radius:8px;padding:10px 12px;background:var(--paper);min-height:72px;font-size:14px;color:var(--muted);margin-top:4px;">Add a comment…</div>
                <div class="row gap-sm" style="margin-top:8px;align-items:center;">
                  <span class="ts-meta">Markdown supported · Be kind.</span>
                  <span class="wf-btn ghost" style="font-size:12px;margin-left:auto;">Cancel</span>
                  <span class="wf-btn primary" style="font-size:12px;">Post comment</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Alt state for logged-out users -->
          <div class="card cream" style="margin-top:8px;padding:10px 14px;border-style:dashed;">
            <div class="ts-body" style="font-size:12px;">
              <strong>Logged-out state</strong> → composer replaced with:
              <span class="wf-btn ghost" style="font-size:11px;margin-left:6px;">Log in to comment</span>
              <span class="wf-btn" style="font-size:11px;margin-left:4px;">Sign up free</span>
            </div>
          </div>

          <!-- Thread -->
          ${[
            { n: "Priya M.",  d: "2 hours ago", q: "I started a journal after your Brighton workshop — already saved me from buying a tube of premixed grey. Genuinely changed how I shop for paint.", likes: 6, src: COVERS[4], replies: 1 },
            { n: "Jun H.",    d: "Yesterday",   q: "Do you keep separate notebooks per medium or all in one? Curious if oils + watercolour mixes get confusing on the same page.", likes: 2, src: COVERS[5], replies: 0 },
            { n: "Marcus T.", d: "2 days ago",  q: "The 'coffee rings on every page' line — that's exactly the permission I needed. Mine looks too tidy and I never use it.", likes: 9, src: COVERS[6], replies: 2 }
          ].map(c => `
          <div class="card" style="padding:12px 14px;margin-top:8px;">
            <div class="row gap-sm" style="align-items:flex-start;">
              ${avatar(c.src)}
              <div class="grow">
                <div><strong>${c.n}</strong> <span class="ts-meta">· ${c.d}</span></div>
                <div class="ts-body" style="font-size:13px;margin-top:4px;line-height:1.5;">${c.q}</div>
                <div class="row gap-sm" style="margin-top:8px;align-items:center;">
                  <span class="ts-meta" style="cursor:pointer;">♡ ${c.likes}</span>
                  <span class="ts-meta" style="cursor:pointer;">↩ Reply${c.replies ? ` (${c.replies})` : ''}</span>
                  <span class="ts-meta" style="margin-left:auto;cursor:pointer;">⋯</span>
                </div>
              </div>
            </div>
          </div>`).join('')}

          <div class="center" style="padding:14px;">
            <span class="wf-btn ghost">Load 9 more comments ↓</span>
          </div>
        </div>
      </article>

      <!-- RIGHT · sticky sidebar · expanded TOC (scroll-spy demo) + tags + free CTA -->
      <aside style="flex:0 0 240px;position:sticky;top:14px;">

        <!-- TOC · multi-level (H2 + H3) demonstrating scroll-spy -->
        <div class="card no-border" style="padding:14px;margin-bottom:12px;background:var(--paper);">
          <div class="ts-meta" style="margin-bottom:8px;">ON THIS PAGE</div>
          <ul style="list-style:none;padding:0;margin:0;font-family:Quicksand,sans-serif;font-size:12px;line-height:1.5;">

            <li style="border-left:2px solid var(--line-soft);padding:4px 0 4px 10px;color:var(--muted);">Why a colour journal?</li>
            <li style="border-left:2px solid var(--line-soft);padding:3px 0 3px 22px;color:var(--muted);font-size:11px;opacity:0.85;">↳ The trap of being precious</li>

            <li style="border-left:2px solid var(--coral,#e6776a);padding:5px 0 5px 10px;color:var(--ink);font-weight:700;background:linear-gradient(90deg,rgba(230,119,106,0.10),transparent);">What goes on each page</li>
            <li style="border-left:2px solid var(--coral,#e6776a);padding:3px 0 3px 22px;color:var(--ink);font-size:11px;">↳ <strong>1 · The mix recipe</strong> <span class="ts-meta" style="font-size:9px;">← active</span></li>
            <li style="border-left:2px solid var(--line-soft);padding:3px 0 3px 22px;color:var(--muted);font-size:11px;opacity:0.85;">↳ 2 · The swatch</li>
            <li style="border-left:2px solid var(--line-soft);padding:3px 0 3px 22px;color:var(--muted);font-size:11px;opacity:0.85;">↳ 3 · One-line review</li>

            <li style="border-left:2px solid var(--line-soft);padding:4px 0 4px 10px;color:var(--muted);">The notebook itself</li>
            <li style="border-left:2px solid var(--line-soft);padding:3px 0 3px 22px;color:var(--muted);font-size:11px;opacity:0.85;">↳ Paper weight · 140gsm</li>
            <li style="border-left:2px solid var(--line-soft);padding:3px 0 3px 22px;color:var(--muted);font-size:11px;opacity:0.85;">↳ Binding · lies flat</li>

            <li style="border-left:2px solid var(--line-soft);padding:4px 0 4px 10px;color:var(--muted);">Comments (12)</li>
          </ul>
          <div class="ts-meta" style="font-size:9px;margin-top:10px;opacity:0.6;line-height:1.3;">scroll-spy · coral bar marks current H2 + nested H3</div>
        </div>

        <!-- Popular tags cloud -->
        <div class="card no-border" style="padding:12px;margin-bottom:12px;">
          <div class="ts-meta" style="margin-bottom:8px;">POPULAR TAGS</div>
          <div class="row gap-sm" style="flex-wrap:wrap;gap:6px;">
            ${[
              {t:'#watercolour', n:42},
              {t:'#beginner', n:38},
              {t:'#studio-notes', n:24},
              {t:'#materials', n:19},
              {t:'#oils', n:17},
              {t:'#colour-mixing', n:14},
              {t:'#sketchbook', n:12},
              {t:'#process', n:9}
            ].map(x => `<span class="wf-btn ghost" style="font-size:10px;padding:3px 8px;line-height:1.4;">${x.t} <span style="opacity:0.55;">${x.n}</span></span>`).join('')}
          </div>
        </div>

        ${SIDEBAR_FREE()}

        <div class="ts-meta" style="font-size:9px;text-align:center;line-height:1.3;opacity:0.6;">sticky right rail · TOC + tags + free CTA · author bio lives inline in the article (removed from sidebar per client mark)</div>
      </aside>
    </div>
  </div>

  <!-- 8 · Image-heavy related posts rail (per client: more image, less text) -->
  <div class="section" style="padding-top:18px;border-top:2px dashed var(--line-soft);">
    <div class="sp-between" style="align-items:baseline;margin-bottom:10px;">
      <div>
        <div class="ts-meta">KEEP READING</div>
        <h2 style="font-family:'Permanent Marker',cursive;font-size:28px;margin:2px 0 0;">More studio notes</h2>
      </div>
      <span class="wf-btn ghost">All posts →</span>
    </div>
    <div class="grid-4">
      ${POST_SET.slice(0, 4).map(p => `
      <article class="wf-post tile compact" style="cursor:pointer;">
        <div class="wf-thumb" style="aspect-ratio:4/3;">${photo(p.src, '', 'square')}</div>
        <div class="wf-body" style="padding:10px 4px 6px;">
          <div class="ts-meta wf-tagrow" style="font-size:10px;">
            <span class="${p.kind === 'LESSON' ? 'blog-tag lesson' : 'blog-tag'}">${p.kind === 'LESSON' ? 'LESSON' : 'BLOG'}</span>
            <span>${p.date}</span>
          </div>
          <h3 class="wf-title" style="font-size:14px;line-height:1.25;margin-top:4px;">${p.t}</h3>
        </div>
      </article>`).join('')}
    </div>
  </div>

  <!-- 9 · Newsletter strip REMOVED v0.10.25 · PRD §OUT excludes newsletter/mass email -->

  ${FOOTER}

  ${annotLayer([
    { dir: "right-down", pos: "top:40px; left:30px;",   text: "C5 · PRD §1.4 — full blog post page. Hero adopts F1 banner treatment for brand consistency." },
    { dir: "left-up",    pos: "top:100px; right:60px;", text: "Banner · 64:9 (~7.1:1) · same ratio as F1 · Permanent Marker H1 in chip backdrop · ratio chip baked in." },
    { dir: "right-up",   pos: "top:520px; left:24px;",  text: "Sticky icon rail · like / comment / save / share — follows the reader." },
    { dir: "left-down",  pos: "top:520px; right:60px;", text: "Expanded TOC · H2 + nested H3 entries · active item highlighted with coral bar (scroll-spy demo). Author-bio card moved inline into the article — no longer in sidebar." },
    { dir: "right-up",   pos: "top:780px; left:120px;", text: "Article typography matches F1: Permanent Marker for H2 displays, Quicksand bold for H3 sub-headings, Quicksand body." },
    { dir: "right-up",   pos: "top:1100px; left:120px;",text: "Pull-quote = the one decisive flourish. Marker font, coral side-bar, paper-2 fill." },
    { dir: "left-up",    pos: "top:1480px; right:60px;",text: "Composer shown to logged-in users; anon state stub shown below as dashed alt." },
    { dir: "right-down", pos: "top:2020px; left:60px;", text: "Related posts use a 4-col image-heavy rail (per client feedback: pictures > text on cards)." }
  ])}
</div>`;
}

function C5_mobile() {
  return `<div class="wf">
  ${NAV_MOBILE}

  <!-- Cinematic hero (16:9) -->
  <div class="section" style="padding:6px 0 10px;">
    <div style="position:relative;aspect-ratio:16/9;overflow:hidden;border:2px solid var(--line);border-radius:var(--radius-card);background:var(--placeholder);">
      <img src="${PHOTOS.cover2}" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"/>
      <div style="position:absolute;inset:0;background:linear-gradient(180deg, rgba(20,16,12,0.1) 30%, rgba(20,16,12,0.78) 100%);"></div>
      <div style="position:absolute;left:12px;right:12px;bottom:10px;color:#fff;">
        <span class="blog-tag" style="font-size:9px;background:rgba(255,255,255,0.92);color:var(--ink);">STUDIO NOTES</span>
        <h1 style="font-family:'Permanent Marker',cursive;font-size:22px;line-height:1.05;margin:4px 0 0;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,0.5);">Why I keep a colour journal</h1>
      </div>
    </div>
    <div class="row gap-sm" style="align-items:center;margin-top:8px;">
      ${avatar(PHOTOS.profile)}
      <div class="ts-body" style="font-size:11px;line-height:1.3;"><strong>Crystal Lau</strong><br/><span class="ts-meta">22 Apr · 8 min read</span></div>
    </div>
  </div>

  <!-- Article -->
  <div class="section" style="padding-top:0;">
    <p class="ts-body" style="font-size:15px;line-height:1.5;font-weight:500;">A little notebook of mixes I've tried. Not for anyone else — just for me.</p>
    <p class="ts-body" style="font-size:13px;line-height:1.6;margin-top:8px;">Every student asks the same question by week two: "how did you get that grey?". The honest answer is I don't remember unless I wrote it down.</p>

    <figure style="margin:12px 0;">
      <div style="border:2px solid var(--line);border-radius:var(--radius-card);overflow:hidden;aspect-ratio:3/2;">
        <img src="${PHOTOS.liquid}" alt="" style="width:100%;height:100%;object-fit:cover;display:block;"/>
      </div>
      <figcaption class="ts-meta" style="font-size:10px;margin-top:4px;text-align:center;font-style:italic;">Fig. 1 — Journal spread</figcaption>
    </figure>

    <blockquote style="margin:14px 0;padding:10px 14px;border-left:3px solid var(--coral,#e6776a);background:var(--paper-2,#efe8d8);border-radius:0 8px 8px 0;">
      <p style="font-family:'Permanent Marker',cursive;font-size:16px;line-height:1.3;margin:0;">"The journal isn't precious. It's a receipt."</p>
    </blockquote>

    <p class="ts-body" style="font-size:13px;">That's it. Three things on each page. The trap is making it too formal — if it takes more than a minute, you'll stop.</p>
  </div>

  <!-- Sticky-bottom engagement bar -->
  <div class="section" style="padding-top:6px;">
    <div class="row gap-sm" style="align-items:center;padding:8px;border:2px solid var(--line);border-radius:var(--radius-card);background:var(--paper);">
      <span class="wf-btn primary" style="font-size:11px;padding:5px 10px;">♡ 42</span>
      <span class="wf-btn" style="font-size:11px;padding:5px 10px;">💬 12</span>
      <span class="wf-btn ghost" style="font-size:11px;padding:5px 10px;">⤴</span>
      <span class="wf-btn ghost" style="font-size:11px;padding:5px 10px;margin-left:auto;">🔖</span>
    </div>
  </div>

  <!-- Comments preview -->
  <div class="section" style="padding-top:10px;">
    <h2 class="ts-h3" style="font-family:'Permanent Marker',cursive;font-size:18px;margin:0 0 6px;">Comments · 12</h2>
    <div class="card" style="padding:10px;">
      <div class="row gap-sm" style="align-items:flex-start;">
        ${avatar(COVERS[4])}
        <div class="grow">
          <div><strong style="font-size:12px;">Priya M.</strong> <span class="ts-meta" style="font-size:10px;">· 2h</span></div>
          <div class="ts-body" style="font-size:12px;line-height:1.45;margin-top:2px;">Started a journal after your Brighton workshop — saved me from buying premixed grey.</div>
        </div>
      </div>
    </div>
    <div class="center" style="padding:8px;"><span class="wf-btn ghost" style="font-size:11px;">View all 12 ↓</span></div>
  </div>

  <!-- Related (image-heavy 2-col) -->
  <div class="section" style="padding-top:10px;border-top:2px dashed var(--line-soft);">
    <div class="ts-meta">MORE STUDIO NOTES</div>
    <div class="grid-2" style="margin-top:6px;">
      ${POST_SET.slice(0, 4).map(p => `
      <article style="cursor:pointer;">
        <div style="aspect-ratio:4/3;border:1.5px solid var(--line);border-radius:var(--radius-card);overflow:hidden;">
          <img src="${p.src}" alt="" style="width:100%;height:100%;object-fit:cover;display:block;"/>
        </div>
        <h3 style="font-family:Quicksand,sans-serif;font-size:12px;line-height:1.25;margin:6px 0 2px;font-weight:600;">${p.t}</h3>
        <div class="ts-meta" style="font-size:9px;">${p.date}</div>
      </article>`).join('')}
    </div>
  </div>

  <!-- Newsletter REMOVED v0.10.25 · PRD §OUT excludes newsletter/mass email -->
</div>`;
}

/* ============================================================ */
/*  REGISTRY                                                    */
/* ============================================================ */

window.VARIANTS = {
  F1: {
    title: "Front · Classic Waterfall",
    caption: "The baseline Will-Kemp-style waterfall: the home page IS the blog — single-column feed of BLOG POSTS (some lesson-tagged, some studio notes), persistent right sidebar (About/Free/Tiers), Proko-style lesson carousel near the top.",
    desktop: F1_desktop, mobile: F1_mobile
  },
  L1: { title: "Lesson · Course Detail — Classic (sales page)", caption: "Pre-purchase. 1:1 cover + meta + CTA up top, full syllabus list, sticky side enrol card.", desktop: L1_desktop, mobile: L1_mobile },
  L2: { title: "Lesson · Course Detail — Preview-Led (sales page)", caption: "Pre-purchase. Big free preview video in a dark band, carousel doubles as syllabus.", desktop: L2_desktop, mobile: L2_mobile },
  L3: { title: "Lesson · Player — Cinema (post-purchase)", caption: "Post-purchase player. Big video, tabs below, lesson rail right.", desktop: L3_desktop, mobile: L3_mobile },
  L4: { title: "Lesson · Player — Study Desk (post-purchase)", caption: "Post-purchase player. 3-column: lessons / video + notes / materials + Discord.", desktop: L4_desktop, mobile: L4_mobile },
  A1: { title: "Account · Sign Up", caption: "Free account creation. Split hero photo + form. T&C checkbox gates submit. Email verification follows.", desktop: A1_desktop, mobile: A1_mobile },
  A2: { title: "Account · Log In", caption: "Centered auth card with Crystal's profile photo anchor, forgot-password link, social sign-in slot.", desktop: A2_desktop, mobile: A2_mobile },
  A3: { title: "Plans · Pricing", caption: "Three-tier pricing with Apprentice highlighted as 'most picked'. Plan cards + FAQ below.", desktop: A3_desktop, mobile: A3_mobile },
  A4: { title: "Account · Details / Dashboard", caption: "Post-login account page per PRD dashboard reqs. Left nav (Overview / Account details / My courses / Purchase history / Email prefs / Discord access / Privacy / Delete). Right = profile, password+2FA, billing, communication prefs (no marketing per PRD), GDPR data export + delete.", desktop: A6_desktop, mobile: A6_mobile },
  A5: { title: "Shop · Cart & Checkout", caption: "WooCommerce-style cart supporting multi-course checkout. Per-line tier switcher (Apprentice↔Fellow), coupon field, sticky order-summary, Stripe + PayPal. 3-step breadcrumb doubles as progress indicator.", desktop: A7_desktop, mobile: A7_mobile },
  C1: { title: "Content · Blog Menu / Index", caption: "The dedicated Blog MENU: category list + archive + tag cloud in a left rail, featured post at top, 3-col grid of latest posts. Since the homepage itself is already a blog feed, this page is for people who want the full archive, categories and search.", desktop: A4_desktop, mobile: A4_mobile },
  C2: { title: "Content · About · Contact · T&C", caption: "Combined page: About Crystal (photo + bio), Contact form + direct channels, Terms/Privacy cards + cookie banner pattern.", desktop: A5_desktop, mobile: A5_mobile },
  C3: { title: "Content · Gallery — Teacher's Work Showcase", caption: "Crystal's studio works in a Google-Photos-style 1:1 grid with adjustable images-per-row. Hover reveals VIEW (lightbox) and BUY (opens Etsy) over a black gradient with the classic-waterfall scale animation.", desktop: C3_desktop, mobile: C3_mobile },
  C4: { title: "Content · Gallery — Student Work Showcase", caption: "Selected student submissions in a mixed-orientation collage (portrait / A4 / landscape) where every row keeps a stable height (landscape items become wider, never taller). Hover reveals the student's name + avatar over a black gradient. Shares the C3 lightbox.", desktop: C4G_desktop, mobile: C4G_mobile },

  L5: { title: "Lesson · Courses Catalog (browse + filter)", caption: "PRD §1 course listing. Left filter rail (category / level / length) + 3-col grid of course cards. Cards link to L1/L2 sales page.", desktop: L5_desktop, mobile: L5_mobile },
  L6: { title: "Lesson · Paywall — locked state", caption: "PRD P0 free-preview → paywall. Blurred locked player + per-course tier picker (Apprentice ↔ Fellow) shown after the free intro lesson is finished.", desktop: L6_desktop, mobile: L6_mobile },
  D1: { title: "Dashboard · Post-login Overview", caption: "PRD §Dashboard. Continue-watching hero (resume video + progress bar), My Courses with progress, Recent Purchases table, Fellow Discord invite-code card (Q#14).", desktop: D1_desktop, mobile: D1_mobile },
  A6: { title: "Account · Order Confirmation / Receipt", caption: "Post-checkout success screen. Order summary, courses-unlocked CTA, receipt-email note, Fellow invite code revealed inline.", desktop: A6N_desktop, mobile: A6N_mobile },
  A7: { title: "Account · Email Verification", caption: "Two states stacked: check-inbox + verified-success. Resend with cooldown. Required by PRD P0 email-verification flow.", desktop: A7N_desktop, mobile: A7N_mobile },
  A8: { title: "Account · Forgot / Reset Password", caption: "Three states stacked: request, link-sent, set-new-password. Pairs with A1/A2/A7 to complete the auth quartet.", desktop: A8_desktop, mobile: A8_mobile },
  C5: { title: "Content · Blog Post Detail", caption: "PRD §1.4 blog post page. F1-matching 64:9 banner · sticky share rail · sticky multi-level TOC · pull quote · inline author bio · composer + threaded comments (logged-out alt state shown) · image-heavy related rail. Newsletter removed v0.10.25 (PRD §OUT).", desktop: C5_desktop, mobile: C5_mobile }
};
/* ============================================================
   Crystal's Art School — Wireframe app shell
   Tabs · compare strip · tweaks · variant rendering
   ============================================================ */

(function () {
  const stage = document.getElementById('stage');
  const tabsEl = document.getElementById('tabs');
  const stripEl = document.getElementById('compare-strip');
  const body = document.body;

  const order = ["F1", "F2", "F3", "F4", "F5", "L1", "L2", "L3", "L4", "L5", "L6", "A1", "A2", "A3", "A4", "A5", "A6", "A7", "A8", "D1", "C1", "C2", "C3", "C4"];

  // ----- Persist state (sandbox-safe) -----
  // v0.10.34 — storage key bumped so prior "annot: on" doesn't override new default.
  const STORE_KEY = 'wf-state-v2';
  function safeGet() {
    try { return JSON.parse(localStorage.getItem(STORE_KEY) || '{}'); }
    catch (e) { return {}; }
  }
  const saved = safeGet();
  let current = saved.current && window.VARIANTS[saved.current] ? saved.current : "F1";
  let viewport = saved.viewport || "desktop";
  let annot = saved.annot || "off"; /* v0.10.34 — default OFF; FAB toggles */
  body.dataset.viewport = viewport;
  body.dataset.annot = annot;

  function persist() {
    try { localStorage.setItem(STORE_KEY, JSON.stringify({ current, viewport, annot })); }
    catch (e) { /* sandboxed preview — ignore */ }
  }

  // ----- Render a single variant into the stage -----
  function renderVariant(key) {
    const v = window.VARIANTS[key];
    if (!v) return;
    current = key;

    const desktopHTML = v.desktop();
    const mobileHTML  = v.mobile();
    // Tablet: reuse desktop markup, just narrower frame
    const tabletHTML  = v.desktop();

    stage.innerHTML = `
      <div class="variant-header">
        <div>
          <div class="section-kicker" style="color:var(--muted);margin-bottom:2px;">${key.startsWith('F') ? 'FRONT PAGE' : key.startsWith('L') ? 'LESSON PAGE' : key.startsWith('C') ? 'CONTENT' : 'ACCOUNT'} · variant</div>
          <h2 class="variant-title">${v.title}</h2>
        </div>
        <div class="variant-caption">${v.caption}</div>
      </div>

      <div class="viewports">
        <div class="viewport desktop">
          <div class="wf-scroll">${desktopHTML}</div>
        </div>
        <div class="viewport tablet">
          <div class="wf-scroll" style="max-height:800px;">${tabletHTML}</div>
        </div>
        <div class="viewport mobile">
          <div class="wf-scroll" style="max-height:760px;">${mobileHTML}</div>
        </div>
      </div>
    `;

    // Stamp data-od-id on every descendant of each viewport's wireframe content.
    // Path format: <variant>.<viewport>.<tag>[.<tag>|<tag[i]>]…  (e.g. C3.d.header.div.input, F1.d.section[2].h2)
    tagVariantDescendants(key);

    // Justified gallery: pick columns from container width ÷ ideal tile size,
    // capped at item count, so each row fills edge-to-edge (no dead space on right).
    applyJustifiedGallery(stage);
    observeGalleryResize(stage);
    initF1HeroCarousel(stage);

    // Update tabs
    [...tabsEl.querySelectorAll('.tab')].forEach(t => {
      t.classList.toggle('active', t.dataset.variant === key);
    });
    // Update thumb strip
    [...stripEl.querySelectorAll('.thumb')].forEach(t => {
      t.classList.toggle('active', t.dataset.variant === key);
    });

    persist();
  }

  // ----- Runtime tagger: stamp deterministic structural IDs on variant descendants -----
  function tagVariantDescendants(key) {
    const vpMap = { desktop: 'd', tablet: 't', mobile: 'm' };
    stage.querySelectorAll('.viewport .wf-scroll').forEach(root => {
      const vp = root.closest('.viewport');
      const vpKey = vpMap[Object.keys(vpMap).find(k => vp.classList.contains(k))] || 'x';
      (function walk(el, path) {
        const kids = [...el.children];
        const counts = {};
        kids.forEach(c => { const t = c.tagName.toLowerCase(); counts[t] = (counts[t] || 0) + 1; });
        const seen = {};
        kids.forEach(c => {
          const t = c.tagName.toLowerCase();
          seen[t] = (seen[t] || 0) + 1;
          const part = counts[t] > 1 ? `${t}[${seen[t] - 1}]` : t;
          const next = path + '.' + part;
          c.setAttribute('data-od-id', next);
          walk(c, next);
        });
      })(root, `${key}.${vpKey}`);
    });
  }

  // ----- Justified gallery: explicit column count -----
  // Slider sets --gallery-cols on .gallery-wrap (an integer). Each .gallery-grid is laid
  // out as N equal 1fr tracks, capped at item count so a group of 4 in a 7-col setting
  // becomes 4 fat tiles edge-to-edge instead of leaving 3 empty tracks.
  function applyJustifiedGallery(root) {
    (root || document).querySelectorAll('.gallery-wrap').forEach(wrap => {
      const cols = Math.max(1, Math.round(parseFloat(getComputedStyle(wrap).getPropertyValue('--gallery-cols')) || 4));
      wrap.querySelectorAll('.gallery-grid').forEach(grid => {
        const items = grid.children.length;
        grid.style.gridTemplateColumns = `repeat(${Math.min(cols, items)}, 1fr)`;
      });
    });
    updateZoomReadout(root || document);
  }
  window.applyJustifiedGallery = applyJustifiedGallery;

  // ----- Zoom slider wiring (the slider lives in the masthead, the grid in a sibling .gallery-wrap) -----
  function zoomSliderHandle(input) {
    const scope = input.closest('.wf-scroll') || document;
    const wrap = scope.querySelector('.gallery-wrap');
    if (!wrap) return;
    wrap.style.setProperty('--gallery-cols', input.value);
    applyJustifiedGallery(scope);
  }
  function zoomSliderStep(btn, delta) {
    const input = btn.parentElement && btn.parentElement.querySelector('input[type=range]');
    if (!input) return;
    const next = Math.max(+input.min, Math.min(+input.max, (+input.value) + delta));
    if (next === +input.value) return;
    input.value = next;
    zoomSliderHandle(input);
  }
  function updateZoomReadout(scope) {
    (scope || document).querySelectorAll('.gallery-zoom').forEach(zoom => {
      const root = zoom.closest('.wf-scroll') || document;
      const wrap = root.querySelector('.gallery-wrap');
      if (!wrap) return;
      const input = zoom.querySelector('input[type=range]');
      const current = input ? +input.value : +(getComputedStyle(wrap).getPropertyValue('--gallery-cols') || 4);
      zoom.querySelectorAll('.col-stop').forEach(stop => {
        const isActive = +stop.dataset.cols === current;
        stop.classList.toggle('active', isActive);
        stop.style.background = isActive ? 'var(--coral, #e6776a)' : 'transparent';
        stop.style.color      = isActive ? '#fff' : 'var(--ink-mute)';
      });
    });
  }
  function zoomSliderJumpTo(btn, n) {
    const zoom = btn.closest('.gallery-zoom');
    const input = zoom && zoom.querySelector('input[type=range]');
    if (!input) return;
    input.value = n;
    zoomSliderHandle(input);
  }
  window.zoomSliderHandle = zoomSliderHandle;
  window.zoomSliderStep = zoomSliderStep;
  window.zoomSliderJumpTo = zoomSliderJumpTo;
  window.updateZoomReadout = updateZoomReadout;

  // ----- F1 hero carousel (Crafti vanilla-JS port) -----
  // Multiple .f1-hero instances may exist (desktop + tablet viewports both render F1).
  // Each maintains its own index. Autoplay 6s, pauses on hover, restarts after manual click.
  const _f1Timers = [];
  function initF1HeroCarousel(root) {
    try {
      _f1Timers.forEach(id => clearInterval(id));
      _f1Timers.length = 0;
      (root || document).querySelectorAll('.f1-hero').forEach(hero => {
        const slides = [...hero.querySelectorAll('.f1-hero__slide')];
        if (slides.length < 2) return;
        const current = hero.querySelector('.f1-hero__counter-current');
        const total = hero.querySelector('.f1-hero__counter-total');
        if (total) total.textContent = '/ ' + String(slides.length).padStart(2, '0');
        let idx = slides.findIndex(s => s.classList.contains('is-active'));
        if (idx < 0) idx = 0;

        function go(next) {
          slides[idx].classList.remove('is-active');
          // restart CSS animations by forcing reflow on the next slide
          const tgt = slides[(next + slides.length) % slides.length];
          // strip + reattach to retrigger keyframes
          tgt.querySelectorAll('.f1-hero__img--large, .f1-hero__img--small, .f1-hero__title, .f1-hero__subtitle, .f1-hero__cta').forEach(el => {
            el.style.animation = 'none';
            // eslint-disable-next-line no-unused-expressions
            void el.offsetWidth;
            el.style.animation = '';
          });
          tgt.classList.add('is-active');
          idx = (next + slides.length) % slides.length;
          if (current) current.textContent = String(idx + 1).padStart(2, '0');
        }

        const prevBtn = hero.querySelector('.f1-hero__arrow--prev');
        const nextBtn = hero.querySelector('.f1-hero__arrow--next');
        const scrollBtn = hero.querySelector('.f1-hero__scroll');
        if (prevBtn) prevBtn.addEventListener('click', () => { go(idx - 1); resetTimer(); });
        if (nextBtn) nextBtn.addEventListener('click', () => { go(idx + 1); resetTimer(); });
        if (scrollBtn) scrollBtn.addEventListener('click', () => {
          const next = hero.nextElementSibling;
          if (next && next.scrollIntoView) {
            // avoid scrollIntoView per project rule — use parent scroll instead
            const scroller = hero.closest('.wf-scroll');
            if (scroller) scroller.scrollTop = next.offsetTop;
          }
        });

        let timer = null;
        function resetTimer() {
          if (timer) clearInterval(timer);
          timer = setInterval(() => go(idx + 1), 6000);
          _f1Timers.push(timer);
        }
        hero.addEventListener('mouseenter', () => { if (timer) clearInterval(timer); });
        hero.addEventListener('mouseleave', resetTimer);
        resetTimer();
      });
    } catch (e) { /* sandboxed preview — ignore */ }
  }
  window.initF1HeroCarousel = initF1HeroCarousel;

  let _galleryRO = null;
  function observeGalleryResize(root) {
    if (_galleryRO) _galleryRO.disconnect();
    if (typeof ResizeObserver === 'undefined') return;
    _galleryRO = new ResizeObserver(() => applyJustifiedGallery(root));
    (root || document).querySelectorAll('.gallery-grid').forEach(g => _galleryRO.observe(g));
  }

  // ----- Build compare strip -----
  // Show the 4 variants in the same group as current (F1-4 or L1-4)
  function rebuildCompareStrip() {
    const groups = {
      F: ['F1','F2','F3','F4','F5'],
      L: ['L1','L2','L3','L4','L5','L6'],
      A: ['A1','A2','A3','A4','A5','A6','A7','A8'],
      D: ['D1'],
      C: ['C1','C2','C3','C4','C5']
    };
    const group = groups[current[0]] || groups.F;
    stripEl.innerHTML = group.filter(k => window.VARIANTS[k]).map(k => {
      const v = window.VARIANTS[k];
      return `
        <button class="thumb ${k===current?'active':''}" data-variant="${k}">
          <div class="thumb-label"><span>${v.title}</span><small>${k}</small></div>
          ${miniThumb(k)}
        </button>
      `;
    }).join('');
    [...stripEl.querySelectorAll('.thumb')].forEach(t => {
      t.addEventListener('click', () => {
        renderVariant(t.dataset.variant);
      });
    });
  }

  // SVG mini thumbnail schematics for each variant
  function miniThumb(key) {
    // 200 x 130 viewBox schematics, flat b/w with one accent
    const accent = "#96D3D9";
    const coral  = "#F59595";
    const ink    = "#15130A";
    const paper  = "#FBF9F2";

    const schematics = {
      F1: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}" stroke-width="1"/>
        <rect x="6" y="22" width="110" height="38" fill="${paper}" stroke="${ink}"/>
        <rect x="120" y="22" width="36" height="18" fill="${accent}" stroke="${ink}"/>
        <rect x="158" y="22" width="36" height="18" fill="${paper}" stroke="${ink}"/>
        <rect x="120" y="42" width="36" height="18" fill="${paper}" stroke="${ink}"/>
        <rect x="158" y="42" width="36" height="18" fill="${paper}" stroke="${ink}"/>
        <rect x="6" y="66" width="188" height="26" fill="${ink}"/>
        <rect x="14" y="72" width="30" height="14" fill="${paper}"/>
        <rect x="48" y="72" width="30" height="14" fill="${paper}"/>
        <rect x="82" y="72" width="30" height="14" fill="${paper}"/>
        <rect x="6" y="96" width="60" height="28" fill="${paper}" stroke="${ink}"/>
        <rect x="70" y="96" width="60" height="28" fill="${paper}" stroke="${ink}"/>
        <rect x="134" y="96" width="60" height="28" fill="${paper}" stroke="${ink}"/>
      `,
      F2: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="20" width="188" height="44" fill="${ink}"/>
        <polygon points="48,32 48,52 72,42" fill="${paper}"/>
        <rect x="130" y="30" width="58" height="6" fill="${paper}"/>
        <rect x="130" y="40" width="40" height="4" fill="${accent}"/>
        <rect x="130" y="48" width="50" height="8" fill="${accent}" stroke="${ink}"/>
        <rect x="6" y="70" width="188" height="22" fill="${ink}"/>
        <rect x="12" y="74" width="24" height="14" fill="${paper}"/>
        <rect x="40" y="74" width="24" height="14" fill="${paper}"/>
        <rect x="68" y="74" width="24" height="14" fill="${paper}"/>
        <rect x="6" y="96" width="44" height="28" fill="${paper}" stroke="${ink}"/>
        <rect x="54" y="96" width="44" height="28" fill="${paper}" stroke="${ink}"/>
        <rect x="102" y="96" width="44" height="28" fill="${paper}" stroke="${ink}"/>
        <rect x="150" y="96" width="44" height="28" fill="${paper}" stroke="${ink}"/>
      `,
      F3: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="22" width="60" height="80" fill="${paper}" stroke="${ink}"/>
        <text x="36" y="66" text-anchor="middle" font-size="8" font-family="Kalam" fill="${ink}">portrait</text>
        <rect x="72" y="22" width="122" height="6" fill="${ink}"/>
        <rect x="72" y="32" width="98" height="10" fill="${ink}"/>
        <rect x="72" y="46" width="122" height="4" fill="${ink}" opacity="0.4"/>
        <rect x="72" y="54" width="122" height="4" fill="${ink}" opacity="0.4"/>
        <rect x="72" y="64" width="122" height="24" fill="${coral}" opacity="0.25" stroke="${coral}" stroke-dasharray="2 2"/>
        <text x="78" y="78" font-size="7" font-family="Kalam" fill="${ink}">Studio Notes</text>
        <rect x="72" y="92" width="40" height="10" fill="${accent}" stroke="${ink}"/>
        <rect x="6" y="108" width="188" height="16" fill="${paper}" stroke="${ink}"/>
      `,
      F4: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="20" width="122" height="28" fill="${paper}" stroke="${ink}"/>
        <rect x="12" y="24" width="18" height="18" fill="${paper}" stroke="${ink}"/>
        <rect x="34" y="24" width="88" height="4" fill="${ink}"/>
        <rect x="34" y="32" width="70" height="4" fill="${ink}" opacity="0.4"/>
        <rect x="34" y="39" width="20" height="6" fill="${accent}" stroke="${ink}"/>
        <rect x="6" y="52" width="122" height="28" fill="${paper}" stroke="${ink}"/>
        <rect x="12" y="56" width="18" height="18" fill="${paper}" stroke="${ink}"/>
        <rect x="34" y="56" width="88" height="4" fill="${ink}"/>
        <rect x="34" y="64" width="70" height="4" fill="${ink}" opacity="0.4"/>
        <rect x="6" y="84" width="122" height="28" fill="${paper}" stroke="${ink}"/>
        <rect x="12" y="88" width="18" height="18" fill="${paper}" stroke="${ink}"/>
        <rect x="34" y="88" width="88" height="4" fill="${ink}"/>
        <rect x="132" y="20" width="62" height="34" fill="${paper}" stroke="${ink}"/>
        <rect x="132" y="58" width="62" height="30" fill="${coral}" opacity="0.35" stroke="${ink}"/>
        <rect x="132" y="92" width="62" height="20" fill="${paper}" stroke="${ink}"/>
      `,
      L1: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="20" width="60" height="60" fill="${paper}" stroke="${ink}"/>
        <text x="36" y="54" text-anchor="middle" font-size="7" font-family="Kalam" fill="${ink}">1:1</text>
        <rect x="72" y="22" width="100" height="8" fill="${ink}"/>
        <rect x="72" y="34" width="80" height="4" fill="${ink}" opacity="0.4"/>
        <rect x="72" y="42" width="80" height="4" fill="${ink}" opacity="0.4"/>
        <rect x="72" y="52" width="40" height="10" fill="${accent}" stroke="${ink}"/>
        <rect x="114" y="52" width="40" height="10" fill="${paper}" stroke="${ink}"/>
        <rect x="6" y="86" width="128" height="10" fill="${paper}" stroke="${ink}"/>
        <rect x="6" y="100" width="128" height="10" fill="${paper}" stroke="${ink}"/>
        <rect x="6" y="114" width="128" height="10" fill="${paper}" stroke="${ink}"/>
        <rect x="140" y="86" width="54" height="38" fill="${paper}" stroke="${ink}" stroke-width="1.5"/>
        <rect x="146" y="110" width="42" height="8" fill="${accent}" stroke="${ink}"/>
      `,
      L2: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="20" width="188" height="50" fill="${ink}"/>
        <rect x="12" y="26" width="112" height="38" fill="${paper}" opacity="0.25" stroke="${paper}"/>
        <polygon points="58,36 58,54 78,45" fill="${paper}"/>
        <rect x="130" y="26" width="58" height="6" fill="${paper}"/>
        <rect x="130" y="36" width="40" height="4" fill="${paper}" opacity="0.6"/>
        <rect x="130" y="48" width="58" height="10" fill="${coral}" stroke="${ink}"/>
        <rect x="6" y="76" width="188" height="22" fill="${ink}"/>
        <rect x="12" y="80" width="32" height="14" fill="${paper}"/>
        <rect x="48" y="80" width="32" height="14" fill="${accent}"/>
        <rect x="84" y="80" width="32" height="14" fill="${paper}"/>
        <rect x="120" y="80" width="32" height="14" fill="${paper}"/>
        <rect x="6" y="102" width="188" height="22" fill="${paper}" stroke="${ink}"/>
      `,
      L3: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="20" width="188" height="60" fill="${ink}"/>
        <polygon points="86,40 86,62 110,51" fill="${paper}"/>
        <rect x="14" y="68" width="80" height="3" fill="${accent}"/>
        <rect x="14" y="74" width="170" height="2" fill="${paper}" opacity="0.3"/>
        <rect x="6" y="86" width="128" height="38" fill="${paper}" stroke="${ink}"/>
        <rect x="12" y="92" width="40" height="4" fill="${ink}"/>
        <rect x="12" y="100" width="80" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="12" y="106" width="80" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="12" y="112" width="30" height="8" fill="${accent}" stroke="${ink}"/>
        <rect x="140" y="86" width="54" height="38" fill="${paper}" stroke="${ink}"/>
        <rect x="144" y="90" width="46" height="6" fill="${accent}" opacity="0.5"/>
        <rect x="144" y="100" width="46" height="6" fill="${paper}" stroke="${ink}"/>
        <rect x="144" y="110" width="46" height="6" fill="${paper}" stroke="${ink}"/>
      `,
      L4: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="20" width="42" height="104" fill="${paper}" stroke="${ink}"/>
        <rect x="10" y="24" width="34" height="10" fill="${accent}" opacity="0.6" stroke="${ink}"/>
        <rect x="10" y="38" width="34" height="10" fill="${paper}" stroke="${ink}"/>
        <rect x="10" y="52" width="34" height="10" fill="${paper}" stroke="${ink}"/>
        <rect x="10" y="66" width="34" height="10" fill="${paper}" stroke="${ink}"/>
        <rect x="52" y="20" width="98" height="44" fill="${ink}"/>
        <polygon points="92,32 92,52 112,42" fill="${paper}"/>
        <rect x="52" y="68" width="98" height="22" fill="${coral}" opacity="0.25" stroke="${coral}" stroke-dasharray="2 2"/>
        <text x="58" y="80" font-size="7" font-family="Kalam" fill="${ink}">lesson</text>
        <rect x="52" y="94" width="98" height="30" fill="${paper}" stroke="${ink}"/>
        <rect x="154" y="20" width="40" height="44" fill="${paper}" stroke="${ink}"/>
        <rect x="154" y="68" width="40" height="22" fill="${accent}" opacity="0.35" stroke="${ink}"/>
        <rect x="154" y="94" width="40" height="30" fill="${paper}" stroke="${ink}"/>
      `,
      F5: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="22" width="60" height="40" fill="${accent}" opacity="0.6" stroke="${ink}"/>
        <rect x="70" y="22" width="60" height="40" fill="${coral}" opacity="0.35" stroke="${ink}"/>
        <rect x="134" y="22" width="60" height="40" fill="${paper}" stroke="${ink}"/>
        <rect x="6" y="66" width="60" height="40" fill="${paper}" stroke="${ink}"/>
        <rect x="70" y="66" width="60" height="40" fill="${accent}" opacity="0.4" stroke="${ink}"/>
        <rect x="134" y="66" width="60" height="40" fill="${paper}" stroke="${ink}"/>
        <rect x="6" y="110" width="188" height="4" fill="${ink}" opacity="0.2"/>
        <rect x="6" y="118" width="60" height="6" fill="${ink}" opacity="0.6"/>
        <rect x="70" y="118" width="40" height="6" fill="${ink}" opacity="0.3"/>
      `,
      A1: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="20" width="90" height="104" fill="${accent}" opacity="0.4" stroke="${ink}"/>
        <circle cx="51" cy="56" r="14" fill="${paper}" stroke="${ink}"/>
        <rect x="30" y="78" width="42" height="3" fill="${ink}"/>
        <rect x="30" y="86" width="32" height="3" fill="${ink}" opacity="0.5"/>
        <rect x="100" y="22" width="94" height="8" fill="${ink}"/>
        <rect x="100" y="36" width="70" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="100" y="50" width="94" height="12" fill="${paper}" stroke="${ink}"/>
        <rect x="100" y="66" width="94" height="12" fill="${paper}" stroke="${ink}"/>
        <rect x="100" y="82" width="94" height="12" fill="${paper}" stroke="${ink}"/>
        <rect x="100" y="98" width="8" height="8" fill="${paper}" stroke="${ink}"/>
        <rect x="112" y="100" width="60" height="3" fill="${ink}" opacity="0.6"/>
        <rect x="100" y="112" width="94" height="10" fill="${coral}" stroke="${ink}"/>
      `,
      A2: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="50" y="28" width="100" height="88" fill="${paper}" stroke="${ink}" stroke-width="1.5"/>
        <circle cx="100" cy="46" r="10" fill="${accent}" stroke="${ink}"/>
        <rect x="62" y="64" width="76" height="10" fill="${paper}" stroke="${ink}"/>
        <rect x="62" y="78" width="76" height="10" fill="${paper}" stroke="${ink}"/>
        <rect x="62" y="92" width="76" height="10" fill="${accent}" stroke="${ink}"/>
        <rect x="62" y="106" width="50" height="4" fill="${ink}" opacity="0.4"/>
      `,
      A3: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="10" y="30" width="56" height="84" fill="${paper}" stroke="${ink}"/>
        <rect x="70" y="24" width="60" height="96" fill="${coral}" opacity="0.3" stroke="${ink}" stroke-width="2"/>
        <rect x="134" y="30" width="56" height="84" fill="${paper}" stroke="${ink}"/>
        <rect x="18" y="40" width="40" height="5" fill="${ink}"/>
        <rect x="18" y="54" width="30" height="10" fill="${ink}"/>
        <rect x="18" y="78" width="40" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="18" y="86" width="40" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="18" y="94" width="40" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="78" y="34" width="44" height="5" fill="${ink}"/>
        <rect x="78" y="48" width="30" height="12" fill="${ink}"/>
        <rect x="78" y="72" width="44" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="78" y="80" width="44" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="78" y="88" width="44" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="78" y="100" width="44" height="10" fill="${accent}" stroke="${ink}"/>
        <rect x="142" y="40" width="40" height="5" fill="${ink}"/>
        <rect x="142" y="54" width="30" height="10" fill="${ink}"/>
        <rect x="142" y="78" width="40" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="142" y="86" width="40" height="3" fill="${ink}" opacity="0.4"/>
      `,
      C1: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="20" width="44" height="100" fill="${paper}" stroke="${ink}"/>
        <rect x="10" y="26" width="36" height="6" fill="${accent}" stroke="${ink}"/>
        <rect x="10" y="36" width="36" height="4" fill="${ink}" opacity="0.5"/>
        <rect x="10" y="44" width="36" height="4" fill="${ink}" opacity="0.5"/>
        <rect x="10" y="52" width="36" height="4" fill="${ink}" opacity="0.5"/>
        <rect x="10" y="62" width="36" height="3" fill="${ink}" opacity="0.3"/>
        <rect x="10" y="70" width="36" height="3" fill="${ink}" opacity="0.3"/>
        <rect x="10" y="84" width="8" height="8" fill="${coral}" opacity="0.5"/>
        <rect x="20" y="84" width="8" height="8" fill="${accent}" opacity="0.5"/>
        <rect x="30" y="84" width="8" height="8" fill="${paper}" stroke="${ink}"/>
        <rect x="54" y="20" width="140" height="38" fill="${ink}"/>
        <rect x="60" y="36" width="80" height="4" fill="${paper}"/>
        <rect x="60" y="44" width="60" height="3" fill="${paper}" opacity="0.6"/>
        <rect x="54" y="62" width="44" height="30" fill="${paper}" stroke="${ink}"/>
        <rect x="102" y="62" width="44" height="30" fill="${paper}" stroke="${ink}"/>
        <rect x="150" y="62" width="44" height="30" fill="${paper}" stroke="${ink}"/>
        <rect x="54" y="96" width="44" height="26" fill="${paper}" stroke="${ink}"/>
        <rect x="102" y="96" width="44" height="26" fill="${paper}" stroke="${ink}"/>
        <rect x="150" y="96" width="44" height="26" fill="${paper}" stroke="${ink}"/>
      `,
      C2: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="20" width="80" height="46" fill="${accent}" opacity="0.4" stroke="${ink}"/>
        <circle cx="46" cy="42" r="12" fill="${paper}" stroke="${ink}"/>
        <rect x="90" y="22" width="104" height="6" fill="${ink}"/>
        <rect x="90" y="32" width="80" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="90" y="40" width="80" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="90" y="48" width="80" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="90" y="56" width="40" height="8" fill="${coral}" stroke="${ink}"/>
        <rect x="6" y="72" width="120" height="48" fill="${paper}" stroke="${ink}"/>
        <rect x="14" y="78" width="100" height="6" fill="${ink}" opacity="0.2"/>
        <rect x="14" y="88" width="100" height="10" fill="${paper}" stroke="${ink}"/>
        <rect x="14" y="102" width="100" height="10" fill="${paper}" stroke="${ink}"/>
        <rect x="132" y="72" width="62" height="22" fill="${paper}" stroke="${ink}"/>
        <rect x="132" y="98" width="62" height="22" fill="${paper}" stroke="${ink}"/>
      `,
      A4: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="20" width="46" height="104" fill="${paper}" stroke="${ink}"/>
        <rect x="10" y="26" width="38" height="8" fill="${accent}" opacity="0.7" stroke="${ink}"/>
        <rect x="10" y="38" width="38" height="5" fill="${ink}" opacity="0.4"/>
        <rect x="10" y="46" width="38" height="5" fill="${ink}" opacity="0.4"/>
        <rect x="10" y="54" width="38" height="5" fill="${ink}" opacity="0.4"/>
        <rect x="10" y="62" width="38" height="5" fill="${ink}" opacity="0.4"/>
        <rect x="10" y="70" width="38" height="5" fill="${ink}" opacity="0.4"/>
        <rect x="10" y="112" width="38" height="8" fill="${coral}" opacity="0.6" stroke="${ink}"/>
        <rect x="56" y="20" width="138" height="30" fill="${paper}" stroke="${ink}"/>
        <circle cx="74" cy="35" r="10" fill="${accent}" opacity="0.6" stroke="${ink}"/>
        <rect x="90" y="26" width="90" height="4" fill="${ink}"/>
        <rect x="90" y="34" width="70" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="90" y="42" width="70" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="56" y="56" width="138" height="22" fill="${paper}" stroke="${ink}"/>
        <rect x="62" y="64" width="60" height="4" fill="${ink}"/>
        <rect x="170" y="62" width="20" height="10" fill="${accent}" stroke="${ink}"/>
        <rect x="56" y="84" width="138" height="18" fill="${paper}" stroke="${ink}"/>
        <rect x="56" y="108" width="138" height="16" fill="${paper}" stroke="${ink}" stroke-dasharray="2 2"/>
      `,
      A5: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="20" width="120" height="4" fill="${ink}" opacity="0.3"/>
        <rect x="6" y="30" width="120" height="22" fill="${paper}" stroke="${ink}"/>
        <rect x="10" y="34" width="14" height="14" fill="${accent}" opacity="0.5" stroke="${ink}"/>
        <rect x="28" y="36" width="40" height="4" fill="${ink}"/>
        <rect x="28" y="44" width="30" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="102" y="36" width="20" height="10" fill="${paper}" stroke="${ink}"/>
        <rect x="6" y="56" width="120" height="22" fill="${paper}" stroke="${ink}"/>
        <rect x="10" y="60" width="14" height="14" fill="${coral}" opacity="0.5" stroke="${ink}"/>
        <rect x="28" y="62" width="40" height="4" fill="${ink}"/>
        <rect x="28" y="70" width="30" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="102" y="62" width="20" height="10" fill="${paper}" stroke="${ink}"/>
        <rect x="6" y="82" width="120" height="22" fill="${paper}" stroke="${ink}"/>
        <rect x="10" y="86" width="14" height="14" fill="${accent}" opacity="0.35" stroke="${ink}"/>
        <rect x="28" y="88" width="40" height="4" fill="${ink}"/>
        <rect x="132" y="30" width="62" height="80" fill="${paper}" stroke="${ink}" stroke-width="2"/>
        <rect x="138" y="36" width="50" height="4" fill="${ink}"/>
        <rect x="138" y="46" width="50" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="138" y="54" width="50" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="138" y="64" width="50" height="3" fill="${ink}" opacity="0.4"/>
        <rect x="138" y="74" width="50" height="8" fill="${ink}"/>
        <rect x="138" y="88" width="50" height="14" fill="${coral}" stroke="${ink}"/>
      `,
      C3: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <rect x="6" y="20" width="38" height="100" fill="${paper}" stroke="${ink}"/>
        <rect x="10" y="26" width="30" height="4" fill="${ink}" opacity="0.5"/>
        <rect x="10" y="34" width="30" height="3" fill="${ink}" opacity="0.3"/>
        <rect x="10" y="40" width="30" height="3" fill="${ink}" opacity="0.3"/>
        <rect x="10" y="46" width="30" height="3" fill="${ink}" opacity="0.3"/>
        <rect x="10" y="54" width="30" height="3" fill="${ink}" opacity="0.3"/>
        <rect x="10" y="66" width="30" height="4" fill="${accent}" opacity="0.6" stroke="${ink}"/>
        <rect x="10" y="76" width="30" height="3" fill="${ink}" opacity="0.3"/>
        <rect x="10" y="82" width="30" height="3" fill="${ink}" opacity="0.3"/>
        <rect x="48" y="20" width="36" height="44" fill="${accent}" opacity="0.5" stroke="${ink}"/>
        <rect x="48" y="68" width="36" height="32" fill="${coral}" opacity="0.35" stroke="${ink}"/>
        <rect x="48" y="104" width="36" height="20" fill="${paper}" stroke="${ink}"/>
        <rect x="88" y="20" width="36" height="32" fill="${paper}" stroke="${ink}"/>
        <rect x="88" y="56" width="36" height="44" fill="${accent}" opacity="0.35" stroke="${ink}"/>
        <rect x="88" y="104" width="36" height="20" fill="${coral}" opacity="0.5" stroke="${ink}"/>
        <rect x="128" y="20" width="36" height="24" fill="${coral}" opacity="0.4" stroke="${ink}"/>
        <rect x="128" y="48" width="36" height="40" fill="${paper}" stroke="${ink}"/>
        <rect x="128" y="92" width="36" height="32" fill="${accent}" opacity="0.5" stroke="${ink}"/>
        <rect x="168" y="20" width="26" height="36" fill="${paper}" stroke="${ink}"/>
        <rect x="168" y="60" width="26" height="28" fill="${coral}" opacity="0.35" stroke="${ink}"/>
        <rect x="168" y="92" width="26" height="32" fill="${accent}" opacity="0.45" stroke="${ink}"/>
      `,
      C4: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <!-- masthead -->
        <rect x="6" y="20" width="80" height="6" fill="${ink}"/>
        <rect x="6" y="30" width="120" height="3" fill="${ink}" opacity="0.4"/>
        <!-- mixed-orientation collage · row 1 (stable height = 28) -->
        <rect x="6" y="40" width="30" height="28" fill="${accent}" opacity="0.55" stroke="${ink}"/>
        <rect x="38" y="40" width="42" height="28" fill="${coral}" opacity="0.4" stroke="${ink}"/>
        <rect x="82" y="40" width="30" height="28" fill="${paper}" stroke="${ink}"/>
        <rect x="114" y="40" width="38" height="28" fill="${accent}" opacity="0.4" stroke="${ink}"/>
        <rect x="154" y="40" width="40" height="28" fill="${coral}" opacity="0.5" stroke="${ink}"/>
        <!-- row 2 (stable height = 30) -->
        <rect x="6" y="70" width="44" height="30" fill="${coral}" opacity="0.35" stroke="${ink}"/>
        <rect x="52" y="70" width="24" height="30" fill="${accent}" opacity="0.6" stroke="${ink}"/>
        <rect x="78" y="70" width="40" height="30" fill="${paper}" stroke="${ink}"/>
        <rect x="120" y="70" width="32" height="30" fill="${accent}" opacity="0.45" stroke="${ink}"/>
        <rect x="154" y="70" width="40" height="30" fill="${coral}" opacity="0.4" stroke="${ink}"/>
        <!-- student name overlay hint on one tile -->
        <rect x="52" y="92" width="24" height="8" fill="${ink}" opacity="0.7"/>
        <circle cx="58" cy="96" r="2" fill="${paper}"/>
        <!-- row 3 -->
        <rect x="6" y="102" width="38" height="22" fill="${paper}" stroke="${ink}"/>
        <rect x="46" y="102" width="28" height="22" fill="${accent}" opacity="0.5" stroke="${ink}"/>
        <rect x="76" y="102" width="48" height="22" fill="${coral}" opacity="0.35" stroke="${ink}"/>
        <rect x="126" y="102" width="32" height="22" fill="${paper}" stroke="${ink}"/>
        <rect x="160" y="102" width="34" height="22" fill="${accent}" opacity="0.6" stroke="${ink}"/>
      `,
      C5: `
        <rect x="0" y="0" width="200" height="130" fill="${paper}"/>
        <rect x="6" y="6" width="188" height="10" fill="none" stroke="${ink}"/>
        <!-- breadcrumb -->
        <rect x="6" y="20" width="60" height="3" fill="${ink}" opacity="0.4"/>
        <!-- cinematic 16:9 hero -->
        <rect x="6" y="26" width="188" height="34" fill="${ink}"/>
        <rect x="10" y="48" width="50" height="3" fill="${accent}"/>
        <rect x="10" y="53" width="120" height="5" fill="${paper}"/>
        <circle cx="13" cy="57" r="2" fill="${paper}" opacity="0.8"/>
        <!-- 3-col body: share rail · article · TOC -->
        <rect x="6" y="64" width="10" height="50" fill="${paper}" stroke="${ink}"/>
        <rect x="8" y="66" width="6" height="6" fill="${coral}" opacity="0.6"/>
        <rect x="8" y="74" width="6" height="6" fill="${paper}" stroke="${ink}"/>
        <rect x="8" y="82" width="6" height="6" fill="${paper}" stroke="${ink}"/>
        <!-- article column -->
        <rect x="20" y="64" width="120" height="3" fill="${ink}"/>
        <rect x="20" y="70" width="120" height="2" fill="${ink}" opacity="0.4"/>
        <rect x="20" y="75" width="100" height="2" fill="${ink}" opacity="0.4"/>
        <rect x="20" y="80" width="120" height="14" fill="${paper}" stroke="${ink}"/>
        <rect x="20" y="97" width="120" height="2" fill="${ink}" opacity="0.4"/>
        <!-- pull quote -->
        <rect x="20" y="102" width="120" height="10" fill="${coral}" opacity="0.25" stroke="${coral}" stroke-dasharray="2 2"/>
        <rect x="20" y="102" width="2" height="10" fill="${coral}"/>
        <!-- engagement bar -->
        <rect x="20" y="115" width="120" height="6" fill="${paper}" stroke="${ink}"/>
        <rect x="22" y="116" width="14" height="4" fill="${accent}"/>
        <!-- TOC column -->
        <rect x="144" y="64" width="50" height="22" fill="${paper}" stroke="${ink}"/>
        <rect x="148" y="68" width="20" height="2" fill="${ink}" opacity="0.5"/>
        <rect x="146" y="72" width="2" height="3" fill="${coral}"/>
        <rect x="150" y="72" width="30" height="2" fill="${ink}"/>
        <rect x="150" y="76" width="30" height="2" fill="${ink}" opacity="0.4"/>
        <rect x="150" y="80" width="30" height="2" fill="${ink}" opacity="0.4"/>
        <rect x="144" y="88" width="50" height="14" fill="${paper}" stroke="${ink}"/>
        <rect x="144" y="104" width="50" height="14" fill="${accent}" opacity="0.4" stroke="${ink}"/>
        <!-- related posts strip -->
        <rect x="6" y="121" width="44" height="6" fill="${paper}" stroke="${ink}"/>
        <rect x="52" y="121" width="44" height="6" fill="${paper}" stroke="${ink}"/>
        <rect x="98" y="121" width="44" height="6" fill="${paper}" stroke="${ink}"/>
      `,
    };
    return `<svg viewBox="0 0 200 130" xmlns="http://www.w3.org/2000/svg">${schematics[key] || ''}</svg>`;
  }

  // ----- Tab clicks -----
  [...tabsEl.querySelectorAll('.tab')].forEach(t => {
    t.addEventListener('click', () => {
      const k = t.dataset.variant;
      renderVariant(k);
      rebuildCompareStrip();
    });
  });

  // ----- Tweaks (viewport, annotations) -----
  document.querySelectorAll('.vp-chip').forEach(b => {
    b.addEventListener('click', () => {
      viewport = b.dataset.vp;
      body.dataset.viewport = viewport;
      document.querySelectorAll('.vp-chip').forEach(x => x.classList.toggle('active', x === b));
      persist();
    });
  });
  document.querySelectorAll('.annot-chip').forEach(b => {
    b.addEventListener('click', () => {
      annot = b.dataset.annot;
      body.dataset.annot = annot;
      document.querySelectorAll('.annot-chip').forEach(x => x.classList.toggle('active', x === b));
      persist();
    });
  });
  // Sync initial chip state from saved
  document.querySelectorAll('.vp-chip').forEach(b => b.classList.toggle('active', b.dataset.vp === viewport));
  document.querySelectorAll('.annot-chip').forEach(b => b.classList.toggle('active', b.dataset.annot === annot));

  // ----- Floating annotation toggle (FAB) — v0.10.34 -----
  const fab = document.getElementById('annot-fab');
  if (fab) {
    fab.addEventListener('click', () => {
      annot = (annot === 'on') ? 'off' : 'on';
      body.dataset.annot = annot;
      document.querySelectorAll('.annot-chip').forEach(x =>
        x.classList.toggle('active', x.dataset.annot === annot));
      persist();
    });
  }

  // ----- Carousel arrow wiring — v0.10.34 (rail scrollbar removed) -----
  // Event delegation: any .slideshow .arrow click scrolls its sibling .rail
  // by ~one card width. Arrows are the only usable carousel control.
  document.addEventListener('click', (e) => {
    const arrow = e.target.closest('.slideshow .arrow');
    if (!arrow) return;
    const slideshow = arrow.closest('.slideshow');
    if (!slideshow) return;
    const rail = slideshow.querySelector('.rail');
    if (!rail) return;
    const card = rail.querySelector('.slide');
    const step = (card ? card.getBoundingClientRect().width : 240) + 18; /* card + gap */
    const dir = arrow.dataset.dir === 'prev' ? -1 : 1;
    rail.scrollBy({ left: step * dir, behavior: 'smooth' });
  });

  // ----- Keyboard nav -----
  window.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
      const idx = order.indexOf(current);
      if (idx < 0) return;
      const next = e.key === 'ArrowRight'
        ? order[(idx + 1) % order.length]
        : order[(idx - 1 + order.length) % order.length];
      renderVariant(next);
      rebuildCompareStrip();
      e.preventDefault();
    }
  });

  // ----- Initial render -----
  renderVariant(current);
  rebuildCompareStrip();
})();
