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

## The material-exemption rule

Two forces pull in opposite directions in this system. The palette is themed: every
token is re-pointed per `[data-theme]`, and a component that reads a token flips with
the switch. But the workshop metaphor has **materials** in it — oak, walnut, copper,
steel, leather, marine, rouge, linen — and a material does not repaint itself when the
lights change. A certificate is gold on cream in a dark room too.

So some components are deliberately **byte-identical in theme A and theme D**. That is
not drift. The rule that decides which is which:

> **A component is exempt from theming when what it depicts is a physical material or a
> colour the client named in writing. Everything else must read tokens.
> An exemption is from THEMING ONLY — it is never an exemption from contrast.**

### What is exempt, and what it measures

Each of these is fixed across both themes on purpose. The ratio is the worst ink on that
surface, measured off the painted pixels, not calculated from the token.

| Surface | Fixed value | Ink | Measured |
|---|---|---|---|
| Oak chip (`.art-tagchip`, `.art-btn.detail`, `.card.blush`, `.pull-quote`, `.unit-row.current`, `.finish-card .cert`) | `#cab389` + `--oak-grain` | `#474138` | 4.70–5.06:1 |
| Walnut button (`.cc-btn.primary`, `.cover-btn.primary`, `.art-btn.etsy`) | `#5e4a3d` + `--walnut-grain` | `#f4e7cf` | 5.92:1 |
| Steel (`.chip.active`, `.fopt.active`, `.cart-btn`) | `--steel` gradient | `#2b2823` | 8.59:1 |
| Copper (`.btn.primary`, `.btn-big`) | `--copper-metal` | `#f8ecd8` | see note below |
| Marine — **client-named**, design direction §1.4.2 (`.rel-chip`, `.cmc.is-filter`, `.media-tag`, `.bc-tag`) | `--marine` `#598493` | `--marine-ink` `#0b1418` | 4.56:1 |
| Rouge — **client-named**, 2026-08-08 (`.sale-ribbon`, `.sale-flag`) | `--rouge` `#a8463f` + `--rouge-metal` | `#fdf3ef` | 5.30:1 |
| Linen — **client-named** (`.cmc.is-fact`, `.btn.linen`, `.portrait-note`) | `--linen` `#d3c2bb` | `#2b2823` | 8.53:1 |
| Mauve — **client-named** (`.teach-card`) | `#a498a6` | `#191419` | 6.60:1 |
| Machined marine — the marine fills that carry a metal gradient and a rake (`.btn.marine`, `.btn-big.btn-marine`, `.blog-feature .bf-ribbon`) | `--marine-metal` under a 10% black rake | `--marine-ink-metal` `#04080a` | 4.78:1 |
| Leather (`.blog-feature`) | `--leather` / `--leather-a` | the four `--leather-*` inks | 4.77–13.39:1 |
| Certificate frame (`.cert-frame`) | `#8a6f3d` on `#f7f2e5` | — | decorative border |
| Video chrome (`.player`, `.stage`, `.controls`, `.co-highlight.has-video`) | `#1b1814` | `#ede1d0` | letterbox black |

Three of those have a caveat worth keeping in view:

- **Leather.** `--leather` and `--leather-a` are the **base fill under five texture
  layers**, not the colour of the card. `--leather-a` is `#d2cec7` (L .620) and paints
  `#a99d8d` (L .345). Never derive a ratio from either token — sample the screenshot.
  The light cover sits at 2.41:1 against the cream page; that is a card boundary, not a
  text or control contrast, and it is additionally bounded by `--shadow`, an inset rim
  and the stitched border.
- **Machined marine.** The row above is a *second* marine row on purpose. A machined
  marine fill is not the flat `--marine` token: `--marine-metal` runs down to `#456b78`
  and a repeating 10%-black rake sits over it, so the pixels under a label average
  darker than the token does. `--marine-ink` `#0b1418` measures 4.56:1 on the token and
  **4.29:1 on the painted fill** — a pass if you check the swatch and a fail if you check
  the screen. `--marine-ink-metal` exists for those fills and only those. Never carry a
  ratio across from the flat row.
- **Video chrome** is exempt because the black IS the letterbox. `.co-highlight` without
  a film in it is not — it was `#1b1814` in both themes and put a 900×506 black
  rectangle above the fold on the cream page. The empty state is now `var(--surface)`
  and only `.has-video` keeps the black.

### What is NOT exempt

If a component is identical in both themes and is not on the list above, it is drift.
Currently outstanding, all outside `css/`:

- **Showcase's wall** — 14 literal colours, 0 token references, in `Showcase.dc.html`.
- **The Courses mega drawer** — `#26221e` / `#cf9457` / `#ede1d0` in `js/site-nav.js`.
- **FreeVideos' lightbox** — 35 raw literals against 44 token uses, in `FreeVideos.dc.html`.

## Copper has three roles, and one token cannot do all three

`--coral` is the copper accent. It failed 4.5:1 in **both** themes across about fifteen
components at once, and the reason it failed everywhere at once is that it was doing
three jobs whose requirements point in opposite directions.

