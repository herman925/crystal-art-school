# Crystal Education Project

## Gstack

Use the `/browse` skill from gstack for all web browsing. Never use `mcp__claude-in-chrome__*` tools.

Available skills:
- /office-hours
- /plan-ceo-review
- /plan-eng-review
- /plan-design-review
- /design-consultation
- /design-shotgun
- /design-html
- /review
- /ship
- /land-and-deploy
- /canary
- /benchmark
- /browse
- /connect-chrome
- /qa
- /qa-only
- /design-review
- /setup-browser-cookies
- /setup-deploy
- /retro
- /investigate
- /document-release
- /codex
- /cso
- /autoplan
- /plan-devex-review
- /devex-review
- /careful
- /freeze
- /guard
- /unfreeze
- /gstack-upgrade
- /learn

## Skill routing

When the user's request matches an available skill, ALWAYS invoke it using the Skill
tool as your FIRST action. Do NOT answer directly, do NOT use other tools first.
The skill has specialized workflows that produce better results than ad-hoc answers.

Key routing rules:
- Product ideas, "is this worth building", brainstorming → invoke office-hours
- Bugs, errors, "why is this broken", 500 errors → invoke investigate
- Ship, deploy, push, create PR → invoke ship
- QA, test the site, find bugs → invoke qa
- Code review, check my diff → invoke review
- Update docs after shipping → invoke document-release
- Weekly retro → invoke retro
- Design system, brand → invoke design-consultation
- Visual audit, design polish → invoke design-review
- Architecture review → invoke plan-eng-review
- Save progress, checkpoint, resume → invoke checkpoint
- Code quality, health check → invoke health

## Design Direction Decision Log

Timestamped record of client-driven design-direction changes. Purpose: maintain a
clear, dated history of what was agreed when, so directional drift is traceable and
the agreed scope is documented. This file is internal working memory — the
authoritative external record is the WhatsApp thread, the dated proposal doc, and any
written confirmation sent to the client.

### Agreed references (baseline)
- **Round 1 (prior):** Client-agreed reference sites were **Proko** + **Will Kemp Art
  School**. Per PRD, design direction = "between Proko and Will Kemp": professional but
  warm, modern but not sterile. This is the contracted look-and-feel direction.

### 2026-05-25 — Client introduced new editorial/broken-grid references
- **What changed:** Client sent two new reference templates this round:
  - Crafti — https://crafti.axiomthemes.com/
  - Ingenioso — https://ingenioso.themerex.net/
- **What client wants:** Less rigid grid; more "alive" feel — overlapping artwork,
  layered text over/between images, background texture, moving/animated text, a
  "theme board" vibe, and a banner with multiple overlapping artworks + layered text.
- **Assessment:** This is a **"Creative Editorial" / "Broken Grid"** direction, which
  is different from the agreed Proko + Will Kemp direction. As such it is a
  **directional scope change** from what was scoped, not part of baseline.
- **Dev position communicated to client (via WhatsApp, 2026-05-25):**
  - The real cost driver is NOT "adding an animation." It is building a
    **client-editable, content-agnostic, responsive overlapping layout** — i.e. a
    layout that does not fall apart when Crystal swaps in images of different aspect
    ratios via the CMS, and that holds up on mobile. That robustness is what blows the
    budget, not static positioning per se.
  - A full-site overlapping + entrance/exit animation system, editable by the client,
    is effectively a small project of its own → **Phase 2, quoted separately**.
  - Static single image / simple carousel banner: doable in scope.
  - Simple fade-in on 1–2 specific spots: doable in scope.
- **Resolution / next action:** Dev (Herman) to **explore Elementor Pro's native
  overlap + motion-effect capabilities**. If a comparable effect is achievable with
  **minimal setup** (no bespoke layout engine required), it will be treated as
  **in-scope** and absorbed at no extra cost. If it requires a custom editable layout
  engine, it remains **Phase 2 (separate quote)**. Herman to report findings back to
  client.
- **Note for defensibility:** The proposal/PRD lists Elementor Pro as in-scope and
  sells it as fast custom-layout creation. Therefore avoid arguing the overlap is
  "impossible / a different system" — the defensible line is specifically that a
  *client-editable, responsive* overlapping layout is the cost driver, and that the
  agreed direction was Proko + Will Kemp (so this is a scope change).

### 2026-05-25 (update) — Crafti hero teardown + revised scope assessment

Investigated the Crafti hero/banner DOM + Revolution Slider config directly.

- **How Crafti's "layered photos" effect is actually built:**
  - It is NOT one composite image and NOT CSS blend modes. NOT a single pre-made
    image. It is **3 separate real image files** stacked with absolute positioning +
    z-index inside a Revolution Slider (`rs-module`) slide. Each is an independent
    `<rs-layer data-type="image">` element inside the active `<rs-slide>`, positioned
    via Revolution Slider's coordinate system (`data-xy`, `data-dim`):

    | Layer | File                       | Role                                        | z-index |
    |-------|----------------------------|---------------------------------------------|---------|
    | 1     | `slider-bg.png`            | Cream/linen texture background, fills slide | 12      |
    | 2     | `new-slide-2-img-2.jpg`    | Large pottery photo (right, behind)         | 13      |
    | 3     | `new-slide-2-img-1.jpg`    | Smaller tools photo (left, overlapping in front) | 14 |

  - **Positioning detail:**
    - Layer 2 (large): pinned to right edge (`x:r`) with `xo:70px` inward offset.
    - Layer 3 (small): also right-anchored but pushed further left (`xo:680px`) and
      down (`yo:80px`) → creates the diagonal "peeking from behind" overlap.
  - Overlap illusion = pure z-index stacking + offset X/Y coordinates. Nothing
    composited.
  - Both photos enter from right (entrance animation `data-frame_0: x:100%`) with
    staggered timing (`st:310` vs `st:610`).
- **Mobile behaviour (the key finding):** Both photo layers carry
  `data-vbility="t,t,t,f"` (4 values map to desktop / notebook / tablet / mobile).
  Trailing **`f` means both photos are HIDDEN on mobile (≤480px)** — only linen
  texture background + text (heading, subtitle, CTA button) remain. The right half
  of the hero is essentially empty on phones. Photos scale at fixed per-breakpoint
  sizes (not fluid reflow):

  | Asset                       | Desktop (1240px+) | Notebook (~1024px) | Tablet (~778px) | Mobile (≤480px) |
  |-----------------------------|-------------------|--------------------|-----------------|-----------------|
  | Large (`...img-2.jpg`)      | 800 × 822         | 560 × 575          | 281 × 289       | hidden          |
  | Small (`...img-1.jpg`)      | 382 × 452         | 280 × 331          | 134 × 159       | hidden          |

  Sensible trade-off for small screens, but the layered visual richness is completely
  absent on phones.
- **Why this matters for scope:** Crafti did NOT solve responsive overlap — it
  **dodged it** by hiding the images on mobile. The expensive problem (overlapping
  images staying coherent across breakpoints when a different-aspect-ratio image is
  swapped in) is avoided, not engineered. We can use the same dodge.

- **REVISED SCOPE DECISION (supersedes the cautious framing above):**
  - **IN-SCOPE / achievable:** A hero with 2–3 stacked images at fixed desktop/tablet
    positions, staggered fade/slide-in entrance animation, hidden or simplified on
    mobile — set up ONCE by Herman using client-chosen images at agreed sizes. This is
    Elementor Pro widget configuration (absolute positioning + motion effects /
    slider), NOT a custom layout engine. Equivalent to what Revolution Slider does for
    Crafti.
  - **STILL PHASE 2 / still the real cost driver:** Making the hero **client-editable**
    — i.e. Crystal swapping these images herself via the CMS and having the
    composition auto-arrange without breaking (especially on the breakpoints where
    images ARE shown). That robustness is the cost, not the visual effect.
  - **Line to communicate to client:** "I can build this exact layered hero. In-scope =
    I set it up with your chosen images at fixed positions, hidden on mobile (same as
    the reference). It becomes Phase 2 only if you need to freely swap these images
    yourself later and have them auto-arrange."
