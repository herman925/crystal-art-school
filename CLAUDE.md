# Crystal's Art School — Site CLAUDE.md (v2.0)

> Working notes for the **production prototype site** at project root.
> The wireframe-phase docs live in `wireframes/docs/` (CLAUDE.md + PRD.md) and
> remain the archive of the exploration phase. This file records decisions made
> when the wireframes graduated into real pages (2026-06-11).

## What this is

A navigable multi-page hi-fi prototype, deployable as-is (Vercel / OD quick
deploy from `index.html`). No build step. GSAP for scroll motion, three.js for
the 3D gallery, everything else vanilla.

```
index.html        ← front page (was F1 Classic Waterfall + Crafti hero)
lessons.html      ← catalog + membership tiers (L5 + A3 lineage)
gallery.html      ← three.js museum — replaces C3 + C4
blog.html         ← blog index (C1 lineage, image-heavy cards)
blog-post.html    ← post detail (C5 lineage)
about.html        ← about + contact + T&C (C2 lineage)
css/site.css      ← design system tokens + shared components
js/site.js        ← nav sheet, GSAP reveals, carousel arrows, cart badge
js/gallery3d.js   ← 3D exhibition module
img/              ← compressed photos (3.7MB total, deploy-safe < 10MB)
DESIGN.md         ← design system v2.0 (source of visual truth)
PRD.md            ← production addendum to docs/PRD-phase1.md
```

## Key decisions (v2.0, 2026-06-11)

1. **Concept adoption** — the attached design-concept deck (pastel sketchbook
   system: cream/paper-2, teal/coral/blush, Quicksand + Permanent Marker) is the
   visual base; iterated wireframe elements (Crafti hero geometry v0.10.34,
   carousel arrows-only, lesson cover 2-button cards, C5 banner treatment) were
   folded in without disturbing the layout language.
2. **Real pages, real nav** — wireframe tab-jumping removed. Sticky top nav
   (always on top, z-100) with Home / Lessons / Gallery / Blog / About; labels
   match filenames 1:1. Cart button with coral badge lives in the nav.
3. **Galleries merged into one 3D museum** (`gallery.html`):
   - East wing **"Art Showcase"** — Crystal's work (name fixed by client);
     click → card with *Buy on Etsy* link.
   - West wing **"Fresh Eyes"** — student showcase (named for the beginner's
     lens — seeing first-hand, before habit sets in); click → student name chip.
   - 20 works per wing, data-driven arrays so the count is dynamic; frame
     meshes size themselves from each texture's natural aspect ratio →
     portrait / landscape / A4 all hang on one eye-line rail.
   - Controls: drag-look, wheel / arrows / WASD / on-screen buttons to walk,
     wayfinding pill jumps between wings. 2D collage fallback (stable row
     heights) when WebGL/module load fails.
4. **Motion stack** — GSAP 3 + ScrollTrigger via CDN: fade-up staggers,
   ✳ spin-in, one brushstroke wipe per viewport. Hero entrance stays CSS-only
   (masked wipe + skew, expo.out — ported verbatim from wireframe v0.10.34).
   `prefers-reduced-motion` disables everything.
5. **Pastel-only rule** — all surfaces from the brand pastel set; `--ink` and
   `--forest` are the only deep anchors (text + max one dark band per page).
   No black borders anywhere (client directive, strict).
6. **Anti-grid posture** — rotated chips, seam-crossing hero images, 2.2:1
   asymmetric columns, hand-drawn squiggle underlines; avoid over-aligned
   template look (client directive).
7. **Wireframe-only artifacts dropped in production**: annotation overlays +
   FAB, ratio labels on banners, mac chrome viewports, compare strips.
   They remain in `wireframes/` for reference.
8. **Versioning** — every page footer carries `v2.x.y`; bump on every change
   (client convention carried over from wireframes).
9. **Legacy cleanup** — root-level orphan exports (`mpl*`, `mph*`, `mq9*`,
   `Crystals Art School Wireframes.html`, root `compress-imgs.py`) deleted;
   `wireframes/` kept intact as the exploration archive.

## Conventions

- Buttons: `.btn.primary` coral = action; `.btn.teal` brand; `.btn.ghost`
  quiet. Cover cards always pair *Add to cart* (coral + cart icon) with
  *View lesson →* (paper).
- Radius: 18px cards (classic-waterfall curve = source of truth), 999px pills.
- Fonts: Permanent Marker (hero/brand) · Italiana (section titles, tracked
  caps; Lathusca stand-in) · Quicksand (body) · Caveat (hand notes, ≤2/page).
- Images referenced as `img/<Name>.jpg`; keep `ext-resource-dependency` metas
  in `index.html` in sync when adding files (OD quick-deploy bundler reads them).
- Demo cart count persists in `localStorage` (`cas-cart-count`).
