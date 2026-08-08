# 03 — Design System

The entire visual language lives in **`css/industrial.css`** (~1,458 lines). It is a
tokenized system: a set of CSS custom properties defined once, re-pointed per theme, and
consumed by every component. Change a token, and every surface that uses it updates.

> **On Claude Design only**, `industrial.css` is split into a master file that
> `@import`s `industrial-1/2/3.css` (to dodge a write-size limit). In this repo it is a
> single file — that's canonical. See [08](08-claude-design-sync.md).

The concept (from `DESIGN.md`): **"the atelier as workshop"** — plaster, concrete, raw oak
& walnut, copper hardware, iron line-work. Warm and light, "art studio not dark loft"…
except the site currently ships the dark variant (theme D). See the theme-lock note below.

---

## Tokens

Defined on `:root` (constants) and per `[data-theme="A|B|C|D"]` (the palette). The
**names are theme-independent**; only their values change per theme. Never hard-code a hex
in a component — read a token.

### Constant tokens (`:root`, same across themes)

- **Shape:** `--radius-card: 16px`, `--radius-pill: 999px`, `--r-promo: 0 0 54px 0`
  (the signature bottom-right scoop), `--r-sharp: 2px` (chips/flags).
- **Layout:** `--site-max: 1440px`, `--page-max: 1216px`, `--gutter: 24px`.
- **Type:** `--font-marker`/`--font-display*` (Bodoni Moda), `--font-body` (Archivo),
  `--font-cond`/`--font-hand` (Oswald), `--font-stencil` (Saira Stencil One).
- **Motion:** `--ease-brush`, `--ease-pop`, `--ease-out`, `--dur-*`, `--stagger`.
- **Materials:** `--oak-grain`, `--walnut-grain`, `--concrete-grain`, `--iron-grain`,
  `--steel-grain` — inline SVG `feTurbulence` data-URIs (no image assets). Plus gradient
  metals `--steel`, `--blacksteel`, `--copper-metal`, `--olive-metal`.
- **The twin-arch mask** `--shape-arch-duo` — an SVG mask ("butterfly": two round-top
  columns) used for the About portrait and the home About tile.

### Palette tokens (per theme)

Each `[data-theme]` block sets the same ~30 names. The load-bearing ones:

| Token | Role |
|-------|------|
| `--cream` | page canvas |
| `--surface`, `--paper`, `--paper-2` | panel / card surfaces |
| `--ink` | text + line-work |
| `--muted` | secondary text |
| `--coral` / `--coral-deep` / `--coral-ink` | **primary action** (copper; olive in C) |
| `--teal` / `--teal-ink` | **secondary accent** (olive) |
| `--sand`, `--blush`, `--forest` | wood tones |
| `--banner-bg` / `--banner-grain` / `--banner-ink` | header/nav wood surface |
| `--wall` / `--wall-grain` | framed-gallery wall |
| `--frame-base` / `--frame-tl` / `--frame-br` | gallery frame mat + bevel |
| `--band-bg` / `--band-grain` / `--band-ink` / `--band-eyebrow` | dark wood bands + footer |
| `--line` | hairline borders |
| `--shadow` | the theme's signature shadow |
| `--glow-op` | daylight glow opacity (1 in D, else 0) |

---

## The four themes

Set via `data-theme` on `.cas-root`. Design intent:

| Theme | Name | Canvas | Wood | Frame wall | Accent | Mood |
|-------|------|--------|------|-----------|--------|------|
| **A** | Light Oak Atelier | plaster cream | oak | concrete | copper | airy (design default) |
| **B** | Walnut Workshop | concrete | walnut | walnut | copper | deeper, warm |
| **C** | Concrete & Iron | grey concrete | concrete | iron | **olive** | cool, line-work |
| **D** | Moody Loft | dark iron + daylight glow | walnut | walnut wood | copper (primary = walnut wood) | dark, light text |

Theme D inverts `--ink` (light) and `--cream`/`--surface` (dark) so the whole site flips
cleanly, and turns on `--glow-op` for a daylight wash. In D the framed gallery wall and
the primary buttons become real **walnut wood** (a `wood-plank.jpg` photo, layered with a
dark gradient).