- **Confidence caveat:** Assessment is from stack reasoning (Elementor Pro has absolute
  positioning, motion effects, entrance animations), NOT from testing the exact widget
  workflow. Verify Elementor Pro's current positioning/motion capabilities before
  committing a specific look to the client.
- **Reference source:** Crafti theme — https://crafti.axiomthemes.com/

### 2026-05-25 — Ingenioso hero teardown (client's other reference)

Inspected the Ingenioso hero/banner DOM directly (https://ingenioso.themerex.net/).
Same vendor family as Crafti (ThemeREX), same slider engine (Slider Revolution).

- **How Ingenioso's hero is actually built:**
  - NOT one composite image. It's a Slider Revolution module (`<rs-module>` →
    `<rs-slide>` → `<rs-layer>`) made up of **7 separately positioned layers** stacked
    via absolute positioning + per-layer z-index. Concretely:

    | # | Type  | Asset / content                              | Approx. size (desktop) | z-index |
    |---|-------|----------------------------------------------|------------------------|---------|
    | 0 | image | `image-132.jpg` (big center portrait)        | 569 × 815              | 8       |
    | 1 | image | `image-133.jpg` (top-left, dark interior)    | 533 × 357              | 10      |
    | 2 | text  | "We enjoy building incredible things."       | 1325 × 300             | 14      |
    | 3 | image | `image-134.jpg` (small girl, bottom-left)    | 213 × 264              | 11      |
    | 4 | image | `image-135.jpg` (small square, behind)       | 213 × 264              | 13      |
    | 5 | image | `image-136.jpg` (orange flowers, right)      | 533 × 357              | 12      |
    | 6 | text  | "creative for tomorrow" (outline word)       | 3455 × 398             | 9       |

  - Each image is its own JPG file — not a flattened composite. Foreground photo sits
    above side photos; headline text sits above all photos; the giant outline word
    sits behind everything as a backdrop.
- **Animation / parallax:**
  - Mouse-driven parallax via Slider Revolution config:
    `parallax: { levels:[1,2,3,4,5,6,7,8,9,10,12,15,17,20,25,30], type:"mouse",
    origo:"slidercenter" }` — each layer drifts a different amount on cursor move,
    producing depth-on-hover. No 3D model — just per-layer parallax levels.
  - Each layer also has its own entrance animation (e.g.
    `data-frame_0="x:110%;o:1;skX:3px;"`) — they slide in from the right with a slight
    skew on page load.
- **Mobile behaviour (the key finding — differs from Crafti):**
  - Ingenioso does NOT hide layers on mobile. It **rescales the whole stage** and uses
    per-breakpoint coordinates. Slider Revolution declares
    `responsiveLevels: "1240, 1460, 785, 500"` → 4 breakpoints
    (desktop / laptop / tablet / phone).
  - Every layer carries 4 comma-separated values for X/Y offsets, font size, etc. e.g.
    layer 1: `data-xy="xo:199px,186px,-1px,-78px; yo:95px,46px,39px,35px;"` — at the
    smallest breakpoint the photo's X becomes -78px (shifts off-screen left) and Y
    becomes 35px. Headline carries `s:130,90,60,40` (font shrinks 130 → 40 px) and
    `l:150,110,90,50` (line-height shrinks).
  - Stage height itself shrinks: `editorheight:"900,600,480,480"` → 480 px on phones.
  - Result: on mobile the photos REMAIN LAYERED/OVERLAPPING (just smaller and
    repositioned), and the giant "creative for tomorrow" outline word is intentionally
    clipped by the stage edges. No reflow into a stacked column.
- **Why this matters vs Crafti:**
  - Crafti **dodged** responsive overlap by hiding photos on mobile (`data-vbility`
    trailing `f`). Ingenioso **engineered** it by hand-tuning per-breakpoint
    coordinates for every layer.
  - The expensive problem (overlap staying coherent across breakpoints) is solved by
    **manually authored breakpoint values per layer**, not by an auto-layout engine.
    Slider Revolution provides the inputs; a human has to fill them in for every
    image at every breakpoint.
- **REVISED SCOPE DECISION for Ingenioso-style hero:**
  - **IN-SCOPE / achievable:** A 5–7 layer stacked hero with mouse parallax, staggered
    entrance animations, hand-tuned at 3–4 breakpoints — set up ONCE by Herman with
    client-chosen images at fixed positions. Elementor Pro can do absolute positioning
    + motion effects per breakpoint, similar (though less ergonomic) to Slider
    Revolution. Acceptable as a one-time hero build.
  - **STILL PHASE 2 / cost driver remains the same:** Making it **client-editable** —
    Crystal swapping any of the 7 layers herself and having the overlap stay coherent
    at all 4 breakpoints. With Ingenioso's pattern this is even worse than Crafti,
    because every swapped image needs 4 sets of X/Y/scale values re-tuned by hand.
  - **Line to communicate to client:** "I can build this exact layered/parallax hero
    in-scope as a one-time setup with your chosen images. If you want to swap any of
    the 7 layers yourself later and keep the composition coherent across mobile/
    tablet/desktop, that's Phase 2 — each swap needs per-breakpoint repositioning,
    which Slider Revolution does by hand, not automatically."
- **Confidence caveat:** Layer count, z-indexes, and breakpoint values were read from
  the live `<rs-module>` markup. Elementor Pro's ability to match Slider Revolution's
  per-breakpoint coordinate authoring should be verified before committing.
- **Reference source:** Ingenioso theme — https://ingenioso.themerex.net/

#### Ingenioso — under-hero photo carousel (`elementor-element-67357e8`)

The horizontally-scrolling photo row directly below the hero (hands holding fruit,
person w/ hat & flowers, woman in red against louvred wall, dot indicator below).
**Different engine from the hero** — standard Elementor section + ThemeREX Swiper
widget, NOT Slider Revolution.

- **Class string decoded:**
  - `elementor-section elementor-top-section elementor-element` → top-level Elementor
    section wrapper.
  - `elementor-element-67357e8` → Elementor's unique per-element ID hash (scopes
    inline CSS).
  - `elementor-section-boxed` → inner container constrained to theme boxed width
    (1320 px); section itself spans full viewport.
  - `elementor-section-height-default` (×2, Elementor sometimes duplicates) → height
    from content, no forced min-height.
  - `sc_fly_static` → ThemeREX ShortCode flag = block is NOT in scroll-parallax
    "fly" mode (stays put while scrolling). `sc_fly_left/right/up/down` would
    translate on scroll; `_static` opts out.
  - `trx_addons_parallax_layers_inited` + `trx_addons_parallax_blocks_inited` →
    "I've been processed" markers added by ThemeREX Addons JS after parallax engine
    inits. No actual `data-parallax` attrs inside this section → plugin attaches but
    has nothing to animate. **Effectively no-ops on this block.**
- **Internal structure:** section → 1 full-width column (`elementor-col-100`) → 2
  widgets:
  1. Spacer widget (`elementor-widget-spacer`, 100 px tall) — gap above carousel.
  2. ThemeREX slider widget (`elementor-widget-trx_widget_slider`) — the carousel.
- **Slider wrapper class flags (`slider_outer slider_swiper_outer ...`):**
  - `slider_style_default` — default visual style.
  - `slider_source_custom` — slides manually picked, not from post category.
  - `slider_outer_direction_horizontal` — horizontal scroll.
  - `slider_outer_multi` — multi-slide view (>1 visible at once).
  - `slider_outer_nopagination`, `slider_outer_nocontrols` — no bullet pagination,
    no prev/next arrows. Single dot = only indicator. Navigation by swipe/drag only.
  - `slider_outer_nocentered` — slides left-aligned, not centered.
  - **`slider_outer_overflow_visible`** — crucial. Overflow visible so partial
    slides bleed off left/right viewport edges (banana-leaf + dark photo clipped on
    left edge).
  - `slider_outer_notitles` — no captions/titles overlaid.
  - `slider_outer_height_auto` — height adapts to image height.
- **Slides:** 7 total. Each = single `<img>` inside `.slider-slide.swiper-slide`
  div. Varying intrinsic heights (~350 px to ~501 px) → staggered-height look.
  Files: `image-10.jpg`, `image-23.jpg`, `image-12.jpg`, `image-148.jpg`,
  `image-13.jpg`, `image-76.jpg`, `image-130.jpg`. Each rendered slide ~390 px wide
  desktop. Swiper.js transforms `.swiper-wrapper` for left/right translation;
  individual slides stay `matrix(1,0,0,1,0,0)`.