| Token | Role | Value |
|---|---|---|
| `--coral` | the **material** — gradients, progress fills, tints, hairlines. Never small text, never a fill under a light label. | A `#9a6436` · D `#a96b38` |
| `--coral-ink` | copper as **text** on `--cream` / `--surface` / `--paper`. Per theme. | A `#7e4c27` (6.11:1) · D `#cab389` (7.21:1) |
| `--coral-fill` + `--coral-fill-ink` | copper as a small **fill** carrying a light label. One value for both themes. | `#8a5528` + `#fdf6ea` (5.73:1) |

Text on a dark page wants a *lighter* copper; a fill under a cream label wants a *darker*
one. `--coral` at either theme's value measured 4.24:1 (A) and 3.36:1 (D) under a cream
label, and 4.35:1 / 3.39:1 as text. **Before adding a copper anywhere, pick the role
first.** If you find yourself writing `color: var(--coral)`, you want `--coral-ink`.

## Focus

One ring, defined once, in `css/industrial-1.css`:

```
--focus-ring: #f9f4ea;        /* the pale band */
--focus-ring-edge: #12100c;   /* the dark bands either side of it */
--focus-ring-w: 3px;
```

It is three bands — dark, pale, dark — because no single colour clears 3:1 against every
surface in this system. The pale band clears 3:1 on anything below luminance .267 and the
dark band on anything above .117, and nothing in either theme falls between the two, so
at least one band always carries. Measured worst case: the Discord card's blurple at
4.20:1; everything else is above 4.5:1 on one band or the other.

The rules live in the **accessibility layer at the foot of `css/industrial-3.css`**, which
is last on purpose — several components set the same properties at the same specificity
several hundred lines further down, and an earlier position simply loses the cascade.

Three things to know before touching it:

- **Do not give a component its own `:focus-visible` colour.** If a new surface appears
  that lands between those luminances, widen the ring in the token block instead.
- **A container that clips will clip the ring.** `overflow: hidden` removes it entirely —
  measured on `.syl-item`, 0 of 103,008 pixels changed on focus. The fix is
  `overflow: clip` plus `overflow-clip-margin: 8px`, which keeps the rounded-corner clip
  and lets ink spill by the width of the ring. Where the clip is load-bearing (the
  `.cover-card` sale ribbon relies on it, `.qty` on its pill, `.cert-list` on its scroll)
  the same ring is turned inward instead.
- **`color-scheme` is declared per theme** (`light` in A, `dark` in D). Without it both
  `:root` and `body` computed `normal`, which is why a dark page got light-mode UA
  chrome — scrollbars, select popups and the default ring all drawn for a white page.

## Touch targets

Every interactive control reaches 44×44 under a coarse pointer, in the same accessibility
layer. Three techniques, chosen by what the control is:

- `min-height` where it is a text button and can simply grow.
- `width`/`height` **plus `flex: none`** where it is an icon disc. The `flex: none` is
  half the fix: the cart button and the burger were being shrunk to 32 and 36 wide by
  their flex row, not by their own rule.
- `padding` + `background-clip: content-box` + `box-sizing: content-box` where the
  control is a **dot** and growing it would change what the design says. The padding
  enlarges the box, the clip keeps the paint at the old size, and `border-radius` is
  resolved against each box separately, so the dot stays a circle. Used on
  `.rel-dots button` (8px) and `.phc-dot` (9px).

`.acc-ico` stays 31×31 and is the one deliberate exception: it is the +/× marker inside
`details.acc-item summary`, and the summary — which is the actual control — measures
360×73 at 390px.

Pagination dots also have to clear **3:1 as non-text indicators**. They were 1.56:1 (A) /
2.08:1 (D); `.phc-dot` is now 55% ink and `.rel-dots button` .72 alpha over a dark ring.

## Reading measure

Body copy on the two Read-mode surfaces is capped in `ch`, not pixels, so the cap tracks
the type:

| Surface | Cap | Resolves to | Measured characters per line |
|---|---|---|---|
| `.article` (blog post) | `min(760px, 58ch)` | 549px at 16.5px | min 57 · **median 66** · max 80 |
| `.acc-body p` (FAQ answers) | `58ch` | 483px at 14.5px | min 24 · **median 67** · max 77 |
| `.page-lead` | `74ch` | 722px at 17px | 87 — the pre-existing precedent for the device, not for the number |

Count characters **including spaces**, with `Range.getClientRects()` over each character;
a space takes measure like any other glyph and leaving them out under-reports a line by
about a sixth. The median is the statistic that matters — a fixed column always has a
spread, and the maximum is one space-light paragraph rather than the typical line.

Heading order inside an article runs **h1 banner → content section h2 (26px) → Comments
(24px) → h3 (19px)**. `.article h2` matches only the blocks the page appends after the
content, because every content heading carries `.bp-sec`; it used to paint those at 34px,
so the least important heading on the page outranked all twenty-one real ones.

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