### ⚠️ Theme lock (current state)

Although all four themes exist and work, **the visible A/B/C/D switcher was removed** and
every page's `Component.componentDidMount()` calls `applyTheme('D')` unconditionally. So:

- The site always renders **theme D (Moody Loft, dark)**.
- The `data-props` `defaultTheme` prop and the `localStorage('cas-theme')` read still
  exist in the code, but the forced `applyTheme('D')` overrides them.
- The `.theme-switch` CSS is still in `industrial.css`, but no page renders the switch
  markup in the nav anymore.

To re-enable the switcher: add the `.theme-switch` segmented control back into the nav
markup, and change `applyTheme('D')` to `applyTheme(saved)` in each page's Component.
(The `saved` variable is already computed just above that line in every page.)

---

## Typography

| Role | Face | Token |
|------|------|-------|
| Display / headlines | **Bodoni Moda** (architectural serif) | `--font-marker`, `--font-display*` |
| Labels / nav / UI accents | **Oswald** (condensed grotesk) | `--font-cond`, `--font-hand` |
| Body / running text | **Archivo** (technical grotesk) | `--font-body` |
| Brand stamp | **Saira Stencil One** | `--font-stencil` |
| Handwritten signature | **Mrs Saint Delafield** (About hero `.sig-script`) | inline |

Loaded per page via a Google Fonts `<link>` in `<helmet>`.

---

## Materials & finishes

All textures are **CSS, not image files** — inline SVG `feTurbulence` data-URIs on `:root`:

- `--oak-grain`, `--walnut-grain` — warm horizontal wood grain.
- `--concrete-grain` — fine cold mottle (panels, filter rails).
- `--iron-grain` — vertical brushed-metal streaks.
- `--steel-grain` — fine vertical brush (light steel).

Buttons and bands aren't flat fills — `.btn.primary` is a brushed-metal gradient built
from the accent (`copper` in A/B, `olive` in C, **walnut wood** in D). Course-card buttons
(`.cc-btn`/`.cover-btn`) are wood (walnut primary, oak secondary). Wooden banners (nav,
`.page-hero`, `.band-forest`, footer) layer `--banner-grain`/`--band-grain` + faint plank
seams (`repeating-linear-gradient`).

The one real photographic texture is **`img/wood-plank.jpg`**, used in theme D for the nav,
page-hero, blog feature, gallery walls, and certificate cards.

> DESIGN.md's plan: when real wood/metal photos arrive, replace the `*-grain` `url()`s on
> `:root` and every surface updates at once.

---

## Signature shapes

- **`--r-promo` (0 0 54px 0)** — the bottom-right scoop, used only on big promos: hero
  large image, gallery promo tile, free-video tile, course cover, checkout confirmation.
- **`--radius-card` (16px)** — the standard corner for cards and tiles.
- **`--radius-pill`** — buttons, nav pills, chips-that-are-actions, cart badge.
- **`--r-sharp` (2px)** — flags/chips (industrial squared edges).
- **Ornate deer frame** — in theme D the gallery frames are replaced by an ornate carved
  SVG overlay (`img/deer-black.svg`) that adapts to any picture ratio.

---

## ⚠️ Theme-D safety rules (read before styling)

Because theme D inverts light/dark, some values flip in ways that cause invisible
(light-on-light or dark-on-dark) text. Follow these:

1. Any element on a **fixed dark surface** (video player chrome, dark bands) must use
   **fixed light text** (`#ede1d0` / `#f3ede1`), never `var(--cream)` / `var(--ink)` /
   `var(--paper)` — those flip in D.
2. An always-light **oak wood chip** (`#cab389`) must use fixed **dark** text (`#474138`).
3. `var(--teal-ink)` becomes a *light* oak in D — never use it as a **background**; for an
   olive surface use fixed `#4d6625`.
4. `<button>` elements default to black text — always set an explicit `color`.
5. Several rules use `-webkit-text-fill-color` alongside `color` to force button label
   colours in WebKit; keep both in sync when you change a button colour.

---

## Component catalog (where to find each in `industrial.css`)