- **Mobile / responsive:** Swiper.js inherently responsive — recomputes slide width
  + translation on resize. With `multi` mode + `overflow_visible`, mobile typically
  shows one full slide centered + peek of neighbors. User swipes horizontally.
  Spacer widget height adjustable per-breakpoint via Elementor editor (100 px
  desktop usually drops to ~40–60 px on mobile in this theme).
- **TL;DR vs the hero:** Hero = Slider Revolution stage, absolutely-positioned
  separately-animated layers, mouse parallax. This carousel = Elementor section →
  column → Spacer + Swiper. `trx_addons_parallax_*` + `sc_fly_static` look exotic
  but are no-ops on this block — just ThemeREX framework tagging every section so
  parallax JS can decide later. Visual effect (partial-bleed gallery row) comes
  entirely from Swiper multi-slide mode + `overflow: visible`.
- **Scope implication:** This carousel pattern is **trivially in-scope**. Elementor
  Pro has a native Image Carousel widget + the Posts Carousel widget; either can
  produce horizontal multi-slide w/ overflow-visible peek-of-neighbors via simple
  config. Client-editable from CMS (add/remove/reorder slides) without per-
  breakpoint hand-tuning. Unlike the hero, **no Phase 2 caveat** for this block.

### 2026-05-25 — Will Kemp Art School teardown (agreed baseline reference)

Inspected willkempartschool.com page structure + hero carousel directly. This is one
of the **Round 1 agreed baseline references** (Proko + Will Kemp). Documenting how
it's actually built so we can match the look without over/under-scoping.

#### Overall page layout

Classic content + sidebar WordPress stack:
- **Header** (banner, `ref_2`): centered logo "Will Kemp Art School — professional
  secrets for aspiring artists", "QEST Craft Scholar" badge top-left. Horizontal nav
  below: Home, New? Start Here, Acrylics, Oils, Drawing, Portraits, Shop All
  Courses, FAQ's & Contact Will, Free Videos, About Will, 7 Painting Principles,
  Student Success Stories, cart icon (0), search icon. Header duplicated into 2
  regions: `ref_7` desktop, `ref_60` mobile/sticky — each w/ own search box +
  mobile-menu toggle.
- **Main content area** (`ref_117`): hidden H1 ("painting tutorials, courses, tips
  & techniques"), carousel slider, then stream of 14 article previews (`ref_173` →
  `ref_804`) = blog/tutorial post cards. Pagination list (`ref_823`) w/ 7 page items.
- **Primary sidebar** (complementary, `ref_839`): right side. "hello…" intro w/
  B&W photo of Will Kemp + short bio. "Get Free Updates" email signup module
  promoting free 50-page PDF + 10 free drawing printouts + 10 free reference images.
- **Footer** (`ref_887`): main footer area (`ref_888`) + copyright bar (`ref_942`)
  "Copyright © 2012-2026 Will Kemp Art School" + Privacy / Terms / Contact links.
- **Utilities:** scroll-to-top (`ref_946`), 2 off-canvas mobile menus (`ref_950`,
  `ref_979`) each duplicating main nav, cart drawer (`ref_976`), notifications
  generic (`ref_1004`).

#### Hero carousel — what it actually is

**Smart Slider 3** (WordPress plugin — every element prefixed `n2-ss-`). Root:
`<div id="n2-ss-2" class="n2-ss-slider n2-ow n2-has-hover n2-ss-desktopPortrait
n2-ss-loaded" data-creator="Smart Slider 3" data-device-mode="desktopPortrait"
data-responsive="auto">`.

- **6 rotating slides**, each w/ same overlay structure:
  - Small uppercase top-line tag: "will kemp art school"
  - Large serif centered headline (slide theme)
  - Yellow rounded pill CTA → different destination per slide

  | # | Headline               | CTA                       | Destination                                          |
  |---|------------------------|---------------------------|------------------------------------------------------|
  | 1 | Learn to Paint         | Shop All Courses          | `/shop/`                                             |
  | 2 | Classical Techniques   | Beginner Drawing Course   | `absolute-beginners-drawing-course`                  |
  | 3 | Simple Approach        | Free Videos               | `how-to-paint-with-acrylic-paint-videos`             |
  | 4 | Online Courses         | Bestseller Acrylic        | `absolute-beginners-acrylic-course`                  |
  | 5 | Acrylics & Oil         | Student Success           | `student-success-stories`                            |
  | 6 | Easy to Follow         | Latest Acrylic Course     | `market-day-peaches-acrylic-still-life-course`       |

- **Controls:** previous arrow (`ref_162`) left edge, next arrow (`ref_164`) right
  edge, dot pager (`ref_166`) bottom center w/ 6 dots (`ref_167`–`ref_172`). Alt
  text duplicated across dots ("Slide 1 learn to paint" repeated) — accessibility nit.

#### Each slide = layered composition (NOT a flat banner)

Most interesting finding: each slide is **5 stacked layers + separate `<img>`**, NOT
a flat baked image. Structure for active slide (at current viewport):

1. **Bottom `<img class="skip-lazy">`** = photographic background. Each slide loads
   own pre-cached WebP at 809×486 from `/wp-content/uploads/slider/cache/`. Files:
   `Screen-Shot-2023-04-03-at-16.15.36.webp` (Learn to Paint),
   `slider-paint-pots.webp` (Simple Approach), `silder-peaches.webp` (peaches), etc.
   WebP for compression. `skip-lazy` keeps them eager-loaded so hero never blank.
2. **Stacked `.n2-ss-layer` divs** above photo (all `z-index: 2; position: relative`,
   vertically flowed inside flex container — **NOT absolute positioning**, unusual
   for slider, this is why layers can re-stack cleanly on mobile):
   - Outer wrapper layer (809×486) holds everything
   - Inner content wrapper (679×386) w/ padding to keep text off edges
   - Small uppercase tagline "WILL KEMP ART SCHOOL" (659×21, tracking-spaced label)
   - Large serif headline (659×240, dominant element)
   - Yellow pill CTA button (246×60) wrapping `<a href>` to per-slide destination
- **No runtime dark gradient overlay** — photos pre-darkened in source files so
  white text reads. Dimming is NOT a CSS effect.
- Arrows + dot pager (`.n2-ss-slider-controls-absolute-center-bottom`) are siblings
  of slide track, absolutely positioned on top via
  `n2-ss-slider-controls-absolute-left-center` utility classes.

#### Mobile / responsive strategy — two cooperating systems

**1. Smart Slider's own device-mode swap.** `data-responsive="auto"` → Smart Slider
listens to viewport width, swaps slider class between 6 modes: `desktopPortrait`,
`desktopLandscape`, `tabletPortrait`, `tabletLandscape`, `mobilePortrait`,
`mobileLandscape`. Each mode ships own font-size + padding + layer-visibility rules
as inline CSS (configured server-side).
- Layers use relative positioning inside flex column → naturally re-flow vertically
  when container shrinks. Tagline, headline, button stack tighter.
- Photo `<img>` scale-to-container w/ `overflow:hidden` → 16:10 desktop hero
  becomes shorter narrower hero on phone w/ same subject centered.
- 32×32 SVG arrows remain tappable at any width.

**2. OceanWP theme breakpoints around the slider.** Body carries
`oceanwp-theme default-breakpoint`. Stylesheet defines 60 distinct media query
blocks. Critical thresholds:
- `max-width: 959px` — OceanWP "mobile" cutover. Entire desktop nav collapses:
  `#top-bar-nav`, `#site-navigation-wrap`, `.oceanwp-social-menu` →
  `display: none`. `.oceanwp-mobile-menu-icon` → `display: block`. Page has 2
  duplicate nav `<list>` structures (`ref_950` + `ref_979`) — one is desktop bar,
  other is hidden off-canvas drawer using **Sidr library** (everything prefixed
  `sidr-class-*`). "Close mobile menu" at `#sidr-menu-close` is dismiss target.
