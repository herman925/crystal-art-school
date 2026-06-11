# Crystal's Art School — PRD addendum (production prototype v2.0)

> Extends `wireframes/docs/PRD.md` (the authoritative Phase-1 PRD).
> This addendum records what changed when wireframes graduated into the
> navigable production prototype on 2026-06-11. Where the two conflict,
> this file wins.

## 1. Site map (replaces wireframe tab codes)

| Page | Lineage | Status |
|---|---|---|
| `index.html` | F1 Classic Waterfall + Crafti hero (v0.10.34 geometry) | built |
| `lessons.html` | L5 catalog + A3 pricing tiers | built |
| `gallery.html` | C3 + C4 → merged 3D museum | built |
| `blog.html` | C1 blog index | built |
| `blog-post.html` | C5 post detail | built |
| `about.html` | C2 About + Contact + T&C | built |
| Auth / dashboard / checkout (A1–A8, D1, L1–L4 player) | wireframes only | Phase 2 |

Navigation: sticky top bar, always on top. Home · Lessons · Gallery · Blog ·
About + cart + Log in / Sign up. No designer controls in any page.

## 2. Gallery requirement (new — supersedes C3/C4 split pages)

One three.js museum, two wings off a lobby:

- **Art Showcase** (east) — Crystal's work. Hover/click → title, author,
  **Buy on Etsy** (Etsy bridge unchanged from PRD §Etsy Bridge).
- **Fresh Eyes** (west) — student showcase. Click → work title + student name.
- Capacity: 20 hung works per wing for launch; works defined in data arrays in
  `js/gallery3d.js` (`TEACHER_TITLES` / `STUDENTS`), so count is dynamic and
  uploads of any aspect ratio are honoured (frames size from texture ratio,
  shared eye-line rail like a real museum).
- Movement: drag-look + walk (wheel / keys / buttons / touch). Wayfinding pill
  jumps Lobby ↔ wings.
- Degradation: WebGL or CDN failure → 2D collage fallback with stable row
  heights (landscape height = portrait height, per client rule).

## 3. Motion requirements (new)

- Library: GSAP 3 + ScrollTrigger (CDN). three.js r160 (CDN, ESM).
- Per-viewport budget: fade-up stagger + at most one brushstroke wipe;
  ✳ ornament spin-in; squiggle link underlines.
- Hero entrance is CSS-only (locked template per PRD §Hero Banner Spec).
- All motion gated behind `prefers-reduced-motion`.

## 4. Content rules carried into production

- Blog cards are image-first with minimal surface text; full article on click.
- Blog post: 64:9 banner, marker H1 in dark chip, inline multi-level TOC,
  no side panels.
- Lesson covers: 1:1, title on scrim, two actions (Add to cart coral + icon /
  View lesson). Prices shown are sample placeholders pending Crystal's final
  pricing (PRD §Course Pricing).
- Pastel-only palette; no black borders anywhere; 18px card radius everywhere.

## 5. Deployment

- Static, no build step. Deploy root = project root (`index.html` is the
  front page). Vercel root directory must point at the folder containing
  `index.html`.
- Images pre-compressed to 3.7MB total (10MB quick-deploy POST limit).
- `ext-resource-dependency` metas in `index.html` enumerate every shippable
  file for the OD quick-deploy bundler — keep in sync.

## 6. Open items (Phase 2 candidates)

- Auth flows, dashboard, checkout (wireframed A1–A8/D1, not built).
- Lesson player pages (L3/L4) + real video hosting.
- Real lesson pricing + Etsy listing URLs per artwork.
- Gallery: dynamic upload pipeline for student works (currently data array).
- Lathusca license purchase → swap `--font-display` token.