Rough section order in the file: base/reset → typography helpers → buttons → nav →
hero → New Release trio → cover cards → carousel → gallery promo → share cards → bands →
testimonials → footer → Lessons page (filters, tiers) → auth → gallery store (frames,
modal) → blog → about → lesson player → checkout/auth → dashboard → mobile polish → the
"2026-07 additions" block (signature, accordions, showcase, course page, bio-certs).

The last block (roughly lines 1355+) is the newest work — if a page looks unstyled after
a partial CSS sync, it's usually because that tail didn't make it up.

Next: [04 — Pages Reference](04-pages.md).

## Letter-spacing

Three tokens, and a fourth case that is the absence of one. Anything not on this
scale is a drift, not a decision.

| Token | Value | Applies to |
|---|---|---|
| `--tracking-label` | `0.06em` | Every Oswald (`--font-cond`) UPPERCASE micro-label, 12–16px: eyebrows, kickers, chips, tags, flags, pills, buttons, nav links, meta rows, table headers |
| `--tracking-display` | `0.04em` | Bodoni Moda (`--font-display` / `--font-display-serif`) uppercase headings at any size |
| `--tracking-stamp` | `0.10em` | Saira Stencil One (`--font-stencil`) wordmarks only — four declarations |
| *(no token — delete the declaration)* | `normal` | All sentence-case and lowercase text in any face |

Why these numbers, briefly:

- **0.06em** is where three independent derivations land: the two contracted
  reference sites' stylised-label ceilings normalise to a 0.050–0.066em band on
  Oswald; at 0.06em the letter gap is 47% of the word gap, and past ~0.10em words
  stop grouping pre-attentively; and Oswald's optical cap-fit gives ~0.05em. One
  value, no size ramp.
- **0.04em** is separate because a didone's serifs occupy the sidebearings — its
  native cap-to-cap gap is ~0.034em against Oswald's 0.117em, so it needs a
  positive delta Oswald does not. This role never drifted; folding it into
  0.06em would cost display quality for nothing.
- **0.10em** is separate because a stencil face has bridge gaps cut into the
  strokes. Tightened to the label value the bridges read as damage. These four
  are lockups the reader identifies, not labels they scan.
- **Never track running lowercase.** The fit is drawn for exactly that use and
  added space costs visual span. Both reference sites set body copy to `normal`.
  Expressing this as a token would give the system a fourth name meaning "delete
  the line", so these declarations are removed rather than re-pointed.

**Nine declarations are deliberately off-scale.** Each is protected by a recorded
client decision — a section of the page the client froze, an element the client
named, or an element scheduled for deletion where retuning would only create a
merge conflict with the deletion. They will visibly disagree with their retuned
siblings; that is accepted, not an oversight. The list with its reasoning is in
the local design-direction document (gitignored). Before "fixing" a stray
tracking value, check that list.

## Brand marks ship exactly as the client supplied them

All three marks — the nav logo, `logo2`, the signature — are **fully opaque**.
Every file the client uploaded has 0.0% transparent pixels, so each one draws a
solid rectangle behind the artwork. That is deliberate and must stay.

De-backgrounding is the client's side of the work under the design direction, and
doing it here has two costs: it reads as included, so it becomes the baseline
expectation for everything after; and while it looks done, she never sends the
real file. The solid rectangle is the ask, made self-evident.

Three consequences for anyone styling these:

- **Do not cut them out.** A set of cut-outs was built, shipped, and then removed
  again. They are kept locally and in git history.
- **Do not fake it either.** `mix-blend-mode: multiply` (and `screen` plus an
  invert on the dark theme) removes the white ground convincingly without
  touching the file. It was built and removed on the same instruction: the page
  would look finished while the asset is not. The method is written up locally so
  nobody derives it a third time.
- **Do not "fix" it with `filter: brightness(0)`.** On an opaque white rectangle
  that produces a black box, which reads as a rendering bug rather than a missing
  asset. The two light-theme invert rules in `css/industrial-1.css` are commented
  out for exactly this reason, and **must be re-enabled the moment transparent
  artwork arrives** or the light theme loses the invert it needs.

That last step is easy to forget, so it is not left to memory:
`tools/verify-round-2.py` reads the alpha channel of the shipped marks on every
run and fails if transparency has arrived while the rules are still commented —
or if the rules are live over an opaque mark.