- `max-width: 767px` — tablet/phone breakpoint. Blog entry cards switch from
  horizontal `flex-direction: row` (image left, text right) → vertical
  `flex-direction: column` (image top, text below). Header columns other than logo
  hidden: `.top-col:not(.logo-col) { display: none }`. Utility classes
  `.hide-tablet-mobile` + `.hide-tablet` kick in.
- `max-width: 600px` + `max-width: 480px` — fine-tuning for small phones (font
  sizes + padding shrink further).

**Mobile-specific logo swap:** `#site-logo.has-responsive-logo .custom-logo-link`
hidden under 959px, `.responsive-logo-link` shown instead → smaller/simpler logo
served on phones (no scaling wide horizontal one). Sticky variant
`.is-sticky .sticky-logo-link` for shrinking header on scroll.

`<meta name="viewport" content="width=device-width, initial-scale=1">` present →
real device width, no iOS 980px fallback.

#### Sidebar on mobile

Right-side complementary sidebar (`ref_839` — Will's photo + bio + "Get Free
Updates" signup) does NOT disappear on mobile. Body class **`sidebar-mobile`**
tells OceanWP to drop sidebar BELOW main content rather than hide. Mobile order:
header → hero slider → article list → sidebar (bio + email signup) → footer.
**Preserves email-capture conversion path on small screens.**

#### Stack summary

Typical WordPress stack:
- **OceanWP theme** → header / nav / sidebar / footer responsiveness via max-width
  media queries + Sidr off-canvas drawer
- **Smart Slider 3** → hero w/ internal device-mode system + layered
  relatively-positioned text/CTA over pre-darkened WebP photos
- **Elementor** → used elsewhere on page (per `elementorFrontendConfig` script)

**Clever trick:** hero text + buttons = real DOM layers, NOT baked into image.
Headlines stay crisp at any density. CTA = real `<a>` link (SEO + a11y win). Slide
recomposes on mobile without tiny illegible text. **This is the model to copy** —
it's the agreed baseline direction and it's achievable in Elementor Pro w/o the
broken-grid / per-breakpoint hand-tuning cost of Crafti / Ingenioso heroes.

#### Scope implication vs Crafti / Ingenioso

- Will Kemp hero pattern = **inherently in-scope + inherently client-editable**.
  Text + CTA are DOM, not coordinates → swapping a slide = upload new WebP + edit
  headline string + edit CTA URL in CMS. No per-breakpoint repositioning.
- This is the **defensible baseline**: it matches PRD direction (Proko + Will
  Kemp), it's achievable in Elementor Pro w/ native slider widgets, and it does NOT
  trigger Phase 2 cost driver.
- Reinforces 2026-05-25 dev position: Crafti/Ingenioso style overlapping/parallax
  hero is a directional scope change from this baseline, not a refinement of it.
- **Reference source:** Will Kemp Art School — https://willkempartschool.com/

### 2026-05-25 — Proko homepage teardown (other agreed baseline reference)

Inspected proko.com homepage directly. This is the **other Round 1 agreed baseline
reference** (Proko + Will Kemp per PRD §Goals). Documenting how it's actually built
so we have the full baseline picture before responding to client's new direction asks.

#### Stack

- **Angular** (`_ngcontent-proko-web-*` attrs on every element).
- **Tailwind CSS** utility classes layered on top of BEM-style component classes
  (`RecentStudentWork`, `ProductCardM`, `SSPoster`, etc.).
- Responsiveness via **two cooperating systems**:
  1. **Tailwind breakpoint prefixes** (`sm:`, `md:`, `lg:`) on individual
     elements. e.g. product card overlay uses `translate-y-[88px]
     md:translate-y-[96px]`; small-card variant rendered w/ `sm:hidden`, desktop
     counterpart w/ `hidden sm:block`.
  2. **Component-scoped CSS media queries** at ~1024px, 1192px, 1280px, 1600px →
     progressively enlarge dimensions per breakpoint.
- **Strategy:** "render different component for small screens, then progressively
  enlarge spacing/dimensions as viewport grows." Horizontal rails (Trending, Deals,
  etc.) = generic `Slider` component w/ `display: flex; gap: 24px;` + CSS
  `scroll-snap-align: start` → mobile = swipeable carousels, NOT fit-everything.

#### Hero / banner section

Visually looks like one wide banner. **Structurally = horizontal slider of
independent layered "poster" cards**, NOT a single image.

- Outer: `div.Slider` → child `div.Slider-container` w/ `display: flex;
  flex-direction: row; gap: 24px; overflow: auto;` + scroll-snap. **13
  `Slider-item` children**: first = "Summer Sale" promo, rest = course posters
  (Practical Perspective, Drawing Basics, etc.).
- Each slide = own Angular component (`proko-summer-sale-poster`,
  `proko-course-poster`). **Course poster = multi-layered**, NOT flat image:
  - `<picture>` w/ 3 `<source>` tags swapping artwork by viewport:
    - `(min-width: 1280px)` → 480×640
    - `(min-width: 1024px)` → 384×512
    - `(min-width: 0)` → 272×362
    - Browser downloads only the needed size.
  - Absolutely-positioned `<img>` (`position: absolute; object-fit: cover`) =
    artwork layer behind text.
  - Separate "PRESALE" / "SALE" diagonal ribbon element on top.
  - Gradient overlay (`ProductCardM-overlay`:
    `linear-gradient(180deg, transparent 0%, black 100%)`) over bottom of image for
    text readability.
  - Title + instructor + duration/lesson stats + START LEARNING / DETAILS buttons
    on top of overlay.
- Summer Sale slide = even more layered. Inline SVG of ice-cube logo as own DOM
  element (NOT baked image) + separate text blocks + BROWSE button.
- **Mobile behavior:** slides resize via CSS, NOT re-crop a single image.
  `.SSPoster`: `width: 272px; height: 362px` default → 384×512 @ ≥1024px →
  480×640 @ ≥1280px. Container = flex row + scroll-snap + `overflow: auto` → on
  phone see ~1.x posters + swipe horizontally. Dot indicators below reflect slide
  groups. **Small-screen story: smaller cards + swipe-to-browse, NOT stack/hide.**

#### "Recent Student Work" section (genuinely clever pattern worth copying)

Looks like masonry grid but is **2-row flex layout where each tile preserves own
image aspect ratio while keeping row height equal**.

- `div.RecentStudentWork`: `display: flex; flex-direction: column;
  max-width: 1216px; margin: auto;`. Holds header + `RecentStudentWork-items`
  block.
- Inside: 2× `RecentStudentWork-row` divs. Each row: `display: flex;
  flex-direction: row; justify-content: center; gap: 16px;` (gap bumps to 24px
  @ ≥1192px and 32px @ ≥1600px; 10px margin between rows).
- Each row = 4× `RecentStudentWork-item` tiles. **The trick:**
  - Every tile in row gets **same fixed height** (~281px current viewport).
  - Each tile width = whatever image aspect ratio dictates (wide sketchbook
    spread → ~461px; narrow portrait sketch → ~167px).
  - Row = equal height + uneven widths → visually tasteful collage, NOT rigid grid.
- Tile CSS: `position: relative; overflow: hidden; border-radius: 16px;
  box-shadow: 0 8px 40px rgba(0,0,0,0.08); flex-shrink: 0; flex-grow: 0;`
  — **`flex-grow: 0` is the key** — stops items stretching to fill row, preserves
  artwork's natural proportions.

**Image + avatar embedding inside each tile:**
- Artwork `<img>`: `position: absolute; inset: 0; object-fit: cover;` fills tile.
- Avatar on top: `RecentStudentWork-avatar --sizing-image-24`,
  `position: absolute; bottom: 8px; left: 8px; z-index: 20;`, 24×24 circular
  badge. Each tile contains `span.avatar_bg --top` (colored circle behind initial
  for users w/o profile photo — e.g. "H" letter bubble) + 2 `<img>` tags (primary
  + `.avatar_img --hidden` swapped in when real photo loads).
- Usernames NOT shown as text in tile. `<a>` tag's accessible label carries name
  (`@justjen`, `Benjamin Gustafsson`, `Lea`, `Patryk Makosz`, `Hodiyah Green`)
  → screen readers + link tooltips reveal. Visually only avatar identifies.
