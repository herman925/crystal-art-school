# Crystal's Art School — Industrial Design System (v3.0)

> 工業風畫室 · "the atelier as workshop"
> Reskins the pastel sketchbook system (site.css v2.1) into a warm industrial
> interior — plaster, concrete, raw oak & walnut, copper hardware, iron
> line-work — without changing the site's layout or behaviour. Implemented in
> **`css/industrial.css`**; both pages (`Home.dc.html`, `Lessons.dc.html`) link it.

---

## 1. Concept

Keep the craft warmth and exact structure of the original site, but rebuild every
surface as a light, warm industrial space (the mood reference: concrete walls,
black-metal shelving, wood furniture, daylight, plants — executed *lighter* so it
reads **art studio, not dark loft**). Four selectable treatments (themes) let the
same markup range from airy light-oak to a moody loft.

## 2. How the reskin works (important)

The original CSS variable **names are preserved** (`--cream`, `--coral`, `--teal`,
`--surface`, `--ink`, `--forest`, …) and **re-pointed per `[data-theme]`**. Every
ported component therefore reskins automatically *and* responds to the A/B/C/D
switch with **zero markup changes**. Never hard-code a hex in a component — read a
token.

## 3. Palette → roles (the client's industrial palette)

| Hex | Material | Token role |
|---|---|---|
| `#ede1d0` | 灰泥奶白 plaster cream | page canvas (`--cream`) |
| `#cdc7bd` | 混凝土灰 concrete | panel / surface step (`--paper-2`) |
| `#cab389` | 生木 oak | **wood for banners/headers** (`--sand`, `--banner-bg`) |
| `#876d5c` | 舊木 walnut / leather | deep wood, dark bands (`--blush`/`--forest`) |
| `#9a6436` | 銅鏽 copper | **primary action** — CTAs, active (`--coral`) |
| `#474138` | 鐵棕 iron-brown | text / line-work (`--ink`) |
| `#4d6625` | 橄欖綠 olive | **secondary accent** — brand/links (`--teal-ink`) |
| `#2b2823` | iron (derived) | metal fixtures, frame hardware, brackets |
| `#f3ede1` | (derived) | frame matting, light text on dark |

Rule: **one accent per moment** — copper is the action colour, olive the secondary.
(Theme C promotes olive to the primary action.)

## 4. Themes (the A/B/C/D switch)

A visible segmented control in the nav (`.theme-switch`) sets `data-theme` on
`.cas-root` and **persists in `localStorage('cas-theme')`** so the choice follows
the visitor across pages. End-user facing; all four available everywhere.

| Theme | Name | Sheet | Banner wood | Frame | Accent | Mood |
|---|---|---|---|---|---|---|
| **A** | Light Oak Atelier | plaster | oak | oak | copper | airy (default) |
| **B** | Walnut Workshop | concrete | walnut | walnut | copper | deeper, warm |
| **C** | Concrete & Iron | grey concrete | concrete | iron | **olive** | cool, line-work |
| **D** | Moody Loft | dark iron + daylight glow | walnut | walnut | copper (primary = **walnut wood**) | dark, light text |

**Button material per theme** (from `design_base.html`): primary `.btn.primary` is
brushed **copper** (A, B), brushed **olive** (C), **walnut wood** (D); framed-gallery
wall is concrete (A) / walnut (B) / iron (C) / **walnut wood** (D).

Theme D flips `--ink` to light and `--cream`/`--surface` to dark, so the whole
site inverts cleanly. `--glow-op` adds the daylight wash (D only).

## 5. Typography

| Role | Face | Token | Notes |
|---|---|---|---|
| Display / headlines | **Bodoni Moda** | `--font-marker`, `--font-display*` | architectural serif; hero, section + card titles |
| Labels / nav / UI accents | **Oswald** | `--font-cond`, `--font-hand` | condensed grotesk; eyebrows, buttons, meta, captions |
| Body / UI | **Archivo** | `--font-body` | technical grotesk; all running text |
| Brand stamp | **Saira Stencil One** | `--font-stencil` | nav brand, footer brand, tier names, hero "Crystal." |

Replaces the old Permanent Marker / Italiana / Quicksand / Caveat set.

## 6. Materials (CSS now — swap for real photos)

Generated with layered `feTurbulence` (no assets), tuned per material; quiet by
design. Defined once on `:root`, applied via theme tokens.

- `--oak-grain`, `--walnut-grain` — streaky horizontal wood grain (warm tints).
- `--concrete-grain` — fine cold mottle.
- `--iron-grain` — vertical brushed-metal streaks (dark / blackened iron).
- `--steel-grain` — fine vertical brush for **light brushed steel**.

**Metal & hardware (explored on the board, `design_base.html` §01B):** three
metal finishes, not one — **brushed steel** (light: `--steel` cool-grey gradient +
`--steel-grain` + edge highlight), **blackened iron** (`--blacksteel` + `--iron-grain`),
and **copper** (`--copper-metal` warm vertical gradient + top sheen). Hardware kit:
rivets (radial-highlight discs), hex **bolts** (`clip-path` polygon on steel),
iron **corner brackets**, and **hinge** plates — used as corner accents on metal
surfaces and dark bands.

