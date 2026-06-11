# Crystal's Art School — Design System v2.1

> v2.0 (2026-06-11) — graduates the wireframe system (v1.2) into the production
> design system for the real multi-page site. Supersedes the tokens embedded in
> `wireframes/styles-v5.css`. Source of visual truth for `index.html`,
> `lessons.html`, `lesson.html`, `gallery.html`, `blog.html`, `blog-post.html`,
> `about.html`.
> v2.1 (2026-06-11) — adds §1.1 paper grain (site-wide canvas feel), respecifies
> the watercolor cursor trail (§4), adds the free-lesson detail page (§5, §7).

## 1. Palette — pastel only

All page surfaces and components draw from this list. Non-pastel values are
allowed **only** where listed under "deep anchors" (brand-justified contrast).

| Token | Value | Use |
|---|---|---|
| `--cream` | `#FCFAED` | page background (left hero tone) |
| `--paper-2` | `#F3EFE3` | card / accent panel (right hero tone) |
| `--warm-white` | `#F5DDDA` | blush-tinted card surfaces |
| `--blush` | `#E5C1B3` | warm accents, hover tints |
| `--teal` | `#96D3D9` | brand accent (Teal Mist), highlights, hover |
| `--mint` | `#C5FAEE` | Fellow-tier / success tint |
| `--coral` | `#F59595` | **primary action** (Add to cart, watch CTA, cart badge) |
| `--sand` | `#D69C85` | warm secondary accent |

Deep anchors (use sparingly, never as page wash):

| Token | Value | Use |
|---|---|---|
| `--ink` | `#15130A` | text, line-work, footer text |
| `--forest` | `#3D6150` | one dark band max per page (free-lesson CTA, footer) |
| `--teal-ink` | `#5AA8B0` | hover state of teal, link accents |
| `--muted` | `#6D665A` | secondary text |

Hard rules:
- **No black borders, anywhere** — separation by surface tint + soft shadow
  (`0 4px 18px rgba(21,19,10,.07)`), not stroke. Applies to every container chain.
- Buttons must contrast in **default** state (never ink-on-ink).
- One accent per screen moment; coral is the action colour, teal is the brand colour.

### 1.1 Paper grain — the canvas feel (site-wide)

The cream background alone reads flat-digital; every page must feel like one
sheet of watercolour paper. Implemented once in `css/site.css` as `body::after`:

- Fixed full-viewport overlay, `z-index: 999`, `pointer-events: none`,
  `mix-blend-mode: multiply`, `opacity: .6`.
- Texture = inline SVG data-URI tile (280×280), **two feTurbulence layers**:
  low-frequency mottle (`baseFrequency 0.035`, cold-press cockle) + fine tooth
  (`baseFrequency 0.9`). Warm sand-tinted, alpha ≤ 0.09 per layer.
- Fixed (not scrolling) so content moves *across* the paper, like paint on one
  sheet. It grains photos, cards, dark bands and the 3D gallery equally —
  intended; that is what unifies the surfaces.
- No raster asset, no network cost. Do not raise opacity above ~0.7 or text
  contrast suffers; do not apply per-component textures on top of it.

## 2. Typography

| Role | Face | Notes |
|---|---|---|
| Hero / brand | `Permanent Marker` | hero H1, brand mark, big section leads. Never below 26px. |
| Section titles | `Italiana` | tracked caps (`letter-spacing:.14em; text-transform:uppercase`). Stand-in for Awesome Lathusca (personal-use license; swap = one token edit). |
| Body / UI | `Quicksand` (500/600/700) | all body, buttons, nav |
| Handwritten accents | `Caveat` | captions, ratio labels, ≤ 2 per page |

Blog post body (`blog-post.html`) heading hierarchy mirrors F1: H1 Permanent
Marker in dark chip on banner; H2 Italiana caps; H3 Quicksand 700.