- Hover: `RecentStudentWork-previewOverlay` (translucent black layer
  `z-index: 10` + 30px icon) fades in via opacity transition.

**Mobile adaptiveness here (intentionally minimal):**
- Items sized by **intrinsic image aspect ratios** → no separate mobile-stacked
  variant needed.
- Only breakpoint-driven changes: gaps (16 → 24 → 32px) + decorative side sketch
  (`RecentStudentWork-sketch`) `display: none` default, only appears @ ≥1600px.
- Narrower screens → less horizontal space → each tile (still respecting aspect
  ratio) ends up shorter. Equal-height property automatic from flexbox
  `align-items: stretch` / equal-row-height behavior.
- Row stays horizontal + scales down. **No reflow to vertical stack.**

#### Other patterns noted in passing

- Course product cards (`ProductCardM`) have height tiers: 364px default →
  424px @ ≥1192px → 468px @ ≥1600px.
- Hover affordance = transform-based reveal: stats block translated down by
  88/96px and slid up on `group-hover`. **Touch devices (no hover) just see
  title + badges → intentional**, no "stuck mid-state" issue.

#### Stack summary + comparison vs Will Kemp

- Proko = bespoke Angular app w/ Tailwind + component-scoped CSS. Much more
  engineered than Will Kemp's WordPress + OceanWP + Smart Slider 3 stack.
- Both reference sites' heroes = **multi-layered DOM components, NOT flat baked
  images, NOT broken-grid, NOT absolute-positioned overlapping artwork.** Both
  use horizontal-scroll multi-card carousels at the top (Will Kemp = single-slide
  rotator, Proko = horizontal-scroll deck), w/ text/CTA as real DOM layers.
- Neither hero uses parallax, mouse-driven layers, or hand-tuned per-breakpoint
  coords. Both rely on the layout engine (flexbox + scroll-snap, or relatively
  positioned flex columns) to re-flow naturally.

#### Scope implication — completes the baseline picture

- **Both PRD baseline references confirm: the contracted direction is
  carousel/grid + real-DOM text overlays, NOT broken-grid + absolute overlap.**
- Proko's "Recent Student Work" section = a **strong candidate pattern to copy**
  for Crystal's student gallery / portfolio area: equal-row-height flex w/
  aspect-ratio-preserving tiles is implementable in Elementor Pro using its
  native Gallery widget set to `flex` layout w/ `flex-grow: 0` on items, OR via a
  simple custom CSS snippet. **In-scope, no Phase 2 caveat.**
- Reinforces 2026-05-25 dev position: any client-pushed broken-grid/parallax
  direction is **not present in EITHER baseline reference** → directional scope
  change, not a refinement of baseline.
- **Reference source:** Proko — https://www.proko.com/

### 2026-05-25 — WhatsApp exchange + reaffirmed dev position (logged verbatim)

Logging this exchange because the new references (Crafti, Ingenioso) are materially
more demanding than the agreed PRD baseline (Proko + Will Kemp). Future drift checks
should refer back to this record.

#### Client message (16:41 + 17:07, +852 6184 6003)

- Client: "不如我選template 給你參考好嗎？現在看如果一整版也是這個設計太工整 ...
  我找不到什麼網站 不如我上網找template好似直接些"
- Then sent:
  - https://crafti.axiomthemes.com/ — "像這個不要一排排的圖片很工整的感覺 生動一些
    例如一些重疊，可以加啲背景 文字有些郁動 或者有些像theme board"
  - https://ingenioso.themerex.net/ — "Banner改為這個不同畫作重疊 表面及夾著有不同
    layers 的文字"

#### Dev (Herman) replies (17:17–17:26)

- 17:17 "如果banner要多樣重疊係完全另一個系統，係一1個component tree，會當一個
  additional feature 🙏 只係做呢個可能已經要1個月。Static 一張相或者幾張跑馬燈
  係可以做的"
- 17:22 "動畫的話（包括入場及離開的動畫）時間上都困難的。好似好簡單加個animation
  但係其實涉及的部分包括component 分割，更多的反應性測試，甚至如果個animation
  嘅文字圖片係會修改（等於要一套可以用於任何文字，任何圖片的動態系統）都抱歉
  現階段時間係做唔到住🙏🙇‍♀️"
- 17:25 (a) "我睇完之後，大概明白你想追求的感覺，這種 'Creative Editorial' (雜誌風)
  的設計確實比傳統網站更生動。但這兩款 Template 採用的其實是 'Broken Grid'
  (打破網格) 的架構，這跟目前合約規劃的標準 CMS 系統是完全不同的邏輯"
- 17:25 (b) "呢類重疊設計如果要在 CMS 讓你自己換圖換字，我們需要開發一套專門的
  佈局引擎，否則你換一張不同比例的圖，整個重疊效果就會散晒，係無法簡單處理。
  另外圖片重疊在電腦看很美，但在手機細螢幕很容易遮擋到文字，這需要額外多一倍
  的時間去做跨裝置測試（做到但係如上，測試時間會大增）"
- 17:26 (a) "如我剛才提到，單是處理 Banner 的多層重疊與入場動畫，就已經等同做
  一個小型專案的工程量，這在現有時程和預算內確實無法負荷🙇‍♀️🙇‍♀️"
- 17:26 (b) "如果想在不增加成本的情況下提升生動感，我們可以在現有框架下，針對 1-2
  個特定位置加入簡單的淡入 (Fade-in) 效果。但如果你需要像範本那樣全站重疊和
  多層次動畫，這需要當作 Phase 2 (第二階段) 的額外功能來報價和規劃時程🙏🙇‍♀️"

#### Reaffirmed dev position (commitment + line in the sand)

- **Commitment to client:** Herman will explore Elementor Pro's native widgets for
  overlap + motion. If a comparable effect is achievable w/ **minimal setup** (a few
  clicks, no custom layout engine), absorb into current scope at no extra cost. If
  it requires building a client-editable responsive overlap engine, it is **Phase 2,
  quoted separately**.
- **Line in the sand:** Contracted design direction = **Proko + Will Kemp** ("between
  Proko and Will Kemp: professional but warm, modern but not sterile" — PRD §Goals
  and Background Context). Both reference sites use standard grid + carousel layouts
  (Will Kemp teardown confirms: Smart Slider 3, layered DOM, no parallax, no
  multi-image overlap, no broken-grid).
- **Therefore:** Crafti + Ingenioso style overlap/parallax/broken-grid is a
  **direction change, NOT a refinement**, of the contracted baseline. Treating it as
  free scope would be **unpaid extra work** on a fixed HK$15,000 fee.
- **Concession Herman is willing to make in-scope** (matches WhatsApp 17:26):
  - 1–2 specific fade-in motion accents on existing layout.
  - Static single image OR simple carousel banner.
  - Elementor Pro native overlap IF achievable in minimal-setup mode.
- **What stays Phase 2:**
  - Site-wide broken-grid / overlapping layouts.
  - Entrance + exit animation system that works across arbitrary client-uploaded
    content.
  - Client-editable responsive overlap engine (per-breakpoint hand-tuning per
    swap = unbounded ongoing cost).

#### Why this exchange matters for future sessions

- The 2 new reference sites are objectively more demanding than the contracted
  baseline. Any future "but the client likes Crafti/Ingenioso" pressure should be
  met w/ the Will Kemp teardown above (proves baseline = simpler architecture) and
  this WhatsApp record (proves the line was drawn on 2026-05-25 in writing).
- Herman is **doing the Elementor exploration as a goodwill gesture**, not as a
  scope obligation. If exploration shows minimal-setup native support exists, it
  becomes a free upgrade. If not, the Phase 2 framing already communicated stands.
- Avoid scope creep through accumulation: small "can you also just..." additions
  in this direction each individually feel small but together = the unscoped Phase
  2 project. Push back early, push back in writing.

### 2026-05-25 (later) — Elementor capabilities VERIFIED + corrected position + report-back sent

Did the Elementor exploration that was promised to client (twice) in earlier messages.
Findings are from Elementor's own docs/academy, not assumption. **This supersedes the
earlier "parallax = needs custom code" framing.**