**Button & control finishes (not flat fills) — live site-wide in `css/industrial.css`:**
`.btn.primary` is **brushed metal** built from the theme accent `--coral` (warm
vertical sheen + diagonal highlight + fine brushed striation): **copper** in A/B,
**olive** in C. In **Theme D the primary is walnut wood** (`--walnut-grain` + sheen,
the nav-banner/frame wood). Course-card buttons (`.cc-btn`/`.cover-btn`) are wood
too — **walnut** primary, **oak** secondary. Secondaries elsewhere stay a wood/ghost
outline. Olive (`--olive-metal`) is the secondary action in Theme C. One accent per
moment — copper acts, olive supports. Reference: the four cards in `design_base.html`.

**Wooden banners (the client request):** header/band surfaces carry a wood panel —
`background-color: var(--banner-bg)` + `var(--banner-grain)` + faint plank seams
(`repeating-linear-gradient`) + iron corner brackets. Applied to: the **Lessons
page header** (oak), the **community / free-lesson bands** and **footer** (walnut),
and tier "Fellow" card. The home A1 photo banner stays **photo + ratio chip only**
(no buttons).

When real wood/metal photos arrive: replace the `*-grain` `url()`s in
`css/industrial.css` `:root` — every surface updates at once.

## 7. Shape & corners

- `--radius-card: 16px` — grid cards / tiles ("all curved").
- `--r-promo: 0 0 54px 0` — **signature bottom-right scoop**, on the big promos
  only: hero large image, A1 banner, free-video tile, gallery promo tile.
- `--r-sharp: 2px` — chips, flags (industrial squared edges).
- `--radius-pill` — **buttons** (`.btn`/`.btn-big`/`.btn-outline`/`.hero__cta`, rounded per design_base), nav pills, theme switch, cart.

## 8. Framed gallery (the Etsy-style store)

Every artwork hangs in a real frame: `--frame-base` mat + 4-tone bevel
(`--frame-tl` light top/left, `--frame-br` dark bottom/right) on a textured wall
(`--wall`). Per-theme walls: concrete (A) / walnut (B) / dark iron (C) / **walnut
wood (D)** — in D the framed wall is the evening-class band wood, matching the board. Cards are a
**uniform 1:1 wall + fixed 4:5 frame** so the whole grid aligns. A left filter
rail drives tag (dynamic, multi-select) + max-price + sort; **View details** opens
a quick-view modal (large framed image, description, specs, price), **Buy on Etsy**
opens an Etsy search and adds to cart. Reference board: `design_base.html`.

## 9. Motion (ported 1:1, reskinned)

- Hero: 3-slide Crafti panel, 6s autoplay, prev/next + counter, GSAP image
  clip-path wipe + text rise (content visible by default — entrance is
  enhancement, since this renderer drops keyframe end-states).
- Home scroll choreography (GSAP): each section performs its own entrance —
  New Release three-beat, Shop card-deal, Share + spinning TOOLS disc, band
  split-in, testimonials pop. On-screen sections cascade on load; below-fold
  play on scroll. Hero depth parallax.
- Other pages: rect-based reveal (`.in-view`) with visible-by-default fallback.
- Carousel arrows fade unless the carousel is hovered/focused.
- All disabled under `prefers-reduced-motion`.
- `✦` (stamped asterisk) is the brand ornament (replaces the old `✳`).

## 9a. Theme-D safety rule (important)

Any element whose **background is a fixed dark surface** (video player chrome,
dark bands) must use **fixed light text** (`#ede1d0`/`#f3ede1`), never
`var(--cream)`/`var(--ink)`/`var(--paper)` — those flip in theme D and cause
light-on-light. Likewise an always-light wood chip (oak `#cab389`) must use
fixed **dark** text (`#474138`). `var(--teal-ink)` is a light oak in D, so never
use it as a *background*; use fixed olive `#4d6625` for olive surfaces. `<button>`
elements need an explicit `color` (they default to black).

## 10. Files

```
css/industrial.css   # the system — tokens, 4 themes, every component, motion
index.html           # Home (hero, New Release, shop, blog, community, testimonials)
Lessons.dc.html      # course catalog + filter + membership tiers
Gallery.dc.html      # Etsy-style framed store (tag/price/sort + quick-view modal)
Blog.dc.html         # journal, newest 10 + tag filter (Tools deep-link)
blog-post.dc.html    # article: oak banner, hierarchical TOC, comments, related
About.dc.html        # twin-arch portrait, approach, contact form, terms
lesson.dc.html       # simulated 6-unit player, progress persists, completion
design_base.html     # the direction board: palette, materials, §01B metal·hardware·buttons, frames, 4 themes
img/                 # studio photography (from the repo)
```

Every page links `css/industrial.css`, carries the same nav/footer chrome, the
A/B/C/D switch, and a site-wide cart count — both persisted in `localStorage`.
Behaviour is vanilla JS in each component's `componentDidMount`.

## 11. Port status — COMPLETE

All repo pages ported and reskinned: index, lessons, gallery (re-imagined as the
Etsy store, replacing the 3D museum), blog, blog-post, about, lesson player.