`✳` asterisk = brand ornament (from Crystal's sketch) — used in scroll
affordance, list markers, dividers.

## 3. Shape & spacing

- `--radius-card: 18px` — source of truth = classic-waterfall card curve.
  **Every** card/cover/tile uses it. Pills `999px`. No other radii.
- Shared 12-col grid: `--grid-cols: 12`, max-width `1440px`, gutter `24px`.
- Anti-grid posture: the site must not read as a rigid template — allow
  rotated chips (±0.5deg), overlapping images crossing colour seams,
  asymmetric column splits (2.2 : 1 feed/sidebar), hand-drawn underlines.

## 4. Motion (GSAP 3 + ScrollTrigger)

| Token | Value |
|---|---|
| `--ease-brush` | `cubic-bezier(.19,1,.22,1)` (expo.out) |
| `--dur-entrance` | `1.6s` (hero masked-wipe) |
| `--dur-reveal` | `0.9s` (scroll fade-up) |
| `--stagger` | `0.12s` |

Baseline choreography (every page):
1. Scroll reveals: `.reveal` children fade-up 24px, power2.out, stagger 0.12s.
2. **One** brushstroke wipe per viewport max (hero images use CSS clip-path
   masked-wipe + 3deg skew, expo.out — ported from the Crafti hero, no GSAP needed).
3. `✳` spin-in on enter (rotate -90→0, back.out).
4. Link hover = squiggle underline (SVG stroke dashoffset), not colour swap.
5. `prefers-reduced-motion: reduce` → all of the above off; content visible.

Cursor: `.cursor-pencil` opt-in class on gallery + hero zones (custom pencil
SVG cursor); disabled on touch + reduced-motion.

### 4.1 Background brush trail (`js/paint-fx.js`, v2.7)

The cursor lays quiet brushstrokes on the paper **behind** the page. Client
directives baked in: never paint over content; one colour at a time; stroke
texture, not dots; keep it subtle.

- **Behind content**: canvas is `position:fixed; z-index:-1` — strokes show on
  the paper background only; cards, text, images always cover them.
- **One colour per stroke** — a stroke keeps a single pastel; the palette only
  rotates when a new stroke starts (≥280ms cursor pause). Interleaved colours
  multiplied into mud; never reintroduce per-dab colour cycling.
- **Ribbon, not dots**: stamps are laid every ~5px along the interpolated
  cursor path using a pre-rendered soft brush-tip sprite. Bristle jitter
  (perpendicular offset, width 0.75–1.25×, random tone, occasional dry-brush
  edge flicks) gives the thick→light randomised oil-brush texture.
- **The brush runs dry**: each stroke starts loaded (~0.75–1.0) and depletes
  per px travelled — long gestures thin out and stop, like a real brush.
  Fast gestures stamp lighter (speed-thinning).
- **Quiet**: stamp alpha ≈ 0.03–0.12, multiply-blended. If it draws the eye on
  its own, it's too strong.
- Lifecycle timestamp-based: settle `160ms` → wet `2600ms` → smoothstep dry
  `3600ms`. Stamp cap 900 (oldest dries first).
- Click = small splatter in the current stroke colour; drops fly ~240–540ms
  then land as stains. `window.casPaintBurst(x, y)` exposed for page scripts
  (lesson-completion celebration).
- Guardrails: fine pointers only; `prefers-reduced-motion: reduce` disables
  entirely; inert over other `<canvas>` (3D gallery).

## 5. Components

- **Top nav** — sticky, always on top (`position:sticky; top:0; z-index:100`),
  cream + blur backdrop. Brand = avatar + "Crystal's Art School". Items:
  Home · Lessons · Gallery · Blog · About. Right: cart button (coral badge),
  Log in (ghost), Sign up (primary). Mobile: hamburger → slide-down sheet.
- **Lesson cover card** — 1:1 image, title on surface (bottom gradient scrim),
  hover lifts cover and reveals two buttons: `Add to cart` (coral/pink
  watercolour + cart icon) + `View lesson →` (paper). No borders.
- **Featured carousel** — arrows only; bottom scrollbar hidden and unusable.
- **Hero (index)** — Crafti-port geometry: stage aspect 1862/1009, two-tone
  cream/paper-2 split at 48%, swirl SVG line-work, large img 41.7%w×79%h
  right-anchored, small img 24%w×50%h at right:34%/bottom:16% crossing the
  seam. 3 slides, 6s autoplay, ← → + counter.
- **Banner (blog post)** — 64:9 (~7.1:1) ratio, Permanent Marker H1 in dark
  chip, byline chip below, Caveat ratio annotation.
- **TOC (blog post)** — inline, ≥2 nesting levels, reads as a real contents
  table. No side panels on C5-derived pages.
- **Portrait** — twin-arch mask (`--shape-arch-duo`), 5:6 ratio, portraits
  only, max one per viewport.
- **Lesson detail page** (`lesson.html`) — the student-journey template,
  demonstrated by the free lesson:
  - Layout: 2.2:1 split — player column + sticky curriculum sidebar.
  - Player: 16:9 stage on ink, big cream play button, controls bar
    (play/pause · timecode · teal→coral gradient seek · demo speed chip).
    "Footage" is simulated: ken-burns on the unit poster + drifting wet sheen.
    Prototype plays at 12× with an honest Caveat hand-note saying so.
  - Curriculum rows: number chip → coral "now playing" → forest ✓ when done.
    Progress card with gradient bar, persists in localStorage
    (`cas-free-lesson-progress`).
  - Each unit = notes (marker H2 + body) + one blush "Crystal's note ✳" tip
    card + downloadable resources (data-URI files — real downloads, no server).
  - Video end or "Mark complete" → mint toast → auto-advance. All 6 done →
    completion card (rotated paper certificate, paint-burst celebration via
    `casPaintBurst`) + forest next-course upsell band.

## 6. 3D Gallery (`gallery.html`, three.js)

One museum, two wings off a lobby:
- **East wing — "Art Showcase"** (Crystal's work; name fixed by client).
- **West wing — "Fresh Eyes"** (student showcase; named for the lens of
  beginners seeing the world first-hand).

Rules:
- 20 hung works per wing (capacity constant `WORKS_PER_HALL = 20`), dynamic
  count supported — frames generated from a data array, not hardcoded.
- Varied source aspect ratios must be honoured: frame meshes size themselves
  from each texture's natural ratio (portrait / landscape / A4 all hang at a
  shared eye-line, like a real museum rail).
- Pastel architecture: cream walls, paper-2 floor reflection, warm gallery
  spotlights; ink wayfinding text.
- Art Showcase works → hover/click card with title + `Buy on Etsy` link;
  Fresh Eyes works → student name + avatar chip.
- Movement: drag to look, scroll / arrow keys / on-screen arrows to walk.
  Touch: drag-look + tap-to-walk. Small screens fall back to the same scene
  with simplified controls (no WASD hint).
- `WebGL` failure → graceful 2D collage fallback (stable row heights —
  landscape height equals portrait height).

## 7. Page map & naming conventions

| File | Wireframe lineage | Title pattern |
|---|---|---|
| `index.html` | F1 Classic Waterfall + Crafti hero | Crystal's Art School |
| `lessons.html` | L5 catalog + tier cards (A3) | Lessons · Crystal's Art School |
| `lesson.html` | new (v2.6) — free-lesson detail / student journey | Materials & setting up · Lessons · … |
| `gallery.html` | C3 + C4 merged → 3D museum | Gallery · … |
| `blog.html` | C1 blog index | Blog · … |
| `blog-post.html` | C5 post detail | <Post> · Blog · … |
| `about.html` | C2 About + Contact + T&C | About · … |

Nav labels match file names 1:1 (Home/Lessons/Gallery/Blog/About); `lesson.html`
highlights **Lessons** in the nav. Buttons: primary = coral filled; secondary =
paper filled; ghost = transparent + ink text. Every page version-stamped in
footer (`v2.x.y`); bump on every change.

Free lesson convention: **"Materials & setting up"** is THE free lesson — every
"free lesson" mention site-wide (home free-video tile, catalog FREE card,
Wanderer tier, forest band, footer "Free intro lesson") names it and links to
`lesson.html`. All other lessons stay behind Add-to-cart.