#### What was verified (sources: elementor.com/help, elementor.com/academy)

- **Absolute / relative / fixed positioning, per device** — native Elementor Pro, no
  code. Per-breakpoint position controls exist in-editor.
- **Entrance animations** — 37 built-in, selectable PER DEVICE (incl. "None" per
  device). Native, no code.
- **Mouse-track / parallax** — native Elementor Pro Motion Effects. "No external
  libraries or custom code required." Elementor's own academy has a tutorial doing
  exactly the Crafti/Ingenioso move: layering images with position:absolute + mouse
  track on a hero.
- **Mouse effects are DESKTOP-ONLY** — confirmed by Elementor directly ("Mouse effects
  will only affect desktop devices" / "do not work on mobile or tablet"). So parallax
  contributing nothing on touch is a fact, not a guess.

#### CORRECTION to earlier record (important)

- Earlier WhatsApp msg (and earlier log entries) framed Ingenioso parallax as
  "要另外寫程式 / bespoke JS / discrete add-on." **This is WRONG.** Parallax is native
  Elementor Pro, a few clicks, no code. Do NOT bill it as "custom development."
- Consequence: parallax is now treated as **IN-SCOPE** (it's near-zero effort in the
  contracted toolset), NOT a paid extra. Reason logged explicitly so the in-scope call
  is grounded in *the tool does it natively*, NOT a general willingness to absorb
  Crafti/Ingenioso features.

#### Corrected scope position (current, authoritative)

- **IN-SCOPE (contracted delivery, NOT a favour — do not call it "free"):** Layered
  DOM banner (images + text stacked, real DOM not flat image) + staggered entrance
  animation + mouse parallax. Built ONCE by Herman with client-chosen images. Grounds:
  this matches the **pre-quotation** agreed references (Will Kemp + Proko), both of
  which use layered-DOM heroes — so it is delivering the agreed direction, and
  Elementor does all of it natively.
- **Mobile:** simplified (Crafti-style). Mouse parallax does nothing on touch anyway;
  multi-layer overlap obscures text on small screens. Client told mobile = simplified
  version. (Sets expectation in writing that full mobile overlap is NOT promised.)
- **PHASE 2 (the ONE true paid extra — narrowed after fact-check):**
  **Client-editable** layered hero — Crystal swapping the overlapping images herself
  via CMS and having the composition stay coherent across desktop/tablet/mobile. No
  auto-layout for overlap → every swapped image needs per-breakpoint manual
  repositioning. "I set it up once = in-scope. A system where you swap images yourself
  unlimited times without it breaking = Phase 2."

#### Contract chronology (the load-bearing fact)

- **Will Kemp + Proko were provided BEFORE the HK$15,000 quotation** → baked into the
  price → anything matching them is contracted, already paid for.
- **Crafti + Ingenioso were provided AFTER quotation** → features that appear ONLY in
  them (not in the baseline pair) are new. The defensible line is chronological, not a
  matter of opinion: in-baseline = in-scope; only-in-new-templates = scope change.

#### Report-back message — DRAFTED but NOT SENT (correction)

- **CORRECTION:** The "Elementor checked → parallax in-scope, 我照做" report-back was
  DRAFTED but **NOT actually sent to the client.** Earlier wording in this section
  implying it was sent is wrong. It was never delivered — which is fortunate, because
  the deep Ingenioso teardown (added later, see below) showed the "parallax is native /
  in-scope" claim was over-simplified. **No promise to retract.**
- (Note: an earlier draft that called the banner "out of scope 免費服務" was also
  DELETED before sending — that framing would have conceded the baseline argument.
  Avoid it.)

#### What was ACTUALLY last sent to the client (the true current state)

- The genuinely-sent thread ends at the message describing the Phase 2 cost driver:
  *"如果你想自己喺後台換嗰幾層重疊嘅相、又要喺電腦/平板/手機都唔散——呢個先係真正貴
  嗰part。因為呢種重疊係冇得自動排，每換一次相都要人手喺幾個螢幕尺寸逐個重新調位
  （Crafti 自己更加係索性喺手機 cap 咗啲相唔顯示嚟避開呢個問題，Ingenioso 就係硬係用
  人手逐個 breakpoint 調返）"*
- **This is the strongest, most defensible point in the whole thread, and it is fully
  consistent with the teardown.** The client has NOT been told parallax is free/native.
  The ball is in the client's court. **No correction message is required — nothing
  inaccurate is outstanding.** Sending an unprompted walk-back would manufacture a
  problem and look wishy-washy.
- Items already sent and holding: (1) Crafti/Ingenioso = editorial direction change vs
  agreed baseline; (2) the real cost driver = self-editable cross-device overlap;
  (3) the Crafti-hides-on-mobile vs Ingenioso-hand-tunes contrast.
- **For the NEXT message** (whenever client replies), keep this distinction ready:
  "similar animated layered banner (stacked images + individual entrance + light mouse
  drift) = in-scope, will build" vs "pixel-exact Ingenioso (unified 3D parallax + mask
  wipes + per-letter type animation + 4s choreography) = needs Slider Revolution /
  custom GSAP = Phase 2 / small-project piece." Do NOT volunteer the word "parallax"
  as a blanket in-scope promise; if it comes up, scope it to *light mouse drift*, not
  the full Ingenioso system.

#### Process note (so the over-promise mistake doesn't recur)

- A web search confirmed Elementor *has* a mouse-track motion effect, and that was
  over-generalised in the earlier entry to "so the whole Ingenioso parallax is native."
  The search verified the feature EXISTS, not that it MATCHES the reference's
  complexity. **Lesson:** "tool has feature X" ≠ "tool reproduces this specific
  implementation of X." Verify depth, not just existence.

#### IMPLEMENTATION GUARDRAIL (for when build happens — protects the Phase 2 line)

- The Phase 2 boundary only holds if the build reflects it. When building the hero,
  **lock the layered hero section** (template / locked / non-trivial to edit) so that
  swapping the overlap images genuinely requires Herman. If the hero is left freely
  editable in the standard Elementor editor, client WILL swap an image, it WILL break
  on mobile, and "but you built it editable" becomes a fair complaint. Decide
  deliberately: lock it, or accept image-swaps as light ongoing support.

#### Open caveats / time-cost honesty

- "Extra testing time" has been mentioned to client ~twice without attaching to a
  concrete number. If the layered banner genuinely threatens the 15-week timeline,
  state it concretely (pushes X by N days) when relevant; otherwise drop the vague
  "this'll take longer" notes — they erode credibility if they never materialise.
- **Reference sources (verified):** elementor.com/help/motion-effects/,
  elementor.com/help/entrance-animations/, elementor.com/academy/motion-effects-collection/

### 2026-05-25 — Site-wide no-border + classic-waterfall 18px curve (visual baseline)

Client-driven directive codified after multiple feedback rounds. **Applies site-wide
unless explicitly overridden.**

- **No black borders anywhere.** All cards, photo frames, banner frames, container
  strips, and panel chrome render borderless. Previous selective border use is
  superseded. New rule in `styles-v5.css`: `.card.no-border { border-color: transparent }`
  + `.card.no-border .photo { border-color: transparent; border-radius: var(--radius-card) }`.
  Apply by default; opt back into bordered variant only when client explicitly asks.
- **Card corner curve = 18px** (the classic-waterfall layout's curve). Single source
  of truth. All cards, photo frames, banner frames, hover overlays adopt this radius.
  Previous per-page curve values (varied 4px–16px) are deprecated.
- **Rationale:** Client wants visual consistency across F1/L*/A*/C* pages so the
  brand reads as one site, not stitched-together templates. Borderless + 18px
  matches Will Kemp's softer warm feel rather than Crafti's bordered editorial look.
- **Future agents:** Do NOT re-introduce black borders or per-page curves "for
  visual interest." If a section feels flat, fix with typography / spacing / accent
  color — not chrome.

### 2026-05-25 — Gallery split: C3 Teacher's Work / C4 Student Work

Client-introduced scope, not in original PRD §Pages. Confirmed in subsequent client
feedback round; PRD §Site Structure now lists both as Pages #8 and #9.

- **C3 Teacher's Work Showcase** — Google-Photos-style grid of Crystal's artwork.
  Hover overlay = View (lightbox) + Buy (Etsy outbound). Reuses existing gallery
  CSS pattern.
- **C4 Student Work Showcase** — collage layout supporting Portrait / Landscape / A4
  ratios on the same row. **Stable row height** is the load-bearing constraint: a
  landscape tile in row N must have the SAME pixel height as the portrait tile
  beside it. Widths vary by aspect ratio; heights are locked per row. Hover overlay
  surfaces student username + avatar (NOT "View" / "Buy" — student artwork is not
  for sale).
- **Both** have lightbox on click + black-gradient hover overlay for legibility.
- **Implementation note for future agents:** Don't merge C3 and C4 back into a
  single Gallery page; client explicitly wants the split (Teacher = sales-driven,
  Student = community-driven, different overlays).

### 2026-05-25 — Shopping cart in header + Etsy bridge (Phase-1 commerce plan)

Two related decisions captured together because they form the launch commerce
posture.

- **Shopping cart visible in F1 site header** (badge w/ item count). Drives the
  per-course / per-lesson WooCommerce purchase flow defined in PRD §Membership and
  §Pricing. Implemented in F1 nav; cart drawer/page pattern reused from A5/A7.
- **Etsy bridge for teacher artwork.** Native painting/equipment commerce is
  Phase 2 OUT. To still let visitors purchase Crystal's paintings at launch, the
  C3 Teacher Gallery's **Buy** button is an outbound link to the matching Etsy
  listing (`target="_blank"`). Etsy handles checkout, fulfilment, payment, returns.
  No WooCommerce SKU for artwork at launch.
- **Why this is defensible vs §OUT "painting/equipment sales":** the §OUT line
  refers to *native* commerce on Crystal's own WordPress site (which would require
  a second WooCommerce product flow, separate fulfilment plumbing, shipping
  integration, etc.). Linking out to an existing Etsy listing is content-link
  authoring, not a new commerce subsystem. Documented under PRD §Site Structure
  §Etsy Bridge.
- **Future agents:** if client asks to "move sales back onto the site," that IS
  Phase 2 (separate quote). Etsy outbound is the launch-day answer.

### 2026-05-25 — Ingenioso banner DEEP teardown (parallax engine + entrance choreography)

Supersedes the earlier surface-level Ingenioso teardown. Inspected the live
`<rs-module id="rev_slider_7_1">` config, GSAP timeline, per-layer keyframes, and
mouse-parallax engine. **Important: this changes the in-scope/Phase 2 line for the
hero — see §Scope correction at the bottom.**

#### Module-level setup

- **Slider Revolution 6.7.34** "Hero" module (single slide, no rotation — that's
  why there's never a transition).
- Config:
  - `sliderType: "hero"`, `sliderLayout: "fullwidth"`
  - `gridwidth: 1920, 1440, 778, 480` + `gridheight: 900, 600, 480, 480` →
    virtual canvas all `data-xy` / `data-dim` coords are relative to. 4 numbers =
    SR scales whole scene proportionally per breakpoint.
  - `responsiveLevels: 1240, 1460, 785, 500`
  - `perspective: 600` + `perspectiveType: "global"` → **shared 3D camera** for
    whole module. Depth-based parallax doesn't pop layers into their own 3D
    contexts. (Caveat: `getComputedStyle` shows `perspective: none` at rest — SR
    injects it dynamically on mouse interaction.)
  - `lazyType: "smart"`, `progressBar: disabled`

#### The parallax engine (the heart of the effect)

```js
parallax: {
  type:   "mouse",           // cursor-driven, NOT scroll
  origo:  "slidercenter",    // (0,0) = geometric center of slider
  speed:  0,                 // instantaneous response (no easing/lag on follow)
  levels: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 15, 17, 20, 25, 30]  // 16 depth tiers
}
```

- **Mouse-driven, NOT scroll-driven.** Scroll page = nothing parallaxes. Move
  mouse = every layer reacts.
- **`origo: slidercenter`** → layers translate OPPOSITE to cursor offset from
  center → classic "look around the scene" feel.
- **16 depth tiers (1–30).** Each `<rs-layer>` gets a level. Low (1, 2) = shifts
  few pixels. High (25–30) = shifts dramatically. Foreground/background
  separation. Orange headline + outline word sit on different levels from the
  5 photos → type appears to float in front of imagery.
- **Global perspective** means high-level layers also pick up subtle Z-axis skew
  relative to low-level layers, not just translation.

#### The 4-second cascading entrance (GSAP timeline, 7 layers)

Each layer has `data-frame_0` (start state), `data-frame_1` (entrance), and
`data-frame_999` (exit). Timing in ms from slide start (`st` = start, `sp` =
duration, `e` = easing).

| Order | Layer        | Type  | Start  | Duration | Easing      | Technique                                                                  |
|-------|--------------|-------|--------|----------|-------------|----------------------------------------------------------------------------|
| 1     | layer-0      | image | 670ms  | 1500ms   | power3.out  | Slide in from `x:110%` + `skX:3px`, revealed through mask wiping `x:-100%` |
| 2     | layer-8 (headline)  | text  | 780ms  | 1500ms   | power3.out  | Per-WORD reveal (`d:8` = 8ms word stagger), each word rises from `y:100%` behind mask, `power2.out` |
| 3     | layer-16 (outline "creative for tomorrow") | text | 1380ms | 2000ms | power3.out | Per-CHARACTER reveal (`d:12` = 12ms char stagger), each char rises from `y:100%` + rotates `-35deg → 0deg`, `power4.out` |
| 4     | layer-9      | image | 2120ms | 1500ms   | power3.out  | Same masked slide-in                                                       |
| 5     | layer-1      | image | 2380ms | 1500ms   | power3.out  | Same masked slide-in                                                       |
| 6     | layer-15     | image | 2590ms | 1500ms   | power3.out  | Same masked slide-in                                                       |
| 7     | layer-10     | image | ~2800ms| 1500ms   | power3.out  | Same masked slide-in                                                       |

**Design intent baked into the timing:**
- Composition does NOT assemble in reading order. First photo at 670ms, then
  headline at 780ms (intentional 110ms gap) — text appears to "answer" the image
  rather than land on top.
- Outline word starts 600ms after headline (1380 vs 780ms) + takes longer
  (2000ms vs 1500ms) — per-character rotation needs more time to feel graceful.
- All 4 later photos use IDENTICAL motion vocabulary (`x:110%, skX:3px`, masked
  wipe, `power3.out`, 1500ms) → read as one cascading "deal-the-cards" move, not
  4 separate gestures. 210–260ms staggers between them = single beat, distinct.
- Exits in `data-frame_999` have `st:w` ("wait for slide change"). Hero mode = no
  next slide = **exits never fire**. Mouse parallax takes over indefinitely.

#### Mask + skew: the signature wipe (every photo layer)

```
data-frame_0:       x:110%;  o:1;  skX:3px;
data-frame_0_mask:  u:t;     x:-100%;
data-frame_1:       e:power3.out; sp:1500;
data-frame_1_mask:  u:t;
```

- Layer starts 110% right + 3px horizontal skew. Mask starts 100% left.
- During 1500ms `power3.out`: layer slides left into place; mask slides right
  into place; they meet → image "revealed through a slit."
- 3px skew (eased out) = tiny perceived momentum a pure translate would lack.
- `power3.out` (cubic ease-out) decelerates quickly at end → photo "settles"
  rather than "stops."

#### Why text uses two different techniques

- **Headline (layer-8, orange)** = split into WORDS (`data-frame_0_words` /
  `data-frame_1_words` with `d:8`). Each word = mini-clip translating up from
  below clip-mask. Fast + crisp — for a sentence reader absorbs quickly.
- **Outline word (layer-16, "creative for tomorrow")** = split into CHARACTERS
  (`data-frame_0_chars` / `data-frame_1_chars` with `d:12`, `rZ:-35deg → 0deg`).
  Each glyph tumbles up + rotates from -35° to upright with `power4.out`
  (stronger ease-out than `power3`). Decorative typography (transparent fill +
  2px white stroke via `data-tst`) → per-char flourish reads as ornament, not
  copy.

#### Responsive scaling of motion

- Headline's `data-text` attr encodes 4 font sizes + line-heights + letter
  spacings:
  ```
  s:130,90,60,40       (font-size px at 4 breakpoints)
  l:150,110,90,50      (line-height)
  ls:-8,-8,-7,-3       (letter-spacing)
  ```
- SR interpolates these alongside positional `xo`/`yo` arrays.
- **Timings + easings + parallax levels stay identical across devices.**
  Choreography preserved; only dimensions scale.

#### Mobile = different module entirely (NOT same animation scaled down)

- Slider is **hidden on tablet/mobile** via `sc_layouts_hide_on_tablet
  sc_layouts_hide_on_mobile` classes on the parent section.
- A **static image-widget variant** is rendered on those breakpoints instead.
- Why: avoid running 7-layer GSAP timeline on low-power devices.

#### Total perceived duration

- 670ms first start + ~2800ms last start + 1500ms last duration ≈ 4.3s ceiling.
- With overlaps, **perceived completion ≈ 4.0s**. Mouse parallax engine takes
  over after.

#### SCOPE CORRECTION — supersedes earlier "Elementor does this natively"

Earlier note (2026-05-25 later, "Elementor capabilities VERIFIED") confirmed
Elementor Pro has native motion effects + entrance animations + mouse track. That
is true — but the FULL Ingenioso choreography requires capabilities Elementor
native **does NOT provide:**

| Ingenioso feature                                       | Elementor Pro native? | Status            |
|---------------------------------------------------------|-----------------------|-------------------|
| Mouse-track parallax (single-layer)                     | YES                   | In-scope          |
| Per-layer absolute positioning + per-device coords      | YES                   | In-scope          |
| Built-in entrance animations (37 presets)               | YES                   | In-scope          |
| **Shared 600px global 3D perspective** across layers    | NO                    | Custom CSS needed |
| **16-tier depth-based parallax** (different multipliers per layer) | PARTIAL — single-layer only | Workaround per-layer |
| **Masked-wipe reveal** (layer + counter-mask sliding to meet) | NO — Elementor entrance presets don't include masked reveals | Custom CSS / GSAP |
| **Per-word text stagger** entrance                      | NO (single block animates as one) | 3rd-party addon (e.g. Premium Addons, Crocoblock) or custom |
| **Per-character text stagger** + rotation (`rZ:-35→0`)  | NO                    | 3rd-party addon or GSAP |
| Mobile-swap to entirely different static widget         | YES (Elementor responsive controls) | In-scope          |

**Net assessment of full Ingenioso clone:**
- Visually "70% there" achievable in Elementor native: stacked images +
  entrance fade/slide + single mouse-track parallax + responsive image sizes.
- "100% there" (masked wipes + per-char text choreography + 16-tier global
  perspective) requires either a 3rd-party Elementor addon (Premium Addons
  Pro, Crocoblock JetTricks, Slider Revolution-as-WordPress-plugin) OR custom
  CSS/GSAP work.
- **Slider Revolution itself IS available as a WordPress plugin** (~$30/yr).
  Installing it and importing an Ingenioso-style template = closest-to-1:1
  match without writing code. NOT currently in PRD §Third-Party Services
  Budget.

#### Revised scope position for the hero (current authoritative)

- **IN-SCOPE (delivers contracted Will Kemp/Proko-baseline direction +
  Elementor-native enhancements):** Layered DOM hero, single-direction entrance
  animations, single-layer mouse-track parallax, per-device positioning, mobile
  static-image swap. All Elementor Pro native. No new plugin cost.
- **STILL IN-SCOPE (small extra effort, defensible):** Build in Elementor +
  custom CSS for the masked-wipe reveal on 1–2 hero images. Bounded task.
- **STILL PHASE 2 (or "Slider Revolution license add-on"):**
  - Full 16-tier global-perspective parallax across 7 layers.
  - Per-word + per-character text reveal choreography.
  - Client-editable layered hero (the ORIGINAL Phase 2 line — unchanged).
- **Client decision needed if they want the FULL Ingenioso look:** add Slider
  Revolution plugin (~$30/yr — Crystal pays, like Elementor Pro) → Herman
  imports Ingenioso template, swaps images. Becomes one-time setup, mostly
  in-scope, **but Phase 2 image-swap line still applies** because SR templates
  are still per-breakpoint hand-tuned.

#### Future agents — read this before quoting hero work

- DO NOT promise full Ingenioso-equivalent in Elementor native alone. Earlier
  log entry was correct that motion effects exist; this entry corrects that
  the FULL choreography requires either 3rd-party addons, custom code, or
  Slider Revolution itself.
- DO offer the "70% in-scope, 100% via SR plugin (client pays $30/yr)" framing
  if client asks for exact Ingenioso match.
- Phase 2 image-swap line (per CLAUDE.md WhatsApp 2026-05-25 + PRD §Hero
  Banner Specification) is UNCHANGED regardless of which path is chosen.

### 2026-05-25 (final) — PRD §Hero Banner Spec expanded with formal Phase 2 boundary table + concession rule

Client requested PRD §Hero Banner Spec be updated "in detail" so the in-scope vs
Phase 2 boundary is defensible in writing (not just in CLAUDE.md decision log) for
the Ingenioso deep-teardown primitives. Done.

#### What changed in PRD §Hero Banner Specification

1. **New "Reference teardown" subsection** at the top — lists Ingenioso's actual
   stack (Slider Revolution 6.7.34 + GSAP) with the exact primitives (16-tier
   levels array, 600px global perspective, masked-wipe + skew, per-word + per-char
   split-text, power2/3/4-out easings, 4s timeline w/ named staggers). Marked
   **informational, NOT a build spec** — so the document records what was asked
   for without committing to it.

2. **"What gets built (IN SCOPE)" table** — maps each Elementor-native primitive
   to its Ingenioso parity rating (✅ matches / ⚠️ partial). Targets ~80%
   perceptual parity at a glance. No GSAP, no SR, no SplitText.

3. **"What stays Phase 2" table** — every Ingenioso primitive that Elementor
   native cannot deliver gets its own row with:
   - Why excluded (stack mismatch)
   - What it would require (custom JS / GSAP / SplitText paid lib / SR license)
   - Indicative effort (0.5–3 dev days each)

   Items individually named: 16-tier shared-perspective parallax, masked-wipe-w/-skew,
   per-word split-text, per-character split-text w/ rotation, GSAP timeline
   choreography, 4s cascading entrance w/ overlapping staggers, per-char glyph
   rotation, Slider Revolution module itself, client-editable layered hero,
   site-wide broken-grid, entrance system for arbitrary content.

4. **NEW "Concession path" rule** — within HK$15,000, client may pick **ONE**
   Phase 2 flourish to absorb under the 1-month post-launch support buffer,
   provided:
   - Hand-coded as a single GSAP/Motion One snippet (no SplitText paid lib, no SR)
   - Applies to ONE specific element only (headline OR portrait OR ≤3-tier depth)
   - Hero stays locked post-build (snippet is part of locked template)
   - Second flourish OR scope extension = re-quote

   This is **goodwill within scope, not a precedent**. Written default = the
   5–7-element Elementor-native build.

#### Why this matters for future agents

The earlier in-line PRD spec was short and could be read as "we'll match
Ingenioso." After client supplied the GSAP-level teardown the answer became
"~80% match in scope, the rest is Phase 2." This expansion makes that answer
explicit at the spec level rather than relying on this CLAUDE.md log.

- If client asks "can we have the masked-wipe?" → point to PRD §Hero Phase 2
  table row "Masked-wipe-with-skew" + offer Concession path (one flourish only).
- If client asks "can we have split-text on every heading site-wide?" → Phase 2,
  re-quote (SplitText is paid lib + site-wide application is not one flourish).
- If client asks "can we install Slider Revolution and import the Ingenioso
  template?" → PRD §Hero Phase 2 table row "Slider Revolution module itself" +
  CLAUDE.md earlier entry (2026-05-25 SCOPE CORRECTION) framing: ~$30/yr client
  cost, 2–3 dev days, Phase 2 image-swap line still applies.
